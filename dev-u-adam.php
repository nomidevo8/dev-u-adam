<?php
/**
 * Plugin Name: Elementor Cart Products Styled Widget
 * Description: An Elementor widget that displays WooCommerce cart products with ingredients parsed from a product attribute ("ingredients") or from custom meta. Styled to match the provided design.
 * Version: 1.0.0
 * Author: SERVETECH
 * Text Domain: elementor-cart-products-widget
 */

if (!defined('ABSPATH')) {
	exit;
}

// Require WooCommerce and Elementor
add_action('plugins_loaded', function () {
	if (!class_exists('WooCommerce') || !defined('ELEMENTOR_PATH')) {
		return;
	}

	// Register widget
	add_action('elementor/widgets/register', function ($widgets_manager) {
		if (!class_exists('\ECPW\Cart_Products_Widget')) {
			// Define the widget class inline

			require_once __DIR__ . '/widgets/dev-cart-products.php';
			require_once __DIR__ . '/widgets/dev-category-products.php';
			require_once __DIR__ . '/widgets/dev-flavor-products.php';
			require_once __DIR__ . '/widgets/dev-class-single-product-widget.php';
			// require_once __DIR__ . '/shortcodes/dev-pickup-time.php';
			// Register widget instance
			$widgets_manager->register(new \ECPW\Cart_Products_Widget());
			$widgets_manager->register(new \ECPW\Dev_Category_Products_Widget());
			$widgets_manager->register(new \ECPW\Dev_Flavor_Products_Widget());
			$widgets_manager->register(new \ECPW\Dev_Single_Product_Widget());
		}
	});
	// Enqueue styles
	add_action('wp_enqueue_scripts', function () {
		wp_register_style('ecpw-styles', plugins_url('assets/css/ecpw-styles.css', __FILE__));
		wp_enqueue_style('ecpw-styles');
	});

	add_action('wp_head', function () {
		// Only print CSS when WooCommerce cart exists on front-end to reduce noise.
		if (!function_exists('WC') || !is_cart() && !is_checkout() && empty(\WC()->cart->get_cart())) {
			return;
		}

		// Print safe scoped CSS
		echo "\n<style id=\"ecpw-inline-styles\">\n.ecpw-list{display:flex;flex-direction:column;gap:18px;margin:0;padding:0}\n.ecpw-item{display:flex;justify-content:space-between;background:#fdefe9;border:1px solid #e7b9b3;border-radius:10px;padding:14px}\n.ecpw-left{display:flex;gap:12px;align-items:flex-start}
    .ecpw-thumb img{width:84px;height:84px;border-radius:8px;object-fit:cover}
    .ecpw-title a{margin:0 0 8px 0;font-size:15px;color:#7a2a2a}
    .ecpw-ingredients{list-style:none;margin:0;padding:0;color:#8b5858;font-size:13px}
    .ecpw-ingredients li{margin:3px 0}
    .ecpw-right{text-align:right;display:flex;flex-direction:column;justify-content:space-between;min-width:110px}
    .ecpw-price{font-weight:700;margin-top:6px}
    .ecpw-qty{color:#7a2a2a;font-size:13px}
    @media(max-width:600px){.ecpw-item{flex-direction:column}.ecpw-right{align-items:flex-end}}
    </style>\n";
	});


	require_once __DIR__ . '/includes/woocommerce/init.php';

	// add_action('wp_ajax_ecpw_add_to_cart', function(){
	// 	if (!function_exists('WC')) {
	// 		wp_send_json_error(['message' => 'WooCommerce not loaded']);
	// 	}

	// 	$product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
	// 	$quantity   = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

	// 	if (!$product_id) {
	// 		wp_send_json_error(['message' => 'Missing product_id']);
	// 	}

	// 	$product = wc_get_product($product_id);
	// 	if (!$product || 'publish' !== $product->get_status()) {
	// 		wp_send_json_error(['message' => 'Invalid product']);
	// 	}

	// 	// Collect posted attributes (flavors or real variation attrs)
	// 	$attributes = [];
	// 	foreach ($_POST as $key => $value) {
	// 		if (strpos($key, 'attribute_') === 0) {
	// 			$attributes[$key] = wc_clean(wp_unslash($value));
	// 		}
	// 	}

	// 	$variation_id = 0;
	// 	$variation_attributes = [];

	// 	// Handle variable products (real WC variations)
	// 	if ($product->is_type('variable')) {
	// 		$available = $product->get_available_variations();
	// 		$found = false;
	// 		foreach ($available as $variation) {
	// 			$match = true;
	// 			foreach ($variation['attributes'] as $attr_key => $attr_val) {
	// 				$posted_val = isset($attributes[$attr_key]) ? $attributes[$attr_key] : '';
	// 				if ($attr_val !== '' && $posted_val !== $attr_val) {
	// 					$match = false;
	// 					break;
	// 				}
	// 			}
	// 			if ($match) {
	// 				$variation_id = $variation['variation_id'];
	// 				$variation_attributes = $variation['attributes'];
	// 				$found = true;
	// 				break;
	// 			}
	// 		}

	// 		if (!$found) {
	// 			wp_send_json_error(['message' => __('Please choose product options.', 'woocommerce')]);
	// 		}
	// 	}

	// 	// ✅ Safe add-to-cart handling for all cases
	// 	if ($product->is_type('variable') && $variation_id) {
	// 		$added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_attributes);
	// 	} else {
	// 		// simple product OR product with flavors
	// 		$cart_item_data = [];

	// 		// Optional: include flavors in cart item data
	// 		if (!empty($attributes)) {
	// 			$cart_item_data['flavors'] = $attributes;
	// 		}

	// 		$added = WC()->cart->add_to_cart($product_id, $quantity, 0, [], $cart_item_data);
	// 	}

	// 	if (!$added) {
	// 		wp_send_json_error(['message' => __('Could not add to cart', 'woocommerce')]);
	// 	}

	// 	// Return updated cart fragments
	// 	WC_AJAX::get_refreshed_fragments();
	// 	wp_die();
	// });
	// add_action('wp_ajax_nopriv_ecpw_add_to_cart', function(){ do_action('wp_ajax_ecpw_add_to_cart'); });





	add_action('wp_ajax_ecpw_add_to_cart', function () {
		if (!function_exists('WC')) {
			wp_send_json_error(['message' => 'WooCommerce not loaded']);
		}

		$product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
		$quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

		if (!$product_id) {
			wp_send_json_error(['message' => 'Missing product_id']);
		}

		$product = wc_get_product($product_id);
		if (!$product || 'publish' !== $product->get_status()) {
			wp_send_json_error(['message' => 'Invalid product']);
		}

		// Collect posted attributes (flavors or real variation attrs)
		$attributes = [];
		foreach ($_POST as $key => $value) {
			if (strpos($key, 'attribute_') === 0) {
				$attributes[$key] = wc_clean(wp_unslash($value));
			}
		}

		$variation_id = 0;
		$variation_attributes = [];

		// Handle variable products (real WC variations)
		if ($product->is_type('variable')) {
			$available = $product->get_available_variations();
			$found = false;
			foreach ($available as $variation) {
				$match = true;
				foreach ($variation['attributes'] as $attr_key => $attr_val) {
					$posted_val = isset($attributes[$attr_key]) ? $attributes[$attr_key] : '';
					if ($attr_val !== '' && $posted_val !== $attr_val) {
						$match = false;
						break;
					}
				}
				if ($match) {
					$variation_id = $variation['variation_id'];
					$variation_attributes = $variation['attributes'];
					$found = true;
					break;
				}
			}

			if (!$found) {
				wp_send_json_error(['message' => __('Please choose product options.', 'woocommerce')]);
			}
		}

		// ✅ Simple product OR flavor-based logic
		$cart_item_data = [];

		if (!empty($attributes)) {
			$cart_item_data['flavors'] = $attributes;
		}

		// ✅ Add extra ingredients if sent
		if (!empty($_POST['extra_ingredients'])) {
			$extras = json_decode(stripslashes($_POST['extra_ingredients']), true);
			if (is_array($extras)) {
				$cart_item_data['extra_ingredients'] = $extras;
				$cart_item_data['extra_count'] = count($extras); // easier for price calc
			}
		}

		// Add to cart
		if ($product->is_type('variable') && $variation_id) {
			$added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_attributes, $cart_item_data);
		} else {
			$added = WC()->cart->add_to_cart($product_id, $quantity, 0, [], $cart_item_data);
		}

		if (!$added) {
			wp_send_json_error(['message' => __('Could not add to cart', 'woocommerce')]);
		}

		// Return updated cart fragments
		WC_AJAX::get_refreshed_fragments();
		wp_die();
	});
	add_action('wp_ajax_nopriv_ecpw_add_to_cart', function () {
		do_action('wp_ajax_ecpw_add_to_cart'); });

		

	add_action('woocommerce_before_calculate_totals', function ($cart) {
		if (is_admin() && !defined('DOING_AJAX')) {
			return;
		}

		foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
			if (!empty($cart_item['extra_ingredients']) && is_array($cart_item['extra_ingredients'])) {
				$extra_price_total = 0;

				foreach ($cart_item['extra_ingredients'] as $extra) {
					if (!empty($extra['price'])) {
						$extra_price_total += floatval($extra['price']);
					}
				}

				if ($extra_price_total > 0) {
					$base_price = floatval($cart_item['data']->get_price());
					$cart_item['data']->set_price($base_price + $extra_price_total);
				}
			}

		}
	});


});
