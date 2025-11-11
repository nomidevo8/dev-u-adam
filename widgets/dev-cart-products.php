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
                $price_html = wc_price($cart_item['line_total']);
                $image = $product->get_image('woocommerce_thumbnail');
                $name = $product->get_name();

                // 🧩 Collect ingredients or selected options
                $details_list = [];

                // 1️⃣ If this is a Chouchou Box
                if (!empty($cart_item['dev_chouchou_flavors']) && is_array($cart_item['dev_chouchou_flavors'])) {
                    foreach ($cart_item['dev_chouchou_flavors'] as $flavor_name => $flavor_qty) {
                        $flavor_qty = intval($flavor_qty);
                        if ($flavor_qty > 0) {
                            $details_list[] = sprintf('%d × %s', $flavor_qty, esc_html($flavor_name));
                        }
                    }
                }

                // 2️⃣ Else, if this is a normal variable product
               elseif (!empty($cart_item['flavors']) && is_array($cart_item['flavors'])) {
                    foreach ($cart_item['flavors'] as $attr_key => $attr_val) {
                        // Extract attribute name correctly
                        $attr_name = str_replace('attribute_', '', $attr_key);

                        // Add prefix if it's a taxonomy attribute (starts with "pa_")
                        if (taxonomy_exists('pa_' . $attr_name)) {
                            $label = wc_attribute_label('pa_' . $attr_name);
                        } else {
                            // Fallback to human-readable label
                            $label = ucwords(str_replace('-', ' ', $attr_name));
                        }

                        $details_list[] = sprintf('%s: %s', esc_html($label), esc_html($attr_val));
                    }
                }


                ?>
                <div class="ecpw-item">
                    <div class="ecpw-left">
                        <div class="ecpw-thumb"><?php echo $image; ?></div>
                        <div class="ecpw-main">
                            <h3 class="ecpw-title"><?php echo esc_html($name); ?></h3>

                            <?php if (!empty($details_list)): ?>
                                <ul class="ecpw-ingredients">
                                    <?php foreach ($details_list as $item): ?>
                                        <li><?php echo wp_kses_post($item); ?></li>
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