/**
 * Contoh Kode: Mengirim Pesan Text
 * 
 * File ini berisi contoh implementasi untuk mengirim pesan text melalui WhatsApp
 */

const { makeRequest, displayResponse } = require('./config');

async function main() {
    console.log('=== WACloud - Send Text Message Example ===\n\n');

    // Device ID yang sudah terhubung (status: connected)
    const deviceId = '550e8400-e29b-41d4-a716-446655440000';

    // Nomor tujuan (format: tanpa leading 0, dengan kode negara)
    // Contoh: 6281234567890 (Indonesia)
    const to = '6281234567890';

    // Data pesan
    const messageData = {
        device_id: deviceId,
        to: to,
        message_type: 'text',
        text: 'Halo, ini pesan dari API WACloud!'
    };

    console.log('Mengirim pesan text...');
    console.log(`To: ${to}`);
    console.log(`Message: ${messageData.text}\n`);

    const response = await makeRequest('POST', '/messages', messageData);
    displayResponse(response);

    if (response.success && response.data?.message_id) {
        console.log('✓ Pesan berhasil dikirim!');
        console.log(`Message ID: ${response.data.message_id}`);
        console.log(`WhatsApp Message ID: ${response.data.whatsapp_message_id}`);
        console.log(`Status: ${response.data.status}`);
    } else {
        console.log('✗ Gagal mengirim pesan.');
        if (response.error) {
            console.log(`Error: ${response.error}`);
        }
        if (response.message) {
            console.log(`Message: ${response.message}`);
        }
    }

    console.log('\n=== Selesai ===');
}

main().catch(console.error);

