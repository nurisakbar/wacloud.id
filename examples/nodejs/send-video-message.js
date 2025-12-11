/**
 * Contoh Kode: Mengirim Pesan Video
 * 
 * File ini berisi contoh implementasi untuk mengirim video melalui WhatsApp
 * 
 * Catatan: URL video harus dapat diakses secara publik (public URL)
 */

const { makeRequest, displayResponse } = require('./config');

async function main() {
    console.log('=== WACloud - Send Video Message Example ===\n\n');

    // Device ID yang sudah terhubung (status: connected)
    const deviceId = '550e8400-e29b-41d4-a716-446655440000';

    // Nomor tujuan (format: tanpa leading 0, dengan kode negara)
    const to = '6281234567890';

    // URL video yang dapat diakses publik
    const videoUrl = 'https://example.com/video.mp4';

    // Data pesan video
    const messageData = {
        device_id: deviceId,
        to: to,
        message_type: 'video',
        video_url: videoUrl,
        caption: 'Ini adalah caption untuk video', // Optional
        as_note: false, // Optional: kirim sebagai video note (lingkaran)
        convert: false // Optional: konversi video jika diperlukan
    };

    console.log('Mengirim pesan video...');
    console.log(`To: ${to}`);
    console.log(`Video URL: ${videoUrl}`);
    console.log(`Caption: ${messageData.caption}`);
    console.log(`As Note: ${messageData.as_note ? 'Yes' : 'No'}`);
    console.log(`Convert: ${messageData.convert ? 'Yes' : 'No'}\n`);

    const response = await makeRequest('POST', '/messages', messageData);
    displayResponse(response);

    if (response.success && response.data?.message_id) {
        console.log('✓ Pesan video berhasil dikirim!');
        console.log(`Message ID: ${response.data.message_id}`);
        console.log(`Status: ${response.data.status}`);
    } else {
        console.log('✗ Gagal mengirim pesan video.');
        if (response.error) {
            console.log(`Error: ${response.error}`);
        }
        if (response.message) {
            console.log(`Message: ${response.message}`);
        }

        console.log('\nTips:');
        console.log('- Pastikan URL video dapat diakses secara publik');
        console.log('- Format video yang didukung: MP4, AVI, MOV, dll');
        console.log('- Pastikan device dalam status \'connected\'');
        console.log('- Ukuran file maksimal sesuai limit WhatsApp (biasanya 64MB untuk video biasa, 16MB untuk video note)');
        console.log('- Durasi video note maksimal 60 detik');
        console.log('- Set \'as_note\' ke true untuk mengirim sebagai video note (lingkaran)');
    }

    console.log('\n=== Selesai ===');
}

main().catch(console.error);

