<?php
namespace ECPW;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!class_exists('ECPW\\Dev_Category_Products_Widget')) {
	class Dev_Category_Products_Widget extends Widget_Base {

		public function get_name() {
			return 'ecpw_category_products';
		}

		public function get_title() {
			return __('Dev Category Beverages', 'elementor-category-products-widget');
		}

		public function get_icon() {
			return 'eicon-products';
		}

		public function get_categories() {
			return ['general'];
		}

		protected function _register_controls() {
			$this->start_controls_section(
				'section_content',
				[
					'label' => __('Content', 'elementor-category-products-widget'),
				]
			);

			$this->add_control(
				'product_category',
				[
					'label' => __('Product Category Slug', 'elementor-category-products-widget'),
					'type' => Controls_Manager::TEXT,
					'placeholder' => __('e.g. latte', 'elementor-category-products-widget'),
				]
			);

			$this->add_control(
				'posts_per_page',
				[
					'label' => __('Number of Products', 'elementor-category-products-widget'),
					'type' => Controls_Manager::NUMBER,
					'default' => 10,
				]
			);

			$this->end_controls_section();
		}
        public function render() {
            if (!function_exists('wc_get_products')) {
                echo '<div class="ecpw-error">WooCommerce is required.</div>';
                return;
            }
        
            $settings = $this->get_settings_for_display();
            $category_slug = sanitize_text_field($settings['product_category']);
            // If not set in widget and on category archive, get it from context
            if (empty($category_slug) && function_exists('is_product_category') && is_product_category()) {
                $current_cat = get_queried_object();
                if (!empty($current_cat) && !empty($current_cat->slug)) {
                    $category_slug = $current_cat->slug;
                }
            }
            $limit = intval($settings['posts_per_page']);
        
            $args = [
                'limit' => $limit,
                'status' => 'publish',
                'category' => [$category_slug],
            ];
        
            $products = wc_get_products($args);
        
            if (empty($products)) {
                echo '<div class="ecpw-empty">No products found in this category.</div>';
                return;
            }
        
            echo '<div class="ecpw-products-list">';
        
            foreach ($products as $product) {
                $image = $product->get_image('woocommerce_single');
                $title = $product->get_name();
                $desc = $product->get_short_description();
                $price_html = $product->get_price_html();
                $price_number = $product->get_price();
        
                echo '<div class="ecpw-product">';
                echo '<div class="ecpw-thumb">' . $image . '</div>';
                echo '<div class="ecpw-details">';
                echo '<div class="ecpw-header">';
                echo '<h3 class="ecpw-title">' . esc_html($title) . '</h3>';
                echo '<div class="ecpw-price-tag">' . wc_price($price_number) . '</div>';
                echo '</div>';
                echo '<div class="ecpw-desc">' . wp_kses_post($desc) . '</div>';
                if ($product->is_type('variable')) {
                    $available_variations = $product->get_available_variations();
                    echo '<script type="application/json" class="ecpw-variation-data">'
                        . wp_json_encode($available_variations)
                        . '</script>';
                }

                // ✅ Attributes (post correct variation fields so add-to-cart works)
                $attributes = $product->get_attributes();
                if (!empty($attributes)) {
                    echo '<div class="ecpw-attributes">';
                    foreach ($attributes as $attr_name => $attr_obj) {
                        $label = wc_attribute_label($attr_name);
                        $options = $attr_obj->get_options();
                        $field_name = 'attribute_' . sanitize_title($attr_obj->get_name());

                        echo '<div class="ecpw-attr-group">';
                        echo '<div class="ecpw-attr-label">' . esc_html($label) . '</div>';
                        echo '<div class="ecpw-attr-options">';

                        foreach ($options as $option) {
                            if ($attr_obj->is_taxonomy()) {
                                $term = get_term($option);
                                if ($term && !is_wp_error($term)) {
                                    $opt_label = $term->name;
                                    $opt_value = $term->slug; // Woo expects term slug
                                } else {
                                    continue;
                                }
                            } else {
                                $opt_label = $option;
                                $opt_value = $option; // custom attribute value
                            }

                            echo '<label class="ecpw-attr-option">';
                            echo '<input type="radio" required name="' . esc_attr($field_name) . '" value="' . esc_attr($opt_value) . '">';
                            echo '<span>' . esc_html($opt_label) . '</span>';
                            echo '</label>';
                        }

                        echo '</div></div>';
                    }
                    echo '</div>';
                }
        
                // ✅ Quantity and Add to Cart (functional - handles simple and variable)
                $product_id = $product->get_id();
                $form_action = esc_url( admin_url('admin-ajax.php') );
                $qty_input_id = 'ecpw-qty-' . $product_id . '-' . uniqid();

                echo '<form class="ecpw-footer ecpw-add-to-cart" action="' . $form_action . '" method="post" data-product-id="' . esc_attr($product_id) . '">';
                echo '<div class="ecpw-qty">';
                echo '<button type="button" class="qty-minus" aria-label="Moins">−</button>';
                echo '<input id="' . esc_attr($qty_input_id) . '" name="quantity" type="number" value="1" min="1">';
                echo '<button type="button" class="qty-plus" aria-label="Plus">+</button>';
                echo '</div>';
                echo '<input type="hidden" name="action" value="ecpw_add_to_cart">';
                echo '<input type="hidden" name="product_id" value="' . esc_attr($product_id) . '">';
                echo '<button type="submit" class="ecpw-add-btn" data-adding="' . esc_attr__('Adding…', 'elementor-category-products-widget') . '" data-added="' . esc_attr__('Added ✓', 'elementor-category-products-widget') . '">' . esc_html__('Ajouter au panier', 'elementor-category-products-widget') . '</button>';
                echo '</form>'; // footer form
        
                echo '</div>'; // details
                echo '</div>'; // product
            }
        
            echo '</div>';

            // Enqueue frontend JS for AJAX add-to-cart
            if (!wp_script_is('jquery')) {
                wp_enqueue_script('jquery');
            }
            wp_enqueue_script(
                'ecpw-category-add-to-cart',
                plugins_url('../assets/js/ecpw-category-add-to-cart.js', __FILE__),
                array('jquery'),
                '1.0',
                true
            );
        }
        
        
	}
}
