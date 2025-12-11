<?php
/**
 * Contoh Kode: Mengirim Pesan Video
 * 
 * File ini berisi contoh implementasi untuk mengirim video melalui WhatsApp
 * 
 * Catatan: URL video harus dapat diakses secara publik (public URL)
 */

require_once 'config.php';

echo "=== WACloud - Send Video Message Example ===\n\n";

// Device ID yang sudah terhubung (status: connected)
$deviceId = '550e8400-e29b-41d4-a716-446655440000';

// Nomor tujuan (format: tanpa leading 0, dengan kode negara)
$to = '6281234567890';

// URL video yang dapat diakses publik
$videoUrl = 'https://example.com/video.mp4';

// Data pesan video
$messageData = [
    'device_id' => $deviceId,
    'to' => $to,
    'message_type' => 'video',
    'video_url' => $videoUrl,
    'caption' => 'Ini adalah caption untuk video', // Optional
    'as_note' => false, // Optional: kirim sebagai video note (lingkaran)
    'convert' => false // Optional: konversi video jika diperlukan
];

echo "Mengirim pesan video...\n";
echo "To: {$to}\n";
echo "Video URL: {$videoUrl}\n";
echo "Caption: {$messageData['caption']}\n";
echo "As Note: " . ($messageData['as_note'] ? 'Yes' : 'No') . "\n";
echo "Convert: " . ($messageData['convert'] ? 'Yes' : 'No') . "\n\n";

$response = makeRequest('POST', '/messages', $messageData);

displayResponse($response);

if ($response['success'] && isset($response['data']['message_id'])) {
    echo "✓ Pesan video berhasil dikirim!\n";
    echo "Message ID: {$response['data']['message_id']}\n";
    echo "Status: {$response['data']['status']}\n";
} else {
    echo "✗ Gagal mengirim pesan video.\n";
    if (isset($response['error'])) {
        echo "Error: {$response['error']}\n";
    }
    if (isset($response['message'])) {
        echo "Message: {$response['message']}\n";
    }
    
    echo "\nTips:\n";
    echo "- Pastikan URL video dapat diakses secara publik\n";
    echo "- Format video yang didukung: MP4, AVI, MOV, dll\n";
    echo "- Pastikan device dalam status 'connected'\n";
    echo "- Ukuran file maksimal sesuai limit WhatsApp (biasanya 64MB untuk video biasa, 16MB untuk video note)\n";
    echo "- Durasi video note maksimal 60 detik\n";
    echo "- Set 'as_note' ke true untuk mengirim sebagai video note (lingkaran)\n";
}

echo "\n=== Selesai ===\n";

