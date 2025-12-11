/**
 * Contoh Kode: Mengirim Pesan Image
 * 
 * File ini berisi contoh implementasi untuk mengirim pesan gambar melalui WhatsApp
 * 
 * Catatan: URL gambar harus dapat diakses secara publik (public URL)
 */

const { makeRequest, displayResponse } = require('./config');

async function main() {
    console.log('=== WACloud - Send Image Message Example ===\n\n');

    // Device ID yang sudah terhubung (status: connected)
    const deviceId = '550e8400-e29b-41d4-a716-446655440000';

    // Nomor tujuan (format: tanpa leading 0, dengan kode negara)
    const to = '6281234567890';

    // URL gambar yang dapat diakses publik
    const imageUrl = 'https://example.com/image.jpg';

    // Data pesan gambar
    const messageData = {
        device_id: deviceId,
        to: to,
        message_type: 'image',
        image_url: imageUrl,
        caption: 'Ini adalah caption untuk gambar' // Optional
    };

    console.log('Mengirim pesan gambar...');
    console.log(`To: ${to}`);
    console.log(`Image URL: ${imageUrl}`);
    console.log(`Caption: ${messageData.caption}\n`);

    const response = await makeRequest('POST', '/messages', messageData);
    displayResponse(response);

    if (response.success && response.data?.message_id) {
        console.log('✓ Pesan gambar berhasil dikirim!');
        console.log(`Message ID: ${response.data.message_id}`);
        console.log(`Status: ${response.data.status}`);
    } else {
        console.log('✗ Gagal mengirim pesan gambar.');
        if (response.error) {
            console.log(`Error: ${response.error}`);
        }
        if (response.message) {
            console.log(`Message: ${response.message}`);
        }

        console.log('\nTips:');
        console.log('- Pastikan URL gambar dapat diakses secara publik');
        console.log('- Pastikan format gambar didukung (JPG, PNG, GIF, dll)');
        console.log('- Pastikan device dalam status \'connected\'');
    }

    console.log('\n=== Selesai ===');
}

main().catch(console.error);

