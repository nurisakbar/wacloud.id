/**
 * Contoh Kode: Mengirim Pesan Document (PDF, DOC, dll)
 * 
 * File ini berisi contoh implementasi untuk mengirim dokumen melalui WhatsApp
 * 
 * Catatan: URL dokumen harus dapat diakses secara publik (public URL)
 */

const { makeRequest, displayResponse } = require('./config');

async function main() {
    console.log('=== WACloud - Send Document Message Example ===\n\n');

    // Device ID yang sudah terhubung (status: connected)
    const deviceId = '550e8400-e29b-41d4-a716-446655440000';

    // Nomor tujuan (format: tanpa leading 0, dengan kode negara)
    const to = '6281234567890';

    // URL dokumen yang dapat diakses publik
    const documentUrl = 'https://example.com/document.pdf';

    // Nama file (akan ditampilkan di WhatsApp)
    const filename = 'document.pdf';

    // Data pesan dokumen
    const messageData = {
        device_id: deviceId,
        to: to,
        message_type: 'document',
        document_url: documentUrl,
        filename: filename,
        caption: 'Ini adalah caption untuk dokumen' // Optional
    };

    console.log('Mengirim pesan dokumen...');
    console.log(`To: ${to}`);
    console.log(`Document URL: ${documentUrl}`);
    console.log(`Filename: ${filename}`);
    console.log(`Caption: ${messageData.caption}\n`);

    const response = await makeRequest('POST', '/messages', messageData);
    displayResponse(response);

    if (response.success && response.data?.message_id) {
        console.log('✓ Pesan dokumen berhasil dikirim!');
        console.log(`Message ID: ${response.data.message_id}`);
        console.log(`Status: ${response.data.status}`);
    } else {
        console.log('✗ Gagal mengirim pesan dokumen.');
        if (response.error) {
            console.log(`Error: ${response.error}`);
        }
        if (response.message) {
            console.log(`Message: ${response.message}`);
        }

        console.log('\nTips:');
        console.log('- Pastikan URL dokumen dapat diakses secara publik');
        console.log('- Format dokumen yang didukung: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, dll');
        console.log('- Pastikan device dalam status \'connected\'');
        console.log('- Ukuran file maksimal sesuai limit WhatsApp (biasanya 100MB)');
    }

    console.log('\n=== Selesai ===');
}

main().catch(console.error);

