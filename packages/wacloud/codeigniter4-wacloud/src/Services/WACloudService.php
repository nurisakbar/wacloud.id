<?php

namespace WACloud\CodeIgniter4WACloud\Services;

use WACloud\CodeIgniter4WACloud\Libraries\WACloud;
use Config\WACloud as WACloudConfig;

/**
 * WACloud Service
 * 
 * Service untuk mendaftarkan WACloud library ke CodeIgniter 4
 */
class WACloudService
{
    /**
     * Get WACloud library instance
     *
     * @param WACloudConfig|null $config
     * @return WACloud
     */
    public static function getInstance(WACloudConfig $config = null)
    {
        return new WACloud($config);
    }
}

