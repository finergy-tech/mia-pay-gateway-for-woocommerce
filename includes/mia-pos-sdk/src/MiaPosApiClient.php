<?php
class MiaPosApiClient {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    public function createPayment($token, $paymentData) {
        $response = wp_remote_post($this->config['baseUrl'] . '/ecomm/api/v1/pay', [
            'body' => json_encode($paymentData),
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function getPaymentStatus($token, $paymentId) {
        $response = wp_remote_get($this->config['baseUrl'] . '/ecomm/api/v1/payment/' . $paymentId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function getPublicKey($token) {
        $response = wp_remote_get($this->config['baseUrl'] . '/ecomm/api/v1/public-key', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['publicKey'];
    }
} 