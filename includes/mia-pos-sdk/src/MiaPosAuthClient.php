<?php
class MiaPosAuthClient {
    private $config;
    private $accessToken;
    private $refreshToken;
    private $accessExpireTime;

    public function __construct($config) {
        $this->config = $config;
    }

    public function getAccessToken() {
        if ($this->accessToken && !$this->isTokenExpired()) {
            return $this->accessToken;
        }

        if ($this->refreshToken) {
            try {
                return $this->refreshAccessToken();
            } catch (Exception $e) {
                error_log('Mia pos refresh token failed: ' . $e->getMessage());
            }
        }

        return $this->generateNewTokens();
    }

    private function generateNewTokens() {
        $response = wp_remote_post($this->config['baseUrl'] . '/ecomm/api/v1/token', [
            'body' => json_encode([
                'merchantId' => $this->config['merchantId'],
                'secretKey' => $this->config['secretKey']
            ]),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        $this->accessToken = $body['accessToken'];
        $this->refreshToken = $body['refreshToken'];
        $this->accessExpireTime = time() + (isset($body['accessTokenExpiresIn']) ? $body['accessTokenExpiresIn'] : 0) - 10;

        return $this->accessToken;
    }

    private function refreshAccessToken() {
        $response = wp_remote_post($this->config['baseUrl'] . '/ecomm/api/v1/token/refresh', [
            'body' => json_encode([
                'refreshToken' => $this->refreshToken
            ]),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        $this->accessToken = $body['accessToken'];
        $this->refreshToken = $body['refreshToken'];
        $this->accessExpireTime = time() + (isset($body['accessTokenExpiresIn']) ? $body['accessTokenExpiresIn'] : 0) - 10;

        return $this->accessToken;
    }

    private function isTokenExpired() {
        return !$this->accessExpireTime || time() >= $this->accessExpireTime;
    }
} 