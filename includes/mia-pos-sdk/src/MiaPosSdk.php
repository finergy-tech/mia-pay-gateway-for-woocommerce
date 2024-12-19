<?php
require_once 'MiaPosApiClient.php';
require_once 'MiaPosAuthClient.php';

class MiaPosSdk
{
    private $apiClient;
    private $authClient;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->authClient = new MiaPosAuthClient($config);
        $this->apiClient = new MiaPosApiClient($config);
    }

    public function createPayment($paymentData)
    {
        $token = $this->getAccessToken();
        return $this->apiClient->createPayment($token, $paymentData);
    }

    public function getPaymentStatus($paymentId)
    {
        $token = $this->getAccessToken();
        return $this->apiClient->getPaymentStatus($token, $paymentId);
    }

    public function verifySignature($result_str, $signature)
    {
        $token = $this->getAccessToken();
        $publicKey= $this->apiClient->getPublicKey($token);

        if (!isset($publicKey)) {
            throw new Exception('Public key is missing in the response: ' . $publicKey);
        }


        $publicKeyPem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($publicKey, 64, "\n") . "-----END PUBLIC KEY-----";
        $publicKeyResource = openssl_pkey_get_public($publicKeyPem);

        if ($publicKeyResource === false) {
            throw new Exception('Failed to parse the public key.');
        }

        $decodedSignature = base64_decode($signature);
        if ($decodedSignature === false) {
            throw new Exception('Failed to decode the signature.');
        }

        $verified = openssl_verify(
            $result_str,
            $decodedSignature,
            $publicKeyResource,
            OPENSSL_ALGO_SHA256
        );

        openssl_free_key($publicKeyResource);

        return $verified === 1;
    }

    private function getAccessToken()
    {
        return $this->authClient->getAccessToken();
    }
} 