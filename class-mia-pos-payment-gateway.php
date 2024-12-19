<?php
defined('ABSPATH') || exit;

/**
 * MIA POS Payment Gateway
 */
class WC_MIA_POS_Payment_Gateway extends WC_Payment_Gateway
{

    #region Constants
    const MIA_POS_MOD_ID = 'mia_pos';
    const MIA_POS_MOD_TITLE = 'MIA POS Payment Gateway';
    const MIA_POS_MOD_DESC = 'Accept payments through MIA POS payment system';
    const MIA_POS_MOD_PREFIX = 'mia_pos_';

    const MIA_POS_SUPPORTED_CURRENCIES = ['MDL'];
    const MIA_POS_ORDER_TEMPLATE = 'Order #%1$s';
    #endregion

    public static $log_enabled = false;
    public static $log = false;

    protected $debug;
    protected $merchant_id, $secret_key, $terminal_id, $base_url;
    protected $payment_type, $language;
    protected $completed_order_status, $failed_order_status;
    protected $mia_pos_access_token = 'mia_pos_access_token';
    protected $mia_pos_refresh_token = 'mia_pos_refresh_token';

    // Routes
    protected $route_return_ok = 'mia-pos/return/ok';
    protected $route_return_fail = 'mia-pos/return/fail';
    protected $route_callback = 'mia-pos/callback';

    protected $p_success = "SUCCESS";
    protected $p_created = "CREATED";
    protected $p_expired = "EXPIRED";
    protected $p_failed = "FAILED";
    protected $p_declined = "DECLINED";
    protected $p_pending = "PENDING";

