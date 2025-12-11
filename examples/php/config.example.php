<?php
/**
 * Contoh File Konfigurasi WACloud API
 * 
 * Copy file ini menjadi config.php dan isi dengan API Key Anda
 * 
 * JANGAN commit file config.php ke repository!
 */

// API Key Anda dari dashboard
define('API_KEY', 'YOUR_API_KEY');

// Base URL API
define('BASE_URL', 'https://app.wacloud.id/api/v1');

/**
 * Helper function untuk membuat HTTP request
 */
function makeRequest($method, $endpoint, $data = null) {
    $url = BASE_URL . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Api-Key: ' . API_KEY,
        'Content-Type: application/json'
    ]);
    
    switch (strtoupper($method)) {
        case 'GET':
            if ($data) {
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
            break;
            
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
            
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
            
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => $error
        ];
    }
    
    $result = json_decode($response, true);
    $result['http_code'] = $httpCode;
    
    return $result;
}

/**
 * Helper function untuk menampilkan response
 */
function displayResponse($response) {
    echo "HTTP Code: " . ($response['http_code'] ?? 'N/A') . "\n";
    echo "Success: " . ($response['success'] ? 'Yes' : 'No') . "\n";
    
    if (isset($response['message'])) {
        echo "Message: " . $response['message'] . "\n";
    }
    
    if (isset($response['data'])) {
        echo "Data:\n";
        print_r($response['data']);
    }
    
    if (isset($response['error'])) {
        echo "Error: " . $response['error'] . "\n";
    }
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

