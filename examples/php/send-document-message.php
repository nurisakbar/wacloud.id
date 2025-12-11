<?php
/**
 * Contoh Kode: Mengirim Pesan Document (PDF, DOC, dll)
 * 
 * File ini berisi contoh implementasi untuk mengirim dokumen melalui WhatsApp
 * 
 * Catatan: URL dokumen harus dapat diakses secara publik (public URL)
 */

require_once 'config.php';

echo "=== WACloud - Send Document Message Example ===\n\n";

// Device ID yang sudah terhubung (status: connected)
$deviceId = '550e8400-e29b-41d4-a716-446655440000';

// Nomor tujuan (format: tanpa leading 0, dengan kode negara)
$to = '6281234567890';

// URL dokumen yang dapat diakses publik
$documentUrl = 'https://example.com/document.pdf';

// Nama file (akan ditampilkan di WhatsApp)
$filename = 'document.pdf';

// Data pesan dokumen
$messageData = [
    'device_id' => $deviceId,
    'to' => $to,
    'message_type' => 'document',
    'document_url' => $documentUrl,
    'filename' => $filename,
    'caption' => 'Ini adalah caption untuk dokumen' // Optional
];

echo "Mengirim pesan dokumen...\n";
echo "To: {$to}\n";
echo "Document URL: {$documentUrl}\n";
echo "Filename: {$filename}\n";
echo "Caption: {$messageData['caption']}\n\n";

$response = makeRequest('POST', '/messages', $messageData);

displayResponse($response);

if ($response['success'] && isset($response['data']['message_id'])) {
    echo "✓ Pesan dokumen berhasil dikirim!\n";
    echo "Message ID: {$response['data']['message_id']}\n";
    echo "Status: {$response['data']['status']}\n";
} else {
    echo "✗ Gagal mengirim pesan dokumen.\n";
    if (isset($response['error'])) {
        echo "Error: {$response['error']}\n";
    }
    if (isset($response['message'])) {
        echo "Message: {$response['message']}\n";
    }
    
    echo "\nTips:\n";
    echo "- Pastikan URL dokumen dapat diakses secara publik\n";
    echo "- Format dokumen yang didukung: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, dll\n";
    echo "- Pastikan device dalam status 'connected'\n";
    echo "- Ukuran file maksimal sesuai limit WhatsApp (biasanya 100MB)\n";
}

echo "\n=== Selesai ===\n";

