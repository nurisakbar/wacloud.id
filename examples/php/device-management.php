<?php
/**
 * Contoh Kode: Device Management (Session Management)
 * 
 * File ini berisi contoh implementasi untuk mengelola device/session WhatsApp
 * termasuk membuat device baru, mendapatkan QR code, dan mengecek status device.
 */

require_once 'config.php';

echo "=== WACloud - Device Management Examples ===\n\n";

// ============================================
// 1. Membuat Device Baru
// ============================================
echo "1. Membuat Device Baru\n";
echo str_repeat('-', 50) . "\n";

$deviceData = [
    'name' => 'Device Utama',
    'phone_number' => '81234567890' // Format: tanpa leading 0, 9-13 digit
];

$response = makeRequest('POST', '/devices', $deviceData);
displayResponse($response);

if ($response['success'] && isset($response['data']['id'])) {
    $deviceId = $response['data']['id'];
    echo "Device ID: {$deviceId}\n";
    echo "Status: {$response['data']['status']}\n\n";
    
    // ============================================
    // 2. Mendapatkan QR Code untuk Pairing
    // ============================================
    echo "2. Mendapatkan QR Code untuk Pairing\n";
    echo str_repeat('-', 50) . "\n";
    
    $qrResponse = makeRequest('GET', "/devices/{$deviceId}/pair");
    displayResponse($qrResponse);
    
    if ($qrResponse['success'] && isset($qrResponse['data']['qr_code'])) {
        $qrCode = $qrResponse['data']['qr_code'];
        $expiresAt = $qrResponse['data']['expires_at'] ?? 'N/A';
        
        echo "QR Code (Base64): " . substr($qrCode, 0, 50) . "...\n";
        echo "Expires At: {$expiresAt}\n";
        echo "\nUntuk menampilkan QR code di browser:\n";
        echo "<img src='{$qrCode}' alt='QR Code' />\n\n";
    }
    
    // ============================================
    // 3. Mendapatkan Status Device
    // ============================================
    echo "3. Mendapatkan Status Device\n";
    echo str_repeat('-', 50) . "\n";
    
    $statusResponse = makeRequest('GET', "/devices/{$deviceId}/status");
    displayResponse($statusResponse);
    
    // ============================================
    // 4. Mendapatkan Detail Device
    // ============================================
    echo "4. Mendapatkan Detail Device\n";
    echo str_repeat('-', 50) . "\n";
    
    $detailResponse = makeRequest('GET', "/devices/{$deviceId}");
    displayResponse($detailResponse);
} else {
    echo "Gagal membuat device. Pastikan API_KEY sudah benar.\n";
}

// ============================================
// 5. Mendapatkan Daftar Semua Device
// ============================================
echo "5. Mendapatkan Daftar Semua Device\n";
echo str_repeat('-', 50) . "\n";

$devicesResponse = makeRequest('GET', '/devices');
displayResponse($devicesResponse);

if ($devicesResponse['success'] && isset($devicesResponse['data'])) {
    echo "Total Devices: " . count($devicesResponse['data']) . "\n";
    foreach ($devicesResponse['data'] as $device) {
        echo "- {$device['name']} (ID: {$device['id']}, Status: {$device['status']})\n";
    }
}

echo "\n=== Selesai ===\n";