    /**
     * Constructor for the gateway
     */
    public function __construct()
    {
        $this->id = self::MIA_POS_MOD_ID;
        $this->icon = apply_filters('woocommerce_mia_pos_icon', MIA_POS_PLUGIN_URL . 'assets/img/mia-pos-logo.png');
        $this->has_fields = false;
        $this->method_title = self::MIA_POS_MOD_TITLE;
        $this->method_description = self::MIA_POS_MOD_DESC;

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        // Define properties
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        self::$log_enabled = $this->debug;

        $this->merchant_id = $this->get_option('merchant_id');
        $this->secret_key = $this->get_option('secret_key');
        $this->terminal_id = $this->get_option('terminal_id');
        $this->base_url = $this->get_option('base_url');
        $this->payment_type = $this->get_option('payment_type');
        $this->language = $this->get_option('language');

        $this->completed_order_status = $this->get_option('completed_order_status');
        $this->failed_order_status = $this->get_option('failed_order_status');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'clear_transients'));

        // API Routes
        add_action('woocommerce_api_' . $this->route_return_ok, array($this, 'route_return_ok'));
        add_action('woocommerce_api_' . $this->route_return_fail, array($this, 'route_return_fail'));
        add_action('woocommerce_api_' . $this->route_callback, array($this, 'route_callback'));
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable MIA POS Payment', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => __('MIA POS Payment', 'mia-pos-payment-gateway-for-woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => __('Pay securely using MIA POS payment system.', 'mia-pos-payment-gateway-for-woocommerce'),
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => __('Test mode', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Test Mode', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => 'yes',
                'description' => __('Place the payment gateway in test mode.', 'mia-pos-payment-gateway-for-woocommerce'),
            ),
            'merchant_id' => array(
                'title' => __('Merchant ID', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'text',
                'description' => __('Enter your Merchant ID provided by MIA POS.', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'secret_key' => array(
                'title' => __('Secret Key', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'password',
                'description' => __('Enter your Secret Key provided by MIA POS.', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'terminal_id' => array(
                'title' => __('Terminal ID', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'text',
                'description' => __('Enter your Terminal ID provided by MIA POS.', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'base_url' => array(
                'title' => __('API Base URL', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'text',
                'description' => __('Enter MIA POS API base URL.', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => 'https://ecomm.mia-pos.md',
                'desc_tip' => true,
            ),
            'payment_type' => array(
                'title' => __('Payment Type', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'select',
                'description' => __('Select the payment type.', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => 'qr',
                'options' => array(
                    'qr' => __('QR Payment', 'mia-pos-payment-gateway-for-woocommerce'),
                    'rtp' => __('Request to Pay', 'mia-pos-payment-gateway-for-woocommerce'),
                ),
            ),
            'language' => array(
                'title' => __('Language', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'select',
                'description' => __('Select the default language for payment page.', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => 'ro',
                'options' => array(
                    'ro' => __('Romanian', 'mia-pos-payment-gateway-for-woocommerce'),
                    'ru' => __('Russian', 'mia-pos-payment-gateway-for-woocommerce'),
                    'en' => __('English', 'mia-pos-payment-gateway-for-woocommerce'),
                ),
            ),
            'debug' => array(
                'title' => __('Debug mode', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'mia-pos-payment-gateway-for-woocommerce'),
                'default' => 'no',
                'description' => __('Save debug messages to the WooCommerce System Status logs.', 'mia-pos-payment-gateway-for-woocommerce'),
            ),

            'endpoint_urls' => array(
                'title' => __('Endpoint URLs', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'title',
                'description' => __('Add these URLs to your MIA POS merchant cabinet', 'mia-pos-payment-gateway-for-woocommerce'),
            ),
            'success_url' => array(
                'description' => sprintf('<b>%1$s:</b> <code>%2$s</code>',
                    __('Success URL', 'mia-pos-payment-gateway-for-woocommerce'),
                    esc_url(sprintf('%s/wc-api/%s', get_bloginfo('url'), $this->route_return_ok))
                ),
                'type' => 'title'
            ),
            'fail_url' => array(
                'description' => sprintf('<b>%1$s:</b> <code>%2$s</code>',
                    __('Fail URL', 'mia-pos-payment-gateway-for-woocommerce'),
                    esc_url(sprintf('%s/wc-api/%s', get_bloginfo('url'), $this->route_return_fail))
                ),
                'type' => 'title'
            ),
            'callback_url' => array(
                'description' => sprintf('<b>%1$s:</b> <code>%2$s</code>',
                    __('Callback URL', 'mia-pos-payment-gateway-for-woocommerce'),
                    esc_url(sprintf('%s/wc-api/%s', get_bloginfo('url'), $this->route_callback))
                ),
                'type' => 'title'
            ),

            'status_settings' => array(
                'title' => __('Order status', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'title'
            ),

            'completed_order_status' => array(
                'title' => __('Payment completed', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'select',
                'options' => $this->getPaymentOrderStatuses(),
                'default' => 'none',
                'description' => __('The completed order status after successful payment. By default: Processing.', 'mia-pos-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ),

            'failed_order_status' => array(
                'title' => __('Payment failed', 'mia-pos-payment-gateway-for-woocommerce'),
                'type' => 'select',
                'options' => $this->getPaymentOrderStatuses(),
                'default' => 'none',
                'description' => __('Order status when payment failed. By default: Failed.', 'mia-pos-payment-gateway-for-woocommerce'),
                'desc_tip' => true
            ),
        );
    }

    /**
     * Clear access token transients when settings saved
     */
    public function clear_transients()
    {
        delete_transient($this->mia_pos_access_token);
        delete_transient($this->mia_pos_refresh_token);
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id Order ID
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        try {

            $this->log(sprintf('Processing payment for order %d', $order_id));

            require_once MIA_POS_PLUGIN_DIR . 'includes/mia-pos-sdk/src/MiaPosSdk.php';

            // Validate required settings
            if (empty($this->merchant_id) || empty($this->secret_key) || empty($this->terminal_id)) {
                throw new Exception(__('MIA POS Payment Gateway is not properly configured.', 'mia-pos-payment-gateway-for-woocommerce'));
            }

            $sdk = new MiaPosSdk([
                'merchantId' => $this->merchant_id,
                'secretKey' => $this->secret_key,
                'baseUrl' => $this->base_url
            ]);

            $nonce = wp_create_nonce('verify_mia_order');
            $this->log(sprintf('Processing payment generated nonce %s, order_is %s', $nonce, $order_id));

            // Format client name
            $client_name = substr($order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), 0, 128);

            // Create payment request data
            $payment_data = [
                'terminalId' => $this->terminal_id,
                'orderId' => $order->get_order_number(),
                'amount' => (float)number_format($order->get_total(), 2, '.', ''),
                'currency' => $order->get_currency(),
                'language' => $this->language,
                'payDescription' => sprintf(self::MIA_POS_ORDER_TEMPLATE, $order->get_order_number()),
                'paymentType' => $this->payment_type,
                'clientName' => sanitize_text_field($client_name),
                'clientPhone' => substr(sanitize_text_field($order->get_billing_phone()), 0, 40),
                'clientEmail' => sanitize_email($order->get_billing_email()),
                'callbackUrl' => home_url("/wc-api/{$this->route_callback}"),
                'successUrl' => add_query_arg([
                    'nonce' => $nonce,
                    'orderId' => $order->get_id()
                ], home_url("/wc-api/{$this->route_return_ok}")),
                'failUrl' => add_query_arg([
                    'nonce' => $nonce,
                    'orderId' => $order->get_id()
                ], home_url("/wc-api/{$this->route_return_fail}"))
            ];


            $this->log(sprintf('Payment request data for order %d: %s',
                $order_id,
                wp_json_encode($payment_data, JSON_PRETTY_PRINT)
            ));


            // Validate currency
            if (!in_array($payment_data['currency'], self::MIA_POS_SUPPORTED_CURRENCIES)) {
                throw new Exception(sprintf(
                    __('Currency %s is not supported. Supported currencies: %s', 'mia-pos-payment-gateway-for-woocommerce'),
                    $payment_data['currency'],
                    implode(', ', self::MIA_POS_SUPPORTED_CURRENCIES)
                ));
            }

            // Create payment in MIA POS
            $response = $sdk->createPayment($payment_data);


            $this->log(sprintf('MIA POS response for order %d: %s',
                $order_id,
                wp_json_encode($response, JSON_PRETTY_PRINT)
            ));


            // Save payment details
            $order->update_meta_data('_mia_pos_payment_id', $response['paymentId']);
            $order->update_meta_data('_mia_pos_payment_created', current_time('mysql'));
            $order->update_meta_data('_mia_pos_nonce', $nonce);

            // Add order note
            $order->add_order_note(
                sprintf(
                    __('MIA POS payment initiated. Payment ID: %s, Amount: %s %s', 'mia-pos-payment-gateway-for-woocommerce'),
                    $response['paymentId'],
                    $payment_data['amount'],
                    $payment_data['currency']
                )
            );

            $order->save();

            $this->log(sprintf('Payment initiated successfully for order %d. Redirecting to: %s',
                $order_id,
                $response['checkoutPage']
            ));

            // Return success and redirect to payment page
            return [
                'result' => 'success',
                'redirect' => $response['checkoutPage']
            ];

        } catch (Exception $e) {
            $error_message = $e->getMessage();
            $this->log(sprintf('Payment error for order %d: %s', $order_id, $error_message));

            // Add order note about the error
            $order->add_order_note(
                sprintf(
                    __('MIA POS payment failed: %s', 'mia-pos-payment-gateway-for-woocommerce'),
                    $error_message
                )
            );

            wc_add_notice(
                __('Payment error:', 'mia-pos-payment-gateway-for-woocommerce') . ' ' . $error_message,
                'error'
            );

            return [
                'result' => 'fail',
                'redirect' => ''
            ];
        }
    }

    /**
     * Handle callback from MIA POS
     */
    public function route_callback()
    {
        try {
            // Get raw POST data
            $raw_post = file_get_contents('php://input');
            $this->log("Mia pos callback received raw data: " . $raw_post);

            // Decode JSON data
            $callback_data = json_decode($raw_post, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log("JSON decode error: " . json_last_error_msg());
                wp_die('Invalid JSON data', 'MIA POS Callback', array('response' => 400));
            }

            // Validate callback data structure
            if (!$callback_data ||
                !isset($callback_data['result']) ||
                !isset($callback_data['signature']) ||
                !isset($callback_data['result']['orderId']) ||
                !isset($callback_data['result']['status']) ||
                !isset($callback_data['result']['paymentId'])) {

                $this->log("Mia pos callback invalid callback data structure: " . wp_json_encode($callback_data, JSON_PRETTY_PRINT));
                wp_die('Invalid callback data structure', 'MIA POS Callback', array('response' => 400));
            }

            $result = $callback_data['result'];
            $order_id = $result['orderId'];

            $this->log("Mia pos callback start check sign for order_id: " . $order_id);

            // Initialize SDK
            require_once MIA_POS_PLUGIN_DIR . 'includes/mia-pos-sdk/src/MiaPosSdk.php';
            $sdk = new MiaPosSdk([
                'merchantId' => $this->merchant_id,
                'secretKey' => $this->secret_key,
                'baseUrl' => $this->base_url
            ]);

            $result_str = $this->form_sign_str_by_result_json($result, $order_id);
            // Verify signature
            if (!$sdk->verifySignature($result_str, $callback_data['signature'])) {
                $this->log("Mia pos invalid signature for callback data: " . wp_json_encode($callback_data, JSON_PRETTY_PRINT));
                wp_die('Invalid signature', 'MIA POS Callback', array('response' => 400));
            }

            $this->log("Mia pos callback signature is valid for order_id: {$order_id}, signature: {$callback_data['signature']}");

            $order = wc_get_order($order_id);

            if (!$order) {
                $this->log("Mia pos callback order not found: " . $result['orderId']);
                wp_die('Order not found', 'MIA POS Callback', array('response' => 404));
            }

            // Verify payment hasn't been processed already
            $current_payment_id = $order->get_meta('_mia_pos_payment_id');
            if ($current_payment_id && $current_payment_id !== $result['paymentId']) {
                $this->log("Mia pos payment ID mismatch for orderId: {$order_id}. Expected: {$current_payment_id}, Received: {$result['paymentId']}");
                wp_die('Payment ID mismatch', 'MIA POS Callback', array('response' => 400));
            }

            $current_state = $order->get_status();
            $result_state = $result['status'];
            if (!in_array($current_state, array('pending', 'failed'))) {
                $this->log("Mia Pos callback order {$order_id} already is in final state {$current_state}, notify is ignored for new result state {$result_state}");
                $tx_note = sprintf('MIA POS ignored payment callback details: %s, source (%s)', wp_json_encode($result, JSON_PRETTY_PRINT), 'callback_url');
                $order->add_order_note($tx_note);

                wp_die('OK', 'MIA POS Callback', array('response' => 200));
            }

            // Update order status based on payment status
            switch ($result['status']) {
                case $this->p_success:
                    $this->payment_complete($order, $current_payment_id, $result, 'callback_url');
                    break;

                case $this->p_pending:
                case $this->p_created:
                    $this->payment_pending($order, $current_payment_id, $result, 'callback_url');
                    break;

                case $this->p_declined:
                case $this->p_failed:
                case $this->p_expired:
                    $this->payment_failed($order, $current_payment_id, $result, 'callback_url');
                    break;

                default:
                    $this->log("Mia Pos callback order unknown payment status received: {$result['status']}");
                    wp_die('Unknown payment status', 'MIA POS Callback', array('response' => 400));
            }

            // Save full payment details in order meta
            $order->update_meta_data('_mia_pos_payment_details', $result);
            $order->save();

            wp_die('OK', 'MIA POS Callback', array('response' => 200));

        } catch (Exception $e) {
            $this->log("Mia Pos callback processing error for order_id {$order_id}: " . $e->getMessage());
            wp_die('Internal server error', 'MIA POS Callback', array('response' => 500));
        }
    }

    /**
     * Output payment fields and payment button
     */
    public function payment_fields()
    {
        // Output description if set
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }

        // Add payment button
        ?>
        <div class="mia-pos-payment-form">
            <?php if ($this->testmode): ?>
                <p class="mia-pos-test-mode-notice">
                    <?php _e('TEST MODE ENABLED', 'mia-pos-payment-gateway-for-woocommerce'); ?>
                </p>
            <?php endif; ?>

            <button type="button" class="mia-pos-payment-button">
                <img src="<?php echo MIA_POS_PLUGIN_URL . 'assets/img/mia-pos-logo.png'; ?>"
                     alt="MIA POS"
                     class="mia-pos-payment-icon"/>
                <?php _e('Pay with MIA POS', 'mia-pos-payment-gateway-for-woocommerce'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Handle successful payment return
     */
    public function route_return_ok()
    {
        $this->process_route_return_user('Return Ok', 'route_return_ok');
    }

    /**
     * Handle failed payment return
     */
    public function route_return_fail()
    {
        $this->process_route_return_user('Return Fail', 'route_return_fail');
    }


    public function process_route_return_user($log_prefix, $source_redirect)
    {
        $order_id = isset($_GET['orderId']) ? absint($_GET['orderId']) : false;

        if (!$order_id) {
            $this->log(sprintf('%s - Order ID not found in return URL', $log_prefix));
            wc_add_notice(__('Payment verification failed.', 'mia-pos-payment-gateway-for-woocommerce'), 'error');
            wp_redirect(wc_get_checkout_url());
            exit;
        }

        $this->log(sprintf('%s - Order ID found in return URL %s', $log_prefix, $order_id));
        $order = wc_get_order($order_id);
        if (!$order) {
            $this->log(sprintf('%s - Order not found: %s', $log_prefix, $order_id));
            wc_add_notice(__('Order not found.', 'mia-pos-payment-gateway-for-woocommerce'), 'error');
            wp_redirect(wc_get_checkout_url());
            exit;
        }

        $this->log(sprintf('%s - Order found %d: %s',
            $log_prefix,
            $order_id,
            wp_json_encode($order, JSON_PRETTY_PRINT)
        ));

        $saved_nonce = $order->get_meta('_mia_pos_nonce');
        $payment_id = $order->get_meta('_mia_pos_payment_id');
        $this->log(sprintf('%s - payment_id: %s, order_id: %s', $log_prefix, $payment_id, $order_id));

        $nonce_code = $_GET['nonce'];
        if (!isset($nonce_code) || $saved_nonce !== $nonce_code) {
            $this->log(sprintf('%s - nonce is not valid in return URL %s, saved_nonce %s', $log_prefix, $nonce_code, $saved_nonce));
            wp_die(__('Security check failed.', 'mia-pos-payment-gateway-for-woocommerce'));
        }

        // Verify payment status
        try {
            require_once MIA_POS_PLUGIN_DIR . 'includes/mia-pos-sdk/src/MiaPosSdk.php';
            $sdk = new MiaPosSdk([
                'merchantId' => $this->merchant_id,
                'secretKey' => $this->secret_key,
                'baseUrl' => $this->base_url
            ]);

            $payment_status = $sdk->getPaymentStatus($payment_id);
            $this->log(sprintf('%s - payemnt status check by orderId %d: %s',
                $log_prefix,
                $order_id,
                wp_json_encode($payment_status, JSON_PRETTY_PRINT)
            ));

            $state = $payment_status['status'];
            if ($state === ($this->p_success)) {
                $this->payment_complete($order, $payment_id, $payment_status, $source_redirect);
                wp_safe_redirect($this->get_safe_return_url($order));
            } elseif ($state === ($this->p_pending) || $state === ($this->p_created)) {
                $this->payment_pending($order, $payment_id, $payment_status, $source_redirect);
                wp_safe_redirect($order->get_checkout_payment_url());
            } else {
                $this->payment_failed($order, $payment_id, $payment_status, $source_redirect);
                $message = sprintf(__('Order #%1$s payment failed via %2$s. %3$s', 'wp_safe_redirect'), $order_id, self::MIA_POS_MOD_TITLE, $state);
                wc_add_notice($message, 'error');
                wp_safe_redirect($order->get_checkout_payment_url());
            }
            exit;
        } catch (Exception $e) {
            $error_msg = sprintf('%s, payment status check failed by orderId %d: %s', $log_prefix, $order_id, $e->getMessage());
            $this->log($error_msg);

            $message = sprintf(__('Order #%1$s payment verification failed %2$s.', 'mia-pos-payment-gateway-for-woocommerce'), $order_id, self::MIA_POS_MOD_TITLE);
            wc_add_notice($message, 'error');
            wp_safe_redirect($order->get_checkout_payment_url());
            exit;
        }
    }


    private function payment_complete($order, $pay_id, $payment, $source)
    {
        $state = $payment['status'];

        if ($order->payment_complete()) {
            $this->log('MIA POS payment completed - payment_id: ' . $pay_id . ', order_id: ' . $order->get_id() . '. completed_order_status: ' . $this->completed_order_status);

            $order_note = sprintf(__('MIA POS payment (%1$s) successful. Updated by mia payment status (%2$s), source (%3$s)', 'mia-pos-payment-gateway'), $pay_id, $state, $source);

            if ($this->completed_order_status != 'default') {
                WC()->cart->empty_cart();
                $order->update_status($this->completed_order_status, $order_note);

                $this->log('MIA POS payment  - payment_id: ' . $pay_id . ', order_id: ' . $order->get_id() . '. Updated status for order: ' . $this->completed_order_status);
            } else {
                $order->add_order_note($order_note);
            }

            $this->log($order_note, 'notice');

            $tx_note = sprintf(__('MIA POS payment details: %s, source (%s)', 'mia-pos-payment-gateway-for-woocommerce'), wp_json_encode($payment, JSON_PRETTY_PRINT), $source);
            $order->add_order_note($tx_note);

            return true;
        }
        return false;
    }

    private function payment_pending($order, $pay_id, $state, $source)
    {
        $this->log('MIA POS payment is not final state - payment_id: ' . $pay_id . ', order_id: ' . $order->get_id() . '. state: ' . $state);
        $order_note = sprintf(__('MIA POS payment (%1$s) is not final. Received  payment status (%2$s), source (%3$s)', 'mia-pos-payment-gateway'), $pay_id, $state, $source);
        $order->add_order_note($order_note);
        $this->log($order_note, 'notice');

        return true;
    }

    private function payment_failed($order, $pay_id, $payment, $source)
    {
        $state = $payment['status'];
        $this->log('MIA POS payment fail state - payment_id: ' . $pay_id . ', order_id: ' . $order->get_id() . '. state: ' . $state . ', source: ' . $source);
        $order_note = sprintf(__('MIA POS payment (%1$s) failed. Received  payment status (%2$s), source (%3$s)', 'mia-pos-payment-gateway'), $pay_id, $state, $source);
        $tx_note = sprintf(__('MIA POS payment details: %s, source (%s)', 'mia-pos-payment-gateway-for-woocommerce'), wp_json_encode($payment, JSON_PRETTY_PRINT), $source);
        $order->add_order_note($tx_note);
        $newOrderStatus = $this->failed_order_status != 'default' ? $this->failed_order_status : 'failed';

        if ($order->has_status('$newOrderStatus')) {
            $order_note = sprintf(__('MIA POS payment (%1$s) failed. Received  payment status (%2$s), source (%3$s)', 'mia-pos-payment-gateway'), $pay_id, $state, $source);
            $order->add_order_note($order_note);
            $this->log($order_note, 'notice');
            return true;
        } else {
            $this->log($order_note, 'notice');
            return $order->update_status($newOrderStatus, $order_note);
        }
    }



    private function form_sign_str_by_result_json($result_data, $order_id)
    {
        ksort($result_data);

        $result_str = implode(
            ';',
            array_map(function ($key, $value) {
                if ($key === 'amount') {
                    return number_format($value, 2, '.', '');
                }
                return (string)$value;
            }, array_keys($result_data), $result_data)
        );

        $this->log("Mia Pos sign str for order_id: {$order_id}, signature: {$result_str}");
        return $result_str;
    }


    /**
     * Getting all available woocommerce order statuses
     *
     * @return array
     */
    public function getPaymentOrderStatuses()
    {
        $order_statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [];
        $statuses = ['default' => __('Default status', 'mia-pos-payment-gateway-for-woocommerce')];
        if ($order_statuses) {
            foreach ($order_statuses as $k => $v) {
                $statuses[str_replace('wc-', '', $k)] = $v;
            }
        }

        return $statuses;
    }


    /**
     * Get return url (order received page) in a safe manner.
     */
    private function get_safe_return_url($order)
    {
        $this->log('Check for redirect URL by order_id: ' . $order->get_id() . ', user_id: ' . $order->get_user_id() . ', current_user_id: ' . get_current_user_id());
        if ($order->get_user_id() === get_current_user_id()) {
            $this->log('Return OK - user id is equals ');
            return $this->get_return_url($order);
        } else {
            return wc_get_endpoint_url('order-received', '', wc_get_page_permalink('checkout'));
        }
    }

    /**
     * Log messages for debugging.
     *
     * @param string $message The log message.
     * @param string $level Optional. Log level: debug|info|notice|warning|error|critical.
     */
    private function log($message, $level = 'info')
    {
        if ($this->debug) {
            if (!self::$log) {
                self::$log = wc_get_logger();
            }
            if (self::$log) {
                self::$log->log($level, $message, ['source' => $this->id]);
            }
        }
        error_log($message);
    }


} 