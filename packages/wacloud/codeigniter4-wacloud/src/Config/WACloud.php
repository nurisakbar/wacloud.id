<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * WACloud Configuration
 * 
 * Konfigurasi untuk WACloud WhatsApp Gateway API
 */
class WACloud extends BaseConfig
{
    /**
     * WACloud API Key
     * 
     * API Key Anda dari dashboard WACloud.
     * Dapatkan di: https://app.wacloud.id
     * 
     * @var string
     */
    public string $apiKey = '';

    /**
     * WACloud Base URL
     * 
     * Base URL untuk WACloud API.
     * Default: https://app.wacloud.id/api/v1
     * 
     * @var string
     */
    public string $baseUrl = 'https://app.wacloud.id/api/v1';

    /**
     * Request Timeout
     * 
     * Timeout untuk HTTP request dalam detik.
     * 
     * @var int
     */
    public int $timeout = 30;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Load from environment variables if available
        $this->apiKey = getenv('WACLOUD_API_KEY') ?: $this->apiKey;
        $this->baseUrl = getenv('WACLOUD_BASE_URL') ?: $this->baseUrl;
        $this->timeout = (int)(getenv('WACLOUD_TIMEOUT') ?: $this->timeout);
    }
}

