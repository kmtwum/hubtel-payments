<?php
/**
 * @wordpress-plugin
 * Plugin Name: Payments for Hubtel
 * Plugin URI: https://github.com/kmtwum/hubtel-payments
 * Description: Accept payments on your WooCommerce powered website directly to your Hubtel account.
 * Version: 1.0.0
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Tested up to: 6.5
 * Author: Kwame Twum
 * Author URI: https://github.com/kmtwum
 * WC requires at least: 7.0
 * WC tested up to: 8.3
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WooHubtel
 * @author    Kwame Twum
 * @copyright 2024 Kwame Twum
 * @category  Admin
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hubtel Payment gateway plugin class.
 *
 * @class Hubtel
 */
class Hubtel {

    /**
     * Plugin bootstrapping.
     */
    public static function init() {

        // Gateway class.
        add_action('plugins_loaded', array(__CLASS__, 'includes'), 0);

        // Make the gateway available to WC.
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));

        // Registers WooCommerce Blocks integration.
        add_action('woocommerce_blocks_loaded', array(__CLASS__, 'hubtel_blocks'));

    }

    /**
     * Add the Payment gateway to the list of available gateways.
     *
     * @param array
     */
    public static function add_gateway($gateways) {
        $options = get_option('woocommerce_hubtel_settings', array());
        $gateways[] = 'Hubtel_Gateway';
        return $gateways;
    }

    /**
     * Plugin includes.
     */
    public static function includes() {

        // Make the Hubtel_Gateway class available.
        if (class_exists('WC_Payment_Gateway')) {
            require_once  __DIR__ . '/includes/hubtel-gateway.php';
        }
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_url() {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Plugin verison.
     *
     * @return string
     */
    public static function version() {
        return '1.0.0';
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_abspath() {
        return trailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Registers WooCommerce Blocks integration.
     *
     */
    public static function hubtel_blocks() {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            require_once  __DIR__ . '/includes/blocks/hubtel-blocks.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new Hubtel_Blocks());
                }
            );
        }
    }


}

Hubtel::init();
