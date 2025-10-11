<?php
/**
 * Plugin Name: Elementor Cart Products Styled Widget
 * Description: An Elementor widget that displays WooCommerce cart products with ingredients parsed from a product attribute ("ingredients") or from custom meta. Styled to match the provided design.
 * Version: 1.0.0
 * Author: SERVETECH
 * Text Domain: elementor-cart-products-widget
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Require WooCommerce and Elementor
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'WooCommerce' ) || ! defined( 'ELEMENTOR_PATH' ) ) {
        return;
    }

    // Register widget
    add_action( 'elementor/widgets/register', function( $widgets_manager ) {
        if ( ! class_exists( '\ECPW\Cart_Products_Widget' ) ) {
            // Define the widget class inline
     
            require_once __DIR__ . '/widgets/dev-cart-products.php';

            // Register widget instance
            $widgets_manager->register( new \ECPW\Cart_Products_Widget() );
        }
    } );
    // Enqueue styles
    add_action( 'wp_enqueue_scripts', function() {
        wp_register_style( 'ecpw-styles', plugins_url( 'assets/ecpw-styles.css', __FILE__ ) );
        wp_enqueue_style( 'ecpw-styles' );
    } );

    add_action( 'wp_head', function() {
            // Only print CSS when WooCommerce cart exists on front-end to reduce noise.
            if ( ! function_exists( 'WC' ) || ! is_cart() && ! is_checkout() && empty( \WC()->cart->get_cart() ) ) {
                return;
            }

            // Print safe scoped CSS
            echo "\n<style id=\"ecpw-inline-styles\">\n.ecpw-list{display:flex;flex-direction:column;gap:18px;margin:0;padding:0}\n.ecpw-item{display:flex;justify-content:space-between;background:#fdefe9;border:1px solid #e7b9b3;border-radius:10px;padding:14px}\n.ecpw-left{display:flex;gap:12px;align-items:flex-start}
    .ecpw-thumb img{width:84px;height:84px;border-radius:8px;object-fit:cover}
    .ecpw-title{margin:0 0 8px 0;font-size:15px;color:#7a2a2a}
    .ecpw-ingredients{list-style:none;margin:0;padding:0;color:#8b5858;font-size:13px}
    .ecpw-ingredients li{margin:3px 0}
    .ecpw-right{text-align:right;display:flex;flex-direction:column;justify-content:space-between;min-width:110px}
    .ecpw-price{font-weight:700;margin-top:6px}
    .ecpw-qty{color:#7a2a2a;font-size:13px}
    @media(max-width:600px){.ecpw-item{flex-direction:column}.ecpw-right{align-items:flex-end}}
    </style>\n";
        } );

} );
