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

                // --- Check product attributes ---
                $attributes = $product->get_attributes();

                if (!empty($attributes)) {
                    foreach ($attributes as $attr_key => $attr_obj) {

                        // Get attribute name
                        $attr_name = is_object($attr_obj) ? $attr_obj->get_name() : $attr_key;

                        // Handle taxonomy attributes (like pa_color)
                        if (method_exists($attr_obj, 'is_taxonomy') && $attr_obj->is_taxonomy()) {
                            $taxonomy = $attr_obj->get_name();
                            $terms = wc_get_product_terms($product->get_id(), $taxonomy, ['fields' => 'names']);
                            foreach ($terms as $term_name) {
                                $ingredients_list[] = sprintf('%s - %s', esc_html($attr_name), esc_html($term_name));
                            }
                        } 
                        // Handle custom attributes
                        else {
                            $options = (method_exists($attr_obj, 'get_options')) ? $attr_obj->get_options() : [];
                            foreach ($options as $opt) {
                                $ingredients_list[] = sprintf('%s - %s', esc_html($attr_name), esc_html($opt));
                            }
                        }
                    }
                }

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