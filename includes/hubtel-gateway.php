<?php
/**
 * Hubtel_Gateway class
 *
 * @author   Kwame Twum <kwame.m.twum@gmail.com>
 * @package  Hubtel WordPress Plugin
 * @since    1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hubtel Gateway.
 *
 * @class    Hubtel_Gateway
 * @version  1.0.0
 */
class Hubtel_Gateway extends WC_Payment_Gateway {

    public $clientId;
    public $activation;
    public $clientSecret;
    public $mobileNumber;
    public $userMode;
    public $merchantAccount;
    public $id;
    public $icon;
    public $has_fields;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id = 'hubtel';
        $this->icon = Hubtel::plugin_url() . '/assets/hubtel.png';
        $this->has_fields = false;

        $this->method_title = _x('Mobile Money / Cards', 'Hubtel payment method', 'woo-hubtel');
        $this->method_description = __('Pay with mobile money or a Ghana issued card.', 'woo-hubtel');

        // Load the settings.
        $this->init_settings();
        $this->init_form_fields();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_api_hubtel_gateway', [$this, 'direct_feedback']);
        add_action('woocommerce_api_hubtel_gateway_delayed', [$this, 'delayed_feedback']);
        add_action('woocommerce_before_thankyou', [$this, 'success_message_after_payment']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $validity = $this->validate_request($this->settings['activation_code']);
        if ($validity->valid) {
            $valid_message = "<br> Status: <span style='padding: 1px 5px 3px 5px; border-radius: 5px; color:white; background-color: #00c731'>Activated</span> <br>
            Expires on " . date('jS F Y', strtotime($validity->validTill)) . ".";
        } elseif ($validity->validTill != null) {
            $valid_message = "<br> Status: <span style='padding: 1px 5px 3px 5px; border-radius: 5px; color:white; background-color: #bb0000'>Expired</span> <br>
            Expired on " . date('jS F Y', strtotime($validity->validTill)). "Please purchase a new code.";
        } else {
            $valid_message = "<br> Status: <span style='padding: 1px 5px 3px 5px; border-radius: 5px; color:white; background-color: #aaa'>Invalid / Empty Code</span> <br>";
        }
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woo-hubtel'),
                'type' => 'checkbox',
                'label' => __('Enable Hubtel Payments', 'woo-hubtel'),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Title', 'woo-hubtel'),
                'type' => 'text',
                'label' => __('Mobile money / Cards', 'woo-hubtel'),
                'description' => __('The title a user will see representing Hubtel payments.', 'woo-hubtel'),
                'default' => 'Mobile money / Cards',
            ],
            'description' => [
                'title' => __('Description', 'woo-hubtel'),
                'type' => 'text',
                'label' => __('Pay with mobile money or a Ghana issued card.', 'woo-hubtel'),
                'description' => __('The description a user will see on checkout representing Hubtel payments',
                    'woo-hubtel'),
                'default' => 'Pay with mobile money or a Ghana issued card.',
            ],
            'merchant_options' => [
                'title' => __('Hubtel Options', 'woo-hubtel'),
                'type' => 'title',
                'description' => __("The following options affect where your funds will be sent when users pay.
                \r\n Use only if you have a fund collection account", 'woo-hubtel'),
                'id' => 'merchant_options'
            ],
            'user_type' => [
                'title' => __('User Type', 'woo-hubtel'),
                'type' => 'select',
                'description' => __('Specify whether you\'re a merchant or a normal customer.', 'woo-hubtel'),
                'default' => '',
                'desc_tip' => false,
                'options' => [
                    '' => __('Select Type', 'woo-hubtel'),
                    'merchant' => __('Merchant', 'woo-hubtel'),
                    'consumer' => __('Customer / Consumer', 'woo-hubtel'),
                ],
            ],
            'mobile_number' => [
                'title' => __('Your Mobile Number', 'woo-hubtel'),
                'id' => 'hubtel_mobile_number',
                'type' => 'text',
                'placeholder' => '233.........',
                'description' => sprintf(__('Your mobile number, as used on Hubtel. Beginning with %1$s233%2$s',
                    'woo-hubtel'), '<b>', '</b>'),
                'desc_tip' => false,
            ],
            'merchant_account_number' => [
                'title' => __('Merchant Account Number', 'woo-hubtel'),
                'id' => 'hubtel_merchant_account_number',
                'type' => 'text',
                'description' => __('Account Number of your POS Sales Account, issued on Hubtel.',
                    'woo-hubtel'),
                'desc_tip' => false
            ],
            'client_id' => [
                'title' => __('Client ID / API ID', 'woo-hubtel'),
                'id' => 'hubtel_client_id',
                'type' => 'text',
                'description' => __('Your Client Id (consumer) or API ID (merchant) issued on Hubtel.', 'woo-hubtel'),
                'desc_tip' => false
            ],
            'client_secret' => [
                'title' => __('Client Secret / API Key', 'woo-hubtel'),
                'id' => 'hubtel_client_secret',
                'type' => 'text',
                'description' => __('Your Client Secret (consumer) or API Key (merchant) issued on Hubtel.',
                    'woo-hubtel'),
                'desc_tip' => false
            ],
            'activation_code' => [
                'title' => __('Activation Key', 'woo-hubtel'),
                'id' => 'activation_code',
                'type' => 'password',
                'description' => __("Code to activate plugin." . $valid_message, 'woo-hubtel'),
                'desc_tip' => false
            ],
            'rule' => [
                'title' => __('', 'hubtel'),
                'type' => 'title',
                'description' => __('<hr/>', 'hubtel'),
                'default' => '',
            ],
            'activation_instructions' => [
                'title' => __('Activation Instructions', 'hubtel'),
                'type' => 'title',
                'description' => __('<ul><li>
                                                • Click <a target="_blank" href="https://paystack.com/pay/hubtel-activate">here</a>
                                                to purchase your activation key.
                                            </li>
                                            <li>
                                                • After payment, you’ll receive an SMS with your activation key
                                            </li>
                                            <li>
                                                • Head back here to enter your activation key to activate the plugin
                                            </li>
                                            </ul>', 'hubtel'),
                'default' => '',
            ]
        ];
    }

