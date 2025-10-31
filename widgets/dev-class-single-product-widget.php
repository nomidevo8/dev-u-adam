<?php
namespace ECPW;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!class_exists('ECPW\\Dev_Single_Product_Widget')) {
	class Dev_Single_Product_Widget extends Widget_Base
	{

		public function get_name()
		{
			return 'ecpw_single_product';
		}
		public function get_title()
		{
			return __('Dev Single Product', 'elementor-single-product-widget');
		}
		public function get_icon()
		{
			return 'eicon-product-info';
		}
		public function get_categories()
		{
			return ['general'];
		}

		protected function _register_controls()
		{
			$this->start_controls_section('section_content', ['label' => __('Content', 'elementor-single-product-widget')]);
			$this->add_control('product_id', [
				'label' => __('Product ID (optional)', 'elementor-single-product-widget'),
				'type' => Controls_Manager::NUMBER,
				'description' => __('Leave empty to use current single product context', 'elementor-single-product-widget'),
			]);
			$this->end_controls_section();
		}

		public function render()
		{
			if (!function_exists('wc_get_product')) {
				echo '<div class="ecpw-error">WooCommerce is required.</div>';
				return;
			}

			$settings = $this->get_settings_for_display();
			$product_id = !empty($settings['product_id']) ? intval($settings['product_id']) : get_the_ID();

			if (!$product_id) {
				echo '<div class="ecpw-error">No product found in context.</div>';
				return;
			}

			$product = wc_get_product($product_id);
			if (!$product) {
				echo '<div class="ecpw-error">Invalid product.</div>';
				return;
			}

			$is_flavor_box = $product->get_meta('_dev_enable_chouchou_box') === 'yes';
			$image = wp_get_attachment_image_src($product->get_image_id(), 'large')[0] ?? '';
			$price_html = $product->get_price_html();
			$base_price = floatval($product->get_price());
			$desc = $product->get_short_description();
			$max_flavors = intval($product->get_meta('_dev_max_flavors_allowed')) ?: 1;
	
			echo '<div class="ecpw-flavor-products">';
			echo '<div class="ecpw-product-box ecpw-product" 
				data-max-flavors="' . esc_attr($max_flavors) . '" 
				data-product-id="' . esc_attr($product->get_id()) . '" 
				data-base-price="' . esc_attr($base_price) . '">';

			echo '<div class="ecpw-product-image">';
			if ($image)
				echo '<img src="' . esc_url($image) . '" alt="' . esc_attr($product->get_name()) . '">';
			echo '</div>';
			if ($product->is_type('variable')) {
				$available_variations = $product->get_available_variations();
				echo '<script type="application/json" class="ecpw-variation-data">'
					. wp_json_encode($available_variations)
					. '</script>';
			}
			echo '<div class="ecpw-product-content">';
			echo '<div class="ecpw-product-header">';
			echo '<h2 class="ecpw-product-title">' . esc_html($product->get_name()) . '</h2>';
			if ($price_html)
				echo '<div class="ecpw-product-price ecpw-price-tag">' . wp_kses_post($price_html) . '</div>';
			echo '</div>';

			if ($desc)
				echo '<div class="ecpw-product-desc">' . wp_kses_post($desc) . '</div>';

			// 🧩 If Chouchou Box: show flavors
			if ($is_flavor_box) {
				$flavors_raw = $product->get_meta('_dev_chouchou_flavors_data');
				$box = new \Dev_Chouchou_Box();
				$flavors = $box->dev_decode_flavors($flavors_raw);
				if (!empty($flavors)) {
			
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
				}

				// Global add-to-cart
				echo '<div class="ecpw-addcart-row">';
				echo '<div class="ecpw-global-qty">';
				echo '<button class="qty-minus">−</button>';
				echo '<input type="number" value="1" min="1">';
				echo '<button class="qty-plus">+</button>';
				echo '</div>';
				echo '<button class="ecpw-addcart-btn" data-product-id="' . esc_attr($product->get_id()) . '">Ajouter au panier</button>';
				echo '</div>';
			}

			// 🧩 Else handle variations / simple product (match category structure)
			else {
				
                // ✅ Attributes (post correct variation fields so add-to-cart works)
                $attributes = $product->get_attributes();
                if (!empty($attributes)) {
                    echo '<div class="ecpw-attributes">';
                    foreach ($attributes as $attr_name => $attr_obj) {
						$label = wc_attribute_label($attr_obj->get_name());
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
				echo '<div class="ecpw-addcart-row">';

                echo '<div class="ecpw-qty ecpw-global-qty">';
                echo '<button type="button" class="qty-minus" aria-label="Moins">−</button>';
                echo '<input id="' . esc_attr($qty_input_id) . '" name="quantity" type="number" value="1" min="1">';
                echo '<button type="button" class="qty-plus" aria-label="Plus">+</button>';
                echo '</div>';
                echo '<input type="hidden" name="action" value="ecpw_add_to_cart">';
                echo '<input type="hidden" name="product_id" value="' . esc_attr($product_id) . '">';
                echo '<button type="submit" class="ecpw-add-btn ecpw-addcart-btn" data-adding="' . esc_attr__('Adding…', 'elementor-category-products-widget') . '" data-added="' . esc_attr__('Added ✓', 'elementor-category-products-widget') . '">' . esc_html__('Ajouter au panier', 'elementor-category-products-widget') . '</button>';
				echo '</div>'; // .ecpw-addcart-row
                echo '</form>'; // footer form

			}


			echo '</div>'; // .ecpw-product-content
			echo '</div>'; // .ecpw-product-box
			echo '</div>'; // .ecpw-flavor-products

			// ✅ Enqueue shared CSS + JS
			?>

			<?php
			if ($is_flavor_box) {
				wp_enqueue_style(
					'ecpw-flavor-products',
					plugins_url('../assets/css/ecpw-flavor-product.css', __FILE__),
					array(),
					null
				);
				wp_enqueue_script(
					'ecpw-flavor-add-to-cart',
					plugins_url('../assets/js/ecpw-flavor-add-to-cart.js', __FILE__),
					array('jquery'),
					'1.0.0',
					true
				);
				wp_localize_script('ecpw-flavor-add-to-cart', 'ecpwFlavorData', array(
					'ajax_url' => admin_url('admin-ajax.php'),
				));
			} else {
				wp_enqueue_style(
					'ecpw-flavor-products',
					plugins_url('../assets/css/ecpw-flavor-product.css', __FILE__),
					array(),
					null
				);
				wp_enqueue_script(
					'ecpw-flavor-add-to-cart',
					plugins_url('../assets/js/ecpw-category-add-to-cart.js', __FILE__),
					array('jquery'),
					null,
					true
				);
				wp_localize_script('ecpw-flavor-add-to-cart', 'ecpwFlavorData', array(
					'ajax_url' => admin_url('admin-ajax.php'),
				));
			}
		}
	}
}
