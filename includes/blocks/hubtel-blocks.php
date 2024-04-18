<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Payments for Hubtel Blocks integration
 *
 * @since 1.0.0
 */
final class Hubtel_Blocks extends AbstractPaymentMethodType {

    /**
     * The gateway instance.
     *
     * @var Hubtel_Gateway
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'payments-hubtel';

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_hubtel_settings', []);
        $this->gateway = new Hubtel_Gateway();
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active() {
        return $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
        $script_path = '/assets/js/frontend/blocks.js';
        $script_asset_path = Hubtel::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require $script_asset_path
            : [
                'dependencies' => [],
                'version' => '1.2.0'
            ];
        $script_url = Hubtel::plugin_url() . $script_path;

        wp_register_script(
            'wc-hubtel-blocks',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc-hubtel-blocks', 'payments-hubtel', Hubtel::plugin_abspath() . 'languages/');
        }

        return ['wc-hubtel-blocks'];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data() {
        $activation = $this->get_setting('activation_code');
        if (empty($activation)) {
            return [
                'title' => 'Unregistered Plugin',
                'description' => 'Payment will fail if selected. Do not go ahead!',
                'activation' => $this->get_setting('activation_code'),
                'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
                'logo' => Hubtel::plugin_url() . '/assets/blank.png'
            ];
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];
        $args = ['headers' => $headers, 'timeout' => 60, 'body' => wp_json_encode([
            'code' => $activation,
            'site' => site_url(),
        ])];

        $request = wp_remote_post('https://excelliumgh.com/cdn/plugins/woo-hubtel/verify', $args);
        $validity = wp_remote_retrieve_body($request);

        $decoded = json_decode($validity);
        $valid = $decoded->valid;

        return [
            'title' => $valid ? $this->get_setting('title') : 'Unregistered Plugin',
            'description' => $valid ? $this->get_setting('description')
                : 'Payment will fail if selected. Do not go ahead!',
            'activation' => $this->get_setting('activation_code'),
            'supports' => array_filter($this->gateway->supports, [$this->gateway, 'supports']),
            'logo' => $valid ? Hubtel::plugin_url() . '/assets/hubtel.png'
                : Hubtel::plugin_url() . '/assets/blank.png'
        ];
    }
}
