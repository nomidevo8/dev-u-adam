<?php
namespace ECPW;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WP_Query;

if (!class_exists('ECPW\Dev_Flavor_Products_Widget')) {
    class Dev_Flavor_Products_Widget extends Widget_Base {

        public function get_name() {
            return 'ecpw_flavor_products';
        }

        public function get_title() {
            return __('Dev Flavor Products', 'elementor-flavor-products-widget');
        }

        public function get_icon() {
            return 'eicon-products';
        }

        public function get_categories() {
            return ['general'];
        }

        protected function _register_controls() {
            $this->start_controls_section('section_content', [
                'label' => __('Content', 'elementor-flavor-products-widget'),
            ]);

            $this->add_control('product_category', [
                'label' => __('Product Category Slug', 'elementor-flavor-products-widget'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('e.g. latte', 'elementor-flavor-products-widget'),
            ]);

            $this->end_controls_section();
        }

        public function render() {
            if (!class_exists('WooCommerce')) {
                echo '<div class="ecpw-error">WooCommerce is required.</div>';
                return;
            }

            $settings = $this->get_settings_for_display();
            $category_slug = sanitize_text_field($settings['product_category']);
            if (!$category_slug) {
                echo '<div class="ecpw-error">Please provide a category slug.</div>';
                return;
            }

            $args = [
                'post_type' => 'product',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'tax_query' => [[
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $category_slug,
                ]],
                'meta_query' => [[
                    'key' => '_dev_enable_chouchou_box',
                    'value' => 'yes',
                ]],
            ];

            $query = new \WP_Query($args);
            if (!$query->have_posts()) {
                echo '<div class="ecpw-empty">No products found.</div>';
                return;
            }

            echo '<div class="ecpw-flavor-products">';
            while ($query->have_posts()) {
                $query->the_post();
                $product = \wc_get_product(get_the_ID());
                if (!$product) continue;

                $price_html = $product->get_price_html();
                $base_price = floatval($product->get_price());
                $image = wp_get_attachment_image_src($product->get_image_id(), 'large')[0] ?? '';
                $flavors_raw = $product->get_meta('_dev_chouchou_flavors_data');
                $box = new \Dev_Chouchou_Box();
                $flavors = $box->dev_decode_flavors($flavors_raw);
                $max_flavors = intval($product->get_meta('_dev_max_flavors_allowed')) ?: 1;
                if (empty($flavors)) continue;

                echo '<div class="ecpw-product-box" 
                        data-max-flavors="' . esc_attr($max_flavors) . '" 
                        data-product-id="' . esc_attr($product->get_id()) . '" 
                        data-base-price="' . esc_attr($base_price) . '">';
                    echo '<div class="ecpw-product-image">';
                        if ($image) echo '<img src="'.esc_url($image).'" alt="'.esc_attr($product->get_name()).'" />';
                    echo '</div>';

                    echo '<div class="ecpw-product-content">';
                        echo '<div class="ecpw-product-header">';
                            echo '<h2 class="ecpw-product-title">
                                    <a href="' . esc_url(get_permalink($product->get_id())) . '">
                                        ' . esc_html($product->get_name()) . '
                                    </a>
                                </h2>';
                            if ($price_html) echo '<div class="ecpw-product-price">' . $price_html . '</div>';
                        echo '</div>';


                        echo '<div class="ecpw-product-desc">';
                        echo wp_kses_post( $product->get_short_description() );
                        echo '</div>';
                        

                        echo '<div class="ecpw-flavor-list">';
                        $flavor_count = 0;
                        $total_flavors = count($flavors);
                        foreach ($flavors as $flavor) {
                            $flavor_count++;
                            $hidden_class = $flavor_count > 3 ? ' style="display:none;"' : '';

                            echo '<div class="ecpw-flavor-column"'.$hidden_class.'>';
                                $title = esc_html($flavor['title'] ?? '');
                                $subtitle = esc_html($flavor['subtitle'] ?? '');
                                $img = esc_url($flavor['image'] ?? '');
                                echo '<div class="ecpw-flavor-row">';
                                    if ($img) echo '<div class="ecpw-flavor-thumb"><img src="'.$img.'" alt="'.$title.'"></div>';
                                    echo '<div class="ecpw-flavor-info">';
                                        echo '<div class="ecpw-flavor-name">'.$title.'</div>';
                                        if ($subtitle) echo '<div class="ecpw-flavor-sub">'.$subtitle.'</div>';
                                    echo '</div>';
                                    $preset = isset($flavor['preset']) ? intval($flavor['preset']) : 0;
                                    $extra_price = isset($flavor['price']) ? floatval($flavor['price']) : 0;
                                    echo '<div class="ecpw-flavor-controls" data-preset="'.$preset.'" data-extra-price="'.$extra_price.'">';
                                        echo '<button type="button" class="qty-minus">−</button>';
                                        echo '<input type="number" value="'.esc_attr($preset).'" min="'.$preset.'">';
                                        echo '<button type="button" class="qty-plus">+</button>';
                                    echo '</div>';
                                echo '</div>';
                            echo '</div>';
                        }

                        if ($total_flavors > 3) {
                            echo '<button type="button" class="ecpw-load-more-flavors" style="margin-top:10px;">Load more</button>';
                        }
                        echo '<div class="ecpw-flavor-counter" style="margin-top:8px;font-size:0.9rem;color:#70352c;"></div>';
                        echo '</div>';

                        echo '<div class="ecpw-addcart-row">';
                            echo '<div class="ecpw-global-qty">';
                                echo '<button class="qty-minus">−</button>';
                                echo '<input type="number" value="1" min="1">';
                                echo '<button class="qty-plus">+</button>';
                            echo '</div>';
                            echo '<button class="ecpw-addcart-btn">Ajouter au panier</button>';
                        echo '</div>';

                    echo '</div>';
                echo '</div>';
            }
            echo '</div>';
            wp_reset_postdata();
            ?>

            <style>
            .ecpw-flavor-products{display:flex;flex-direction:column;gap:50px}
            .ecpw-product-box{max-width: 500px; display:flex;flex-wrap:wrap;align-items:stretch;background:#fdf3ee;border-radius:18px;padding:22px 28px;gap:28px;box-shadow:0 4px 25px rgba(160,90,70,.15);border:2px solid #d4a68a;}
            .ecpw-product-image{flex:1 1 420px;min-width:320px;border-radius:14px;overflow:hidden;}
            .ecpw-product-image img{width:100%;height:100%;object-fit:cover;border-radius:14px;}
            .ecpw-product-content{flex:1 1 420px;display:flex;flex-direction:column;justify-content:space-between;padding:10px;}
            .ecpw-product-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
            .ecpw-product-title a{font-family:serif;font-size:1.6rem;color:#70352c;margin:0;}
            .ecpw-product-price{background:#70352c;color:#fff;padding:5px 16px;border-radius:100px;font-weight:600;}
            .ecpw-product-desc p{color:#7b4d40;font-size:0.95rem;margin:6px 0 16px;}
            .ecpw-flavor-list{display:flex;flex-direction:column;gap:8px;}
            .ecpw-flavor-row{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;}
            .ecpw-flavor-thumb img{width:35px;height:35px;border-radius:50%;object-fit:cover;margin-right:8px;}
            .ecpw-flavor-info{flex:1;}
            .ecpw-flavor-name{font-weight:600;color:#70352c;font-size:1rem;}
            .ecpw-flavor-sub{font-size:0.9rem;color:#a47c6e;}
            .ecpw-flavor-controls{display:flex;align-items:center;gap:6px;}
            .ecpw-flavor-controls input{width:36px;text-align:center;border:1px solid #e4b7a1;border-radius:6px;padding:3px 0;font-size:0.9rem;}
            .ecpw-flavor-controls button{border:none;border-radius:6px;width:25px;height:25px;line-height:1;font-size:1rem;cursor:pointer;}
            .ecpw-addcart-row{display:flex;align-items:center;justify-content:space-between;margin-top:20px;}
            .ecpw-global-qty{display:flex;align-items:center;gap:8px;}
            .ecpw-global-qty input{width:45px;text-align:center;border:1px solid #e4b7a1;border-radius:6px;padding:5px;}
            .ecpw-global-qty button{border:none;border-radius:6px;width:25px;height:25px;font-size:1rem;cursor:pointer;}
            .ecpw-addcart-btn{background:#70352c;color:#fff;padding:10px 28px;border:none;border-radius:50px;font-size:1rem;font-weight:500;cursor:pointer;transition:background .2s;}
            .ecpw-addcart-btn:hover{background:#8b4d41;}
            @media(max-width:768px){
                .ecpw-product-box{flex-direction:column;}
                .ecpw-product-header{flex-direction:column;align-items:flex-start;gap:6px;}
            }
            	.ecpw-addcart-btn, .ecpw-load-more-flavors {
                    background: #70352c;
                    color: #fff;
                    padding: 10px 28px;
                    border: none;
                    border-radius: 50px;
                    font-size: 1rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background .2s;
                }

                .ecpw-addcart-btn:hover, .ecpw-load-more-flavors:hover {
                    background: #8b4d41;
                }
            </style>

            <?php
            // Enqueue our JS for flavor box add-to-cart
            wp_enqueue_script(
                'ecpw-flavor-add-to-cart',
                plugins_url('../assets/js/ecpw-flavor-add-to-cart.js', __FILE__),
                array('jquery'), null, true
            );
            wp_localize_script('ecpw-flavor-add-to-cart', 'ecpwFlavorData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
            ));
            ?>

            <?php
        }
    }
}
