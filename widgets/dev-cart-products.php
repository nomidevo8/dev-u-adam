<?php

namespace ECPW;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!class_exists('ECPW\\Cart_Products_Widget')) {
    class Cart_Products_Widget extends \Elementor\Widget_Base
    {

        public function get_name()
        {
            return 'ecpw_cart_products';
        }

        public function get_title()
        {
            return __('Cart Products Styled', 'elementor-cart-products-widget');
        }

        public function get_icon()
        {
            return 'eicon-product-categories';
        }

        public function get_categories()
        {
            return ['general'];
        }

        public function get_script_depends()
        {
            return [];
        }

        protected function _register_controls()
        {
            $this->start_controls_section(
                'section_content',
                ['label' => __('Content', 'elementor-cart-products-widget')]
            );

            $this->add_control(
                'show_if_empty',
                [
                    'label' => __('Show when cart is empty', 'elementor-cart-products-widget'),
                    'type' => Controls_Manager::SWITCHER,
                    'label_on' => 'Show',
                    'label_off' => 'Hide',
                    'return_value' => 'yes',
                    'default' => '',
                ]
            );

            $this->end_controls_section();
        }

        public function render()
        {
            if (!function_exists('WC')) {
                echo '<div class="ecpw-no-wc">' . esc_html__('WooCommerce is required.', 'elementor-cart-products-widget') . '</div>';
                return;
            }

            $settings = $this->get_settings_for_display();
            $cart = \WC()->cart->get_cart();

            if (empty($cart)) {
                if ($settings['show_if_empty'] === 'yes') {
                    echo '<div class="ecpw-empty">' . esc_html__('Your cart is empty.', 'elementor-cart-products-widget') . '</div>';
                } else {
                    echo '<div class="ecpw-empty-hidden" style="display:none"></div>';
                }
                return;
            }

            echo '<div class="ecpw-list">';

            foreach ($cart as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $quantity = intval($cart_item['quantity']);
                $price_html = $product->get_price_html();
                $image = $product->get_image('woocommerce_thumbnail');
                $name = $product->get_name();

                // Build ingredients list
                $ingredients_list = [];

                // --- 1️⃣ Check custom meta field (if exists) ---
                $meta_ing = get_post_meta($product->get_id(), 'ingredients', true);
                if ($meta_ing) {
                    $pairs = preg_split('/\|/', $meta_ing);
                    foreach ($pairs as $p) {
                        $p = trim($p);
                        if (strpos($p, '=') !== false) {
                            list($name_k, $qty_k) = array_map('trim', explode('=', $p, 2));
                            $ingredients_list[] = sprintf('%s - %s', esc_html($qty_k), esc_html($name_k));
                        } else {
                            $ingredients_list[] = esc_html($p);
                        }
                    }
                }

                // --- 2️⃣ Check product attributes ---
                if (empty($ingredients_list)) {
                    $attributes = $product->get_attributes();

                    if (!empty($attributes)) {
                        // Optional: remove debug print
                        // echo "<pre>" . print_r($attributes, true) . "</pre>";

                        // Optional mapping for your custom attribute names
                        $name_map = [
                            'orirign' => 'Originel',
                            'pirec'   => 'Pêche',
                            'enrobage_chocolat' => 'Enrobage Chocolat',
                        ];

                        foreach ($attributes as $attr_key => $attr_obj) {
                            // Check if this looks like an ingredient
                            if (
                                stripos($attr_key, 'ingredient') !== false ||
                                stripos($attr_key, 'orirign') !== false ||
                                stripos($attr_key, 'pirec') !== false ||
                                (is_object($attr_obj) && stripos($attr_obj->get_name(), 'ingredient') !== false)
                            ) {
                                // taxonomy attribute (like pa_ingredients)
                                if (method_exists($attr_obj, 'is_taxonomy') && $attr_obj->is_taxonomy()) {
                                    $taxonomy = $attr_obj->get_name();
                                    $terms = wc_get_product_terms($product->get_id(), $taxonomy, ['fields' => 'names']);
                                    foreach ($terms as $t) {
                                        $ingredients_list[] = '1 - ' . esc_html($t);
                                    }
                                } else {
                                    // custom attribute
                                    $opts = (method_exists($attr_obj, 'get_options')) ? $attr_obj->get_options() : [];
                                    foreach ($opts as $opt) {
                                        $label = isset($name_map[$attr_key]) ? $name_map[$attr_key] : $attr_key;
                                        // If option is numeric or short, assume it's quantity
                                        if (is_numeric($opt) || strlen($opt) <= 2) {
                                            $ingredients_list[] = sprintf('%s - %s', esc_html($opt), esc_html($label));
                                        } else {
                                            // Otherwise parse Name=Value pattern if possible
                                            if (strpos($opt, '=') !== false) {
                                                list($name_k, $qty_k) = array_map('trim', explode('=', $opt, 2));
                                                $ingredients_list[] = sprintf('%s - %s', esc_html($qty_k), esc_html($name_k));
                                            } else {
                                                $ingredients_list[] = '1 - ' . esc_html($opt);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // --- 3️⃣ Fallback: variation meta ---
                if (empty($ingredients_list) && !empty($cart_item['variation'])) {
                    foreach ($cart_item['variation'] as $k => $v) {
                        if (stripos($k, 'ingredient') !== false || stripos($v, 'ingredient') !== false) {
                            $ingredients_list[] = esc_html($v);
                        }
                    }
                }

                // --- 🔹 Render item ---
                ?>
                <div class="ecpw-item">
                    <div class="ecpw-left">
                        <div class="ecpw-thumb"><?php echo $image; ?></div>
                        <div class="ecpw-main">
                            <h3 class="ecpw-title"><?php echo esc_html($name); ?></h3>

                            <?php if (!empty($ingredients_list)): ?>
                                <ul class="ecpw-ingredients">
                                    <?php foreach ($ingredients_list as $ing): ?>
                                        <li><?php echo wp_kses_post($ing); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>

                            <div class="ecpw-price"><?php echo wp_kses_post($price_html); ?></div>
                        </div>
                    </div>

                    <div class="ecpw-right">
                        <div class="ecpw-qty">
                            <?php echo esc_html__('Quantité', 'elementor-cart-products-widget') . ' : ' . intval($quantity); ?>
                        </div>
                    </div>
                </div>
                <?php
            }

            echo '</div>'; // ecpw-list
        }

    }
}