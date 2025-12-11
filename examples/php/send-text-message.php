<?php
/**
 * Contoh Kode: Mengirim Pesan Text
 * 
 * File ini berisi contoh implementasi untuk mengirim pesan text melalui WhatsApp
 */

require_once 'config.php';

echo "=== WACloud - Send Text Message Example ===\n\n";

// Device ID yang sudah terhubung (status: connected)
$deviceId = '550e8400-e29b-41d4-a716-446655440000';

// Nomor tujuan (format: tanpa leading 0, dengan kode negara)
// Contoh: 6281234567890 (Indonesia)
$to = '6281234567890';

// Data pesan
$messageData = [
    'device_id' => $deviceId,
    'to' => $to,
    'message_type' => 'text',
    'text' => 'Halo, ini pesan dari API WACloud!'
];

echo "Mengirim pesan text...\n";
echo "To: {$to}\n";
echo "Message: {$messageData['text']}\n\n";

$response = makeRequest('POST', '/messages', $messageData);

displayResponse($response);

if ($response['success'] && isset($response['data']['message_id'])) {
    echo "✓ Pesan berhasil dikirim!\n";
    echo "Message ID: {$response['data']['message_id']}\n";
    echo "WhatsApp Message ID: {$response['data']['whatsapp_message_id']}\n";
    echo "Status: {$response['data']['status']}\n";
} else {
    echo "✗ Gagal mengirim pesan.\n";
    if (isset($response['error'])) {
        echo "Error: {$response['error']}\n";
    }
    if (isset($response['message'])) {
        echo "Message: {$response['message']}\n";
    }
}

echo "\n=== Selesai ===\n";

