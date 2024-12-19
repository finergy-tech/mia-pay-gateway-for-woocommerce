<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Class MiaPosPaymentGateway_Blocks
 *
 * This class integrates the MIA POS payment gateway with WooCommerce blocks.
 */
final class MiaPosPaymentGateway_Blocks extends AbstractPaymentMethodType {

    private $gateway; // Instance of the MIA POS payment gateway class
    protected $name = 'mia_pos'; // Payment method identifier

    /**
     * Initialize the payment gateway
     */
    public function initialize() 
    {
        // Get gateway settings from WooCommerce options
        $this->settings = get_option('woocommerce_mia_pos_settings', []);
        // Create a new instance of MIA POS payment gateway
        $this->gateway = new WC_MIA_POS_Payment_Gateway();
    }

    /**
     * Check if the payment gateway is active
     *
     * @return bool
     */
    public function is_active() 
    {
        // Return gateway availability status
        return $this->gateway->is_available();
    }

    /**
     * Register and return script handlers for the payment method
     *
     * @return array
     */
    public function get_payment_method_script_handles() 
    {
        // Register script for MIA POS integration
        wp_register_script(
            'mia-pos-blocks-integration',
            MIA_POS_PLUGIN_URL . 'checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
                'wc-blocks-checkout',
            ],
            MIA_POS_VERSION,
            true
        );

        // Add script localization
        wp_localize_script('mia-pos-blocks-integration', 'mia_pos_data', [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'icon' => $this->gateway->icon,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mia_pos_payment'),
        ]);

        // Set script translations if the function exists
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('mia-pos-blocks-integration');
        }

        // Return script handler
        return ['mia-pos-blocks-integration'];
    }

    /**
     * Returns payment method data
     *
     * @return array
     */
    public function get_payment_method_data() 
    {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'icon' => $this->gateway->icon,
            'supports' => [
                'products',
                'refunds',
                'checkout',
                '__experimentalDestructureToBlocksCheckout',
                'cart',
                'mini-cart',
            ],
            'paymentMethodId' => 'mia_pos',
            'placeOrderButtonLabel' => __('Pay with MIA POS', 'mia-pos'),
            'showSavedCards' => false,
            'showSaveOption' => false,
            'requiresManualSubmit' => true,
        ];
    }
} 