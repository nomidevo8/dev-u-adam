<?php
/**
 * Plugin Name: Dev Chouchou Box
 * Description: Custom flavor selector for WooCommerce products.
 * Author: Noman Dev
 * Version: 1.1
 */

if (!defined('ABSPATH')) exit;

class Dev_Chouchou_Box {

    public function __construct() {
        add_action('woocommerce_product_options_general_product_data', [$this, 'render_admin_fields']);
        add_action('woocommerce_admin_process_product_object', [$this, 'save_admin_fields']);
        // add_action('woocommerce_before_add_to_cart_button', [$this, 'render_frontend_selector']);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 2);
        add_filter('woocommerce_get_item_data', [$this, 'show_cart_item_data'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_order_item_meta'], 10, 3);
    }

    // Normalize boolean-ish meta values coming from imports (yes/1/true/on)
    private function dev_is_enabled($raw) {
        if (is_bool($raw)) return $raw;
        $value = strtolower(trim((string) $raw));
        return in_array($value, ['yes', '1', 'true', 'on', 'enabled'], true);
    }

    // Decode flavors meta saved as JSON string, possibly HTML-encoded or serialized
    public function dev_decode_flavors($raw) {
        if (empty($raw)) return [];

        // Unserialize if needed
        if (is_string($raw)) {
            $maybe_unserialized = maybe_unserialize($raw);
            if ($maybe_unserialized !== $raw) {
                if (is_array($maybe_unserialized)) return $maybe_unserialized;
                $raw = $maybe_unserialized;
            }
        }

        if (is_array($raw)) return $raw;

        // Now treat as string JSON possibly encoded/escaped
        $string = (string) $raw;
        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        $string = wp_unslash($string);

        $decoded = json_decode($string, true);
        if (is_array($decoded)) return $decoded;

        // Retry with stripslashes in case of double-escaping
        $decoded = json_decode(stripslashes($string), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * ===============================
     * ADMIN PANEL FIELDS
     * ===============================
     */
    public function render_admin_fields() {
        global $post;
        $product = wc_get_product($post->ID);

        $enabled = $product->get_meta('_dev_enable_chouchou_box');
        $max     = $product->get_meta('_dev_max_flavors_allowed');
        $data    = $product->get_meta('_dev_chouchou_flavors_data');
        // Ensure the hidden field always contains valid JSON for the admin UI
        $data_array = $this->dev_decode_flavors($data);
        $data_json  = ! empty($data_array) ? wp_json_encode($data_array) : '';
        ?>
        <div class="options_group">
            <?php
            woocommerce_wp_checkbox([
                'id'          => '_dev_enable_chouchou_box',
                'label'       => __('Enable Chouchou Box Selection', 'dev'),
                'description' => __('Allow customers to choose Chouchous for this product.', 'dev'),
                'value'       => $enabled,
            ]);

            woocommerce_wp_text_input([
                'id'                => '_dev_max_flavors_allowed',
                'label'             => __('Max Total Quantity', 'dev'),
                'type'              => 'number',
                'custom_attributes' => ['min' => '1'],
                'value'             => $max ?: '',
            ]);
            ?>

            <div id="dev_flavor_wrapper" style="margin-top:15px;">
                <h4 style="margin-bottom:5px;"><?php esc_html_e('Flavor Options', 'dev'); ?></h4>
                <small><?php esc_html_e('Each flavor has a title, subtitle, image, and optional extra price.', 'dev'); ?></small>
                <div id="dev_flavor_container" style="margin-top:10px;"></div>
                <button type="button" class="button dev_add_flavor">+ Add Flavor</button>
            </div>

            <input type="hidden" id="_dev_chouchou_flavors_data" name="_dev_chouchou_flavors_data"
                value="<?php echo esc_attr($data_json); ?>">
        </div>

        <style>
            .dev_flavor-box {
                border: 1px solid #ddd;
                padding: 8px;
                margin-bottom: 8px;
                border-radius: 6px;
                background: #fafafa;
            }

            div#dev_flavor_container label {
                float: unset !important;
                margin: unset;
            }
            .dev_flavor-box header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                cursor: pointer;
            }
            .dev_flavor-fields { display: none; margin-top: 6px; }
            .dev_flavor-box.active .dev_flavor-fields { display: block; }
            .dev_flavor-box img { width: 70px; border-radius: 4px; margin-top: 4px; }
        </style>

        <script>
        jQuery(function($){
            const container = $("#dev_flavor_container");
            const hidden = $("#_dev_chouchou_flavors_data");
            let data = [];
            try { data = JSON.parse(hidden.val()) || []; } catch(e) { data = []; }

            function render(){
                container.empty();
                data.forEach((flavor,i)=>{
                    container.append(`
                        <div class="dev_flavor-box">
                            <header>
                                <strong>${flavor.title || 'Flavor '+(i+1)}</strong>
                                <span class="dashicons dashicons-trash dev_remove_flavor" style="cursor:pointer;"></span>
                            </header>
                            <div class="dev_flavor-fields">
                                <p><label>Title<br><input type="text" class="dev_flavor_title" value="${flavor.title||''}" style="width:100%;"></label></p>
                                <p><label>Subtitle<br><input type="text" class="dev_flavor_subtitle" value="${flavor.subtitle||''}" style="width:100%;"></label></p>
                                <p style="display: flex; flex-direction: column;">
                                    <label>Preset Quantity<br>
                                        <input type="number" class="dev_flavor_preset" value="${flavor.preset || 0}" min="0" style="width:100%;" placeholder="e.g. 1 for mandatory">
                                    </label>
                                    <small>If greater than 0, this quantity will be preselected and mandatory.</small>
                                </p>

                                <p>
                                    <label>Extra Price ($)<br>
                                        <input type="number" class="dev_flavor_price" value="${flavor.price || 0}" min="0" step="0.01" style="width:100%;" placeholder="e.g. 1.50">
                                    </label>
                                </p>

                                <p>
                                    <label>Image<br>
                                        <input type="text" class="dev_flavor_image" value="${flavor.image||''}" style="width:80%;" placeholder="Image URL">
                                        <button type="button" class="button dev_upload_image">Upload</button>
                                    </label>
                                    ${flavor.image ? `<img src="${flavor.image}" alt="" />` : ''}
                                </p>
                            </div>
                        </div>
                    `);
                });
            }

            render();   

            $(document).on("input change", ".dev_flavor_preset", function(){
                validatePresets();
                save();
            });

            function validatePresets() {
                const maxAllowed = parseInt($("#_dev_max_flavors_allowed").val()) || 0;
                let totalPresets = 0;

                container.find(".dev_flavor_preset").each(function() {
                    totalPresets += parseInt($(this).val() || 0);
                });

                if (maxAllowed && totalPresets > maxAllowed) {
                    alert(
                        "⚠️ The total preset quantity (" + totalPresets +
                        ") exceeds the Max Total Quantity (" + maxAllowed + ").\n\n" +
                        "Please increase the Max Total Quantity or reduce preset values."
                    );
                    return false;
                }
                return true;
            }

            // toggle fields
            $(document).on("click", ".dev_flavor-box header", function(){
                $(this).parent().toggleClass("active");
            });

            // Add
            $(document).on("click", ".dev_add_flavor", function(){
                data.push({title:"", subtitle:"", image:"", preset:0, price:0});
                render();
                save();
            });

            // Remove
            $(document).on("click", ".dev_remove_flavor", function(){
                const index = $(this).closest(".dev_flavor-box").index();
                data.splice(index,1);
                render();
                save();
            });

            // Upload
            $(document).on("click", ".dev_upload_image", function(e){
                e.preventDefault();
                const button = $(this);
                const frame = wp.media({title:"Select Chouchou Image", button:{text:"Use this image"}, multiple:false});
                frame.on("select", function(){
                    const attachment = frame.state().get("selection").first().toJSON();
                    const box = button.closest(".dev_flavor-box");
                    box.find(".dev_flavor_image").val(attachment.url);
                    box.find("img").remove();
                    box.find("label").after(`<img src="${attachment.url}" />`);
                    save();
                });
                frame.open();
            });

            // Save on input change
            $(document).on("input change", ".dev_flavor_title, .dev_flavor_subtitle, .dev_flavor_image, .dev_flavor_price", save);

            function save(){
                data = [];
                container.find(".dev_flavor-box").each(function(){
                    data.push({
                        title: $(this).find(".dev_flavor_title").val(),
                        subtitle: $(this).find(".dev_flavor_subtitle").val(),
                        image: $(this).find(".dev_flavor_image").val(),
                        preset: parseInt($(this).find(".dev_flavor_preset").val()) || 0,
                        price: parseFloat($(this).find(".dev_flavor_price").val()) || 0
                    });
                });
                hidden.val(JSON.stringify(data));
            }

            // Always save on product save
            $(document).on("submit", "#post", save);
        });
        </script>
        <?php
    }

    public function save_admin_fields($product) {
        $product->update_meta_data('_dev_enable_chouchou_box',
            isset($_POST['_dev_enable_chouchou_box']) ? 'yes' : 'no'
        );
        $product->update_meta_data('_dev_max_flavors_allowed',
            isset($_POST['_dev_max_flavors_allowed']) ? intval($_POST['_dev_max_flavors_allowed']) : ''
        );
        if (isset($_POST['_dev_chouchou_flavors_data'])) {
            $decoded = json_decode(wp_unslash($_POST['_dev_chouchou_flavors_data']), true);
            if (is_array($decoded)) {
                $to_store = wp_json_encode($decoded, JSON_UNESCAPED_UNICODE);
                $product->update_meta_data('_dev_chouchou_flavors_data', $to_store);
            } else {
                $product->update_meta_data('_dev_chouchou_flavors_data', '');
            }
        }
    }


    /**
     * ===============================
     * FRONTEND DISPLAY
     * ===============================
     */
    public function render_frontend_selector() {
        global $product;
        if (! $this->dev_is_enabled($product->get_meta('_dev_enable_chouchou_box'))) return;

        $flavors_raw = $product->get_meta('_dev_chouchou_flavors_data');
        $flavors = $this->dev_decode_flavors($flavors_raw);
        if (empty($flavors)) return;

        $max = intval($product->get_meta('_dev_max_flavors_allowed')) ?: 1;

        echo '<div class="dev_chouchou_flavor_section">';
        echo '<p><strong>' . sprintf(__('Select up to %d total items:', 'dev'), $max) . '</strong></p>';
        echo '<div class="dev_chouchou_grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;">';

        foreach ($flavors as $flavor) {
            $title = esc_html($flavor['title']);
            $subtitle = esc_html($flavor['subtitle']);
            $image = esc_url($flavor['image']);
            echo '<div class="dev_flavor_item" style="border:1px solid #eee;padding:10px;text-align:center;border-radius:6px;">';
            if ($image) echo '<img src="' . $image . '" alt="' . $title . '" style="width:100%;max-width:100px;height:auto;margin-bottom:8px;border-radius:4px;">';
            echo '<div><strong>' . $title . '</strong><br><small>' . $subtitle . '</small></div>';
            echo '<input type="number" name="dev_chouchou_flavors[' . esc_attr($title) . ']" value="0" min="0" style="margin-top:5px;width:60px;">';
            echo '</div>';
        }
        echo '</div></div>';

        ?>
        <script>
        document.addEventListener("DOMContentLoaded", function(){
            const inputs = document.querySelectorAll(".dev_chouchou_flavor_section input[type=number]");
            const max = <?php echo intval($max); ?>;
            inputs.forEach(i=>{
                i.addEventListener("change", function(){
                    let total = 0;
                    inputs.forEach(x=> total += parseInt(x.value)||0);
                    if(total > max){
                        alert("You can only select up to " + max + " total items.");
                        this.value = 0;
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * ===============================
     * CART + ORDER METADATA
     * ===============================
     */
    public function add_cart_item_data($cart_item_data, $product_id) {
        if (!empty($_POST['dev_chouchou_flavors'])) {
            $cart_item_data['dev_chouchou_flavors'] = array_filter(array_map('intval', $_POST['dev_chouchou_flavors']));
        }
        return $cart_item_data;
    }

    public function show_cart_item_data($item_data, $cart_item) {
        if (!empty($cart_item['dev_chouchou_flavors'])) {
            $flavors = [];
            foreach ($cart_item['dev_chouchou_flavors'] as $name => $qty) {
                if ($qty > 0) $flavors[] = $name . ' (' . $qty . ')';
            }
            $item_data[] = [
                'key'   => __('Selected Chouchous', 'dev'),
                'value' => implode(', ', $flavors)
            ];
        }
        return $item_data;
    }

    public function save_order_item_meta($item, $cart_item_key, $values) {
        if (!empty($values['dev_chouchou_flavors'])) {
            $flavors = [];
            foreach ($values['dev_chouchou_flavors'] as $name => $qty) {
                if ($qty > 0) $flavors[] = $name . ' (' . $qty . ')';
            }
            $item->add_meta_data(__('Selected Chouchous', 'dev'), implode(', ', $flavors));
        }
    }
}

new Dev_Chouchou_Box();