    public function process_admin_options() {
        $this->init_form_fields();
        $post_data = $this->get_post_data();
        foreach ($this->get_form_fields() as $key => $field) {
            if ('title' !== $this->get_field_type($field)) {
                try {
                    $this->settings[$key] = $this->get_field_value($key, $field, $post_data);
                } catch (Exception $e) {
                    $this->add_error($e->getMessage());
                }
            }
        }

        $validity = $this->validate_request($this->settings['activation_code']);
        if (!$validity->valid) {
            $this->settings['enabled'] = 'no';
        } else {
            $this->settings['enabled'] = 'yes';
        }

        return update_option($this->get_option_key(),
            apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings), 'yes');
    }

    /**
     * Process the payment and return the result.
     * @param int $order_id
     * @return array
     * @throws Exception
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $order_data = json_decode($order);
        $hp_settings = get_option('woocommerce_hubtel_settings');
        $this->clientId = $hp_settings['client_id'];
        $this->activation = $hp_settings['activation_code'];
        $this->clientSecret = $hp_settings['client_secret'];
        $this->userMode = $hp_settings['user_type'];

        $validity = $this->validate_request($this->activation);

        if (!$validity->valid) {
            $message = __('Order payment failed. Please retry after some time.', 'woo-hubtel');
            throw new Exception($message);
        }

        if ($this->userMode === 'merchant') {
            $this->merchantAccount = $hp_settings['merchant_account_number'];
            try {
                $redirectUrl = $this->process_merchant_payment($order_data);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        } else {
            $this->mobileNumber = $hp_settings['mobile_number'];
            try {
                $redirectUrl = $this->process_consumer_payment($order_data);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        }

        return [
            'result' => 'success',
            'redirect' => $redirectUrl
        ];
    }

    /**
     * @throws Exception
     */
    function process_consumer_payment($order_data) {
        $payload = [
            "identifier" => $this->clientId . ':' . $this->clientSecret,
            "amount" => (float)$order_data->total,
            "title" => 'Order Payment',
            "description" => 'Purchase made on ' . get_bloginfo('name'),
            "clientReference" => $this->generateId('WOO') . $order_data->id,
            "callbackUrl" => WC()->api_request_url('hubtel_gateway_delayed'),
            "cancellationUrl" => WC()->api_request_url('hubtel_gateway') . '?o=' . $order_data->id,
            "returnUrl" => WC()->api_request_url('hubtel_gateway') . '?o=' . $order_data->id,
            "mobile" => $this->mobileNumber,
            "userMode" => $this->userMode,
            "site" => get_bloginfo('url')
        ];

        $this->tail($payload);

        $url = "https://excelliumgh.com/cdn/plugins/woo-hubtel/pay";
        $args = ['headers' => ['Content-Type' => 'application/json'], 'timeout' => 60, 'body' => json_encode($payload)];

        $request = wp_remote_post($url, $args);
        if (!is_wp_error($request) && 201 === wp_remote_retrieve_response_code($request)) {
            $response = json_decode(wp_remote_retrieve_body($request));
            return $response->data->paylinkUrl;
        } else {
            $message = __('Order payment failed. Please retry after some time.', 'woo-hubtel');
            throw new Exception($message);
        }
    }

    function process_merchant_payment($order_data) {
        $payload = [
            "identifier" => $this->clientId . ':' . $this->clientSecret,
            "amount" => (float)$order_data->total,
            "description" => 'Purchase made on ' . get_bloginfo('name'),
            "callbackUrl" => WC()->api_request_url('hubtel_gateway_delayed'),
            "returnUrl" => WC()->api_request_url('hubtel_gateway') . '?o=' . $order_data->id,
            "cancellationUrl" => WC()->api_request_url('hubtel_gateway') . '?o=' . $order_data->id,
            "accountNumber" => $this->merchantAccount,
            "clientReference" => $this->generateId('WOO') . $order_data->id,
            "userMode" => $this->userMode,
            "site" => get_bloginfo('url')
        ];

        $this->tail($payload);

        $url = 'https://excelliumgh.com/cdn/plugins/woo-hubtel/pay';
        $args = ['headers' => ['Content-Type' => 'application/json'], 'timeout' => 60, 'body' => json_encode($payload)];

        $request = wp_remote_post($url, $args);
        if (!is_wp_error($request) && 200 === wp_remote_retrieve_response_code($request)) {
            $response = json_decode(wp_remote_retrieve_body($request));
            if ($response->responseCode === '0000') {
                return $response->data->checkoutDirectUrl;
            }
        }
        $message = __('Order payment failed. Please retry after some time.', 'woo-hubtel');
        throw new Exception($message);
    }

    public function direct_feedback() {
        if (!isset($_REQUEST['o'])) {
            exit;
        }

        $orderString = $_REQUEST['o'];
        if (strpos($orderString, '?') !== false) {
            $order_id = substr($orderString, 0, strpos($orderString, '?'));
        } else {
            $order_id = $orderString;
        }
        $this->tail('Order Id seen as' . $order_id);

        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        // Return thank-you redirect
        wp_redirect($this->get_return_url($order));
        exit;
    }

    public function delayed_feedback() {
        $json = file_get_contents('php://input');
        $response = json_decode($json);

        if ($response->ResponseCode === '0000') {
            $clientReference = $response->Data->ClientReference;
            $externalReference = $response->Data->TransactionId ?? $response->Data->SalesInvoiceId;
            $order_id = $this->getOrderIdFromReference($clientReference);
            $order = wc_get_order($order_id);

            //  Complete order and clear cart
            $order->payment_complete();
            WC()->cart->empty_cart();

            //  Update order with external transaction
            $order->add_order_note(sprintf(__('Hubtel payment successful. External Id: %s)', 'woo-hubtel'),
                $externalReference));
            $order->save();
        }
        exit;
    }

    public function success_message_after_payment($order_id) {
        $order = wc_get_order($order_id);
        if (in_array($order->get_status(), ['processing', 'completed'])) {
            wc_print_notice(__("Your payment has been received successfully", "woocommerce"));
        }
    }

    public function admin_scripts() {
        if ('woocommerce_page_wc-settings' !== get_current_screen()->id) {
            return;
        }

        $admin_params = ['plugin_url' => Hubtel::plugin_url()];

        wp_enqueue_script('wc_hubtel_admin', Hubtel::plugin_url() . '/assets/js/admin.js',
            array(), Hubtel::version(), true);

        wp_localize_script('wc_hubtel_admin', 'wc_hubtel_admin_params', $admin_params);

    }

    public function tail($str) {
        @file_put_contents(__DIR__ . '/log.txt', print_r($str, true) . "\r\n", FILE_APPEND | LOCK_EX);
    }

    public function generateId($prefix): string {
        $date = new DateTime ();
        $stamp = $date->format('Y-m-d');
        return $prefix . str_replace('-', '', $stamp) . mt_rand(10000, 50000);
    }

    public function getOrderIdFromReference($reference): string {
        $date = new DateTime ();
        $stamp = $date->format('Y-m-d');
        $formattedTimeStamp = str_replace('-', '', $stamp);
        if (strpos($reference, $formattedTimeStamp) === false) {
            return '';
        }
        return substr($reference, 8 + (strlen($formattedTimeStamp)));
    }

    public function validate_request($code) {
        if (empty($code)) {
            return false;
        }

        $headers = [
            'Content-Type' => 'application/json',
        ];
        $args = ['headers' => $headers, 'timeout' => 60, 'body' => json_encode([
            'code' => $code,
            'site' => site_url(),
        ])];
        $request = wp_remote_post('https://excelliumgh.com/cdn/plugins/woo-hubtel/verify', $args);
        return json_decode(wp_remote_retrieve_body($request));
    }
}
