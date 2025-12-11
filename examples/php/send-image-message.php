<?php
/**
 * Contoh Kode: Mengirim Pesan Image
 * 
 * File ini berisi contoh implementasi untuk mengirim pesan gambar melalui WhatsApp
 * 
 * Catatan: URL gambar harus dapat diakses secara publik (public URL)
 */

require_once 'config.php';

echo "=== WACloud - Send Image Message Example ===\n\n";

// Device ID yang sudah terhubung (status: connected)
$deviceId = '550e8400-e29b-41d4-a716-446655440000';

// Nomor tujuan (format: tanpa leading 0, dengan kode negara)
$to = '6281234567890';

// URL gambar yang dapat diakses publik
$imageUrl = 'https://example.com/image.jpg';

// Data pesan gambar
$messageData = [
    'device_id' => $deviceId,
    'to' => $to,
    'message_type' => 'image',
    'image_url' => $imageUrl,
    'caption' => 'Ini adalah caption untuk gambar' // Optional
];

echo "Mengirim pesan gambar...\n";
echo "To: {$to}\n";
echo "Image URL: {$imageUrl}\n";
echo "Caption: {$messageData['caption']}\n\n";

$response = makeRequest('POST', '/messages', $messageData);

displayResponse($response);

if ($response['success'] && isset($response['data']['message_id'])) {
    echo "✓ Pesan gambar berhasil dikirim!\n";
    echo "Message ID: {$response['data']['message_id']}\n";
    echo "Status: {$response['data']['status']}\n";
} else {
    echo "✗ Gagal mengirim pesan gambar.\n";
    if (isset($response['error'])) {
        echo "Error: {$response['error']}\n";
    }
    if (isset($response['message'])) {
        echo "Message: {$response['message']}\n";
    }
    
    echo "\nTips:\n";
    echo "- Pastikan URL gambar dapat diakses secara publik\n";
    echo "- Pastikan format gambar didukung (JPG, PNG, GIF, dll)\n";
    echo "- Pastikan device dalam status 'connected'\n";
}

echo "\n=== Selesai ===\n";

