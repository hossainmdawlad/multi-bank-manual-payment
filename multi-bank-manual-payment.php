<?php
/*
Plugin Name: Multi Bank Manual Payment
Plugin URI:  https://wordpress.org/plugins/multi-bank-manual-payment/
Description: WooCommerce payment gateway allowing customers to choose from multiple bank accounts, view full account details including SWIFT code and bank address, and upload a payment receipt (screenshot/image) during checkout for manual verification.
Version:     3.0.0
Author:      Hossain Md. Awlad
Author URI:  https://www.technoviable.com
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Requires Plugins: woocommerce
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Contributors: hossainmdawlad
Text Domain: multi-bank-manual-payment
Domain Path: /languages
Stable tag:  3.0.0
*/

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

    add_action( 'wp_enqueue_scripts', function() {
        if ( is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) {
            
            /**
             * Using anonymous logic inside action to avoid Non-Prefixed Function Warnings
             */
            $css_path = plugin_dir_path( __FILE__ ) . 'assets/mbpm-style-checkout.css';
            
            $css_v = file_exists( $css_path ) ? filemtime( $css_path ) : '3.0.0';

            wp_enqueue_style( 'mbmp-style-checkout', plugins_url( 'assets/mbpm-style-checkout.css', __FILE__ ), array(), $css_v );
        }
    });

    // Block-based checkout compatibility
    add_action( 'wp_enqueue_scripts', function() {
        // Only load on checkout pages (works for both classic and block checkout)
        if ( ! is_checkout() ) {
            return;
        }
        
        $block_js_path = plugin_dir_path( __FILE__ ) . 'assets/mbmp-blocks-integration.js';
        if ( file_exists( $block_js_path ) && class_exists( 'WC_Blocks_Loader' ) ) {
            wp_enqueue_script(
                'mbmp-blocks-integration',
                plugins_url( 'assets/mbmp-blocks-integration.js', __FILE__ ),
                array( 'wp-components', 'wc-blocks-registry' ),
                '3.0.0',
                true
            );

            // Get the gateway instance and localize payment method data
            if ( isset( WC()->payment_gateways ) ) {
                $gateways = WC()->payment_gateways->payment_gateways();
                if ( isset( $gateways['multi_bank_manual_payment'] ) ) {
                    $gateway = $gateways['multi_bank_manual_payment'];
                    if ( method_exists( $gateway, 'get_payment_method_data' ) ) {
                        wp_localize_script(
                            'mbmp-blocks-integration',
                            'multi_bank_manual_payment_data',
                            $gateway->get_payment_method_data()
                        );
                    }
                }
            }
        }
    }, 100 );

    // Hook at priority 11 to ensure WooCommerce has already loaded WC_Payment_Gateway class
    add_action( 'plugins_loaded', function() {
        if ( class_exists( 'WC_Payment_Gateway' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'includes/class-mbmp-wc-gateway-multi-bank-manual-payment.php';

            // Register the gateway filter AFTER class is loaded
            add_filter( 'woocommerce_payment_gateways', function( $methods ) {
                $methods[] = 'MBMP_WC_Gateway_Multi_Bank_Manual_Payment';
                return $methods;
            });
        }
    }, 11 );
}

