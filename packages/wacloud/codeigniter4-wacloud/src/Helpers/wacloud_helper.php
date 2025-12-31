<?php

/**
 * WACloud Helper Functions
 * 
 * Helper functions untuk kemudahan penggunaan WACloud library
 */

if (!function_exists('wacloud')) {
    /**
     * Get WACloud library instance
     *
     * @return \WACloud\CodeIgniter4WACloud\Libraries\WACloud
     */
    function wacloud()
    {
        // Try to get from service first
        if (function_exists('service') && service('wacloud', false)) {
            return service('wacloud');
        }
        
        // Fallback to direct instantiation
        return new \WACloud\CodeIgniter4WACloud\Libraries\WACloud();
    }
}

if (!function_exists('wacloud_send_text')) {
    /**
     * Send text message via WACloud
     *
     * @param string $deviceId
     * @param string $to
     * @param string $text
     * @return array
     */
    function wacloud_send_text($deviceId, $to, $text)
    {
        return wacloud()->sendText($deviceId, $to, $text);
    }
}

if (!function_exists('wacloud_send_image')) {
    /**
     * Send image message via WACloud
     *
     * @param string $deviceId
     * @param string $to
     * @param string $imageUrl
     * @param string|null $caption
     * @return array
     */
    function wacloud_send_image($deviceId, $to, $imageUrl, $caption = null)
    {
        return wacloud()->sendImage($deviceId, $to, $imageUrl, $caption);
    }
}

if (!function_exists('wacloud_send_video')) {
    /**
     * Send video message via WACloud
     *
     * @param string $deviceId
     * @param string $to
     * @param string $videoUrl
     * @param string|null $caption
     * @param bool $asNote
     * @param bool $convert
     * @return array
     */
    function wacloud_send_video($deviceId, $to, $videoUrl, $caption = null, $asNote = false, $convert = false)
    {
        return wacloud()->sendVideo($deviceId, $to, $videoUrl, $caption, $asNote, $convert);
    }
}

if (!function_exists('wacloud_send_document')) {
    /**
     * Send document message via WACloud
     *
     * @param string $deviceId
     * @param string $to
     * @param string $documentUrl
     * @param string $filename
     * @param string|null $caption
     * @return array
     */
    function wacloud_send_document($deviceId, $to, $documentUrl, $filename, $caption = null)
    {
        return wacloud()->sendDocument($deviceId, $to, $documentUrl, $filename, $caption);
    }
}

