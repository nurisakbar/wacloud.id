<?php

namespace WACloud\LaravelWACloud\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getDevices()
 * @method static array getDevice(string $deviceId)
 * @method static array createDevice(string $name, string $phoneNumber)
 * @method static array getDeviceStatus(string $deviceId)
 * @method static array getDeviceQrCode(string $deviceId)
 * @method static array deleteDevice(string $deviceId)
 * @method static array sendText(string $deviceId, string $to, string $text)
 * @method static array sendImage(string $deviceId, string $to, string $imageUrl, string|null $caption = null)
 * @method static array sendVideo(string $deviceId, string $to, string $videoUrl, string|null $caption = null, bool $asNote = false, bool $convert = false)
 * @method static array sendDocument(string $deviceId, string $to, string $documentUrl, string $filename, string|null $caption = null)
 * @method static array sendMessage(array $messageData)
 * @method static array get(string $endpoint, array $params = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static array put(string $endpoint, array $data = [])
 * @method static array delete(string $endpoint)
 * @method static \WACloud\LaravelWACloud\WACloudClient setApiKey(string $apiKey)
 * @method static \WACloud\LaravelWACloud\WACloudClient setBaseUrl(string $baseUrl)
 * @method static \WACloud\LaravelWACloud\WACloudClient setTimeout(int $timeout)
 *
 * @see \WACloud\LaravelWACloud\WACloudClient
 */
class WACloud extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'wacloud';
    }
}

