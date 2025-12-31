<?php

namespace WACloud\LaravelWACloud;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;

class WACloudClient
{
    /**
     * HTTP Client instance
     *
     * @var Client
     */
    protected $client;

    /**
     * API Key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Base URL
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Request timeout in seconds
     *
     * @var int
     */
    protected $timeout;

    /**
     * Create a new WACloud client instance.
     *
     * @param string $apiKey
     * @param string $baseUrl
     * @param int $timeout
     */
    public function __construct($apiKey, $baseUrl = 'https://app.wacloud.id/api/v1', $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * Set API Key
     *
     * @param string $apiKey
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => $this->timeout,
        ]);

        return $this;
    }

    /**
     * Set Base URL
     *
     * @param string $baseUrl
     * @return $this
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => $this->timeout,
        ]);

        return $this;
    }

    /**
     * Set timeout
     *
     * @param int $timeout
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => $this->timeout,
        ]);

        return $this;
    }

    /**
     * Make GET request
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    public function get($endpoint, $params = [])
    {
        return $this->request('GET', $endpoint, ['query' => $params]);
    }

    /**
     * Make POST request
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function post($endpoint, $data = [])
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Make PUT request
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function put($endpoint, $data = [])
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * Make DELETE request
     *
     * @param string $endpoint
     * @return array
     */
    public function delete($endpoint)
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Make HTTP request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return array
     */
    protected function request($method, $endpoint, $options = [])
    {
        try {
            $endpoint = ltrim($endpoint, '/');
            $response = $this->client->request($method, $endpoint, $options);

            $body = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => true,
                'http_code' => $response->getStatusCode(),
                'data' => $body['data'] ?? $body,
                'message' => $body['message'] ?? null,
                'raw' => $body,
            ];
        } catch (GuzzleException $e) {
            $response = $e->hasResponse() ? $e->getResponse() : null;
            $statusCode = $response ? $response->getStatusCode() : 500;
            $body = $response ? json_decode($response->getBody()->getContents(), true) : null;

            return [
                'success' => false,
                'http_code' => $statusCode,
                'error' => $body['error'] ?? $e->getMessage(),
                'message' => $body['message'] ?? null,
                'raw' => $body,
            ];
        }
    }

    // ============================================
    // Device Management Methods
    // ============================================

    /**
     * Get all devices
     *
     * @return array
     */
    public function getDevices()
    {
        return $this->get('/devices');
    }

    /**
     * Get device by ID
     *
     * @param string $deviceId
     * @return array
     */
    public function getDevice($deviceId)
    {
        return $this->get("/devices/{$deviceId}");
    }

    /**
     * Create new device
     *
     * @param string $name
     * @param string $phoneNumber
     * @return array
     */
    public function createDevice($name, $phoneNumber)
    {
        return $this->post('/devices', [
            'name' => $name,
            'phone_number' => $phoneNumber,
        ]);
    }

    /**
     * Get device status
     *
     * @param string $deviceId
     * @return array
     */
    public function getDeviceStatus($deviceId)
    {
        return $this->get("/devices/{$deviceId}/status");
    }

    /**
     * Get QR code for device pairing
     *
     * @param string $deviceId
     * @return array
     */
    public function getDeviceQrCode($deviceId)
    {
        return $this->get("/devices/{$deviceId}/pair");
    }

    /**
     * Delete device
     *
     * @param string $deviceId
     * @return array
     */
    public function deleteDevice($deviceId)
    {
        return $this->delete("/devices/{$deviceId}");
    }

    // ============================================
    // Message Methods
    // ============================================

    /**
     * Send text message
     *
     * @param string $deviceId
     * @param string $to
     * @param string $text
     * @return array
     */
    public function sendText($deviceId, $to, $text)
    {
        return $this->post('/messages', [
            'device_id' => $deviceId,
            'to' => $to,
            'message_type' => 'text',
            'text' => $text,
        ]);
    }

    /**
     * Send image message
     *
     * @param string $deviceId
     * @param string $to
     * @param string $imageUrl
     * @param string|null $caption
     * @return array
     */
    public function sendImage($deviceId, $to, $imageUrl, $caption = null)
    {
        $data = [
            'device_id' => $deviceId,
            'to' => $to,
            'message_type' => 'image',
            'image_url' => $imageUrl,
        ];

        if ($caption) {
            $data['caption'] = $caption;
        }

        return $this->post('/messages', $data);
    }

    /**
     * Send video message
     *
     * @param string $deviceId
     * @param string $to
     * @param string $videoUrl
     * @param string|null $caption
     * @param bool $asNote
     * @param bool $convert
     * @return array
     */
    public function sendVideo($deviceId, $to, $videoUrl, $caption = null, $asNote = false, $convert = false)
    {
        $data = [
            'device_id' => $deviceId,
            'to' => $to,
            'message_type' => 'video',
            'video_url' => $videoUrl,
            'as_note' => $asNote,
            'convert' => $convert,
        ];

        if ($caption) {
            $data['caption'] = $caption;
        }

        return $this->post('/messages', $data);
    }

    /**
     * Send document message
     *
     * @param string $deviceId
     * @param string $to
     * @param string $documentUrl
     * @param string $filename
     * @param string|null $caption
     * @return array
     */
    public function sendDocument($deviceId, $to, $documentUrl, $filename, $caption = null)
    {
        $data = [
            'device_id' => $deviceId,
            'to' => $to,
            'message_type' => 'document',
            'document_url' => $documentUrl,
            'filename' => $filename,
        ];

        if ($caption) {
            $data['caption'] = $caption;
        }

        return $this->post('/messages', $data);
    }

    /**
     * Send custom message
     *
     * @param array $messageData
     * @return array
     */
    public function sendMessage($messageData)
    {
        return $this->post('/messages', $messageData);
    }
}

