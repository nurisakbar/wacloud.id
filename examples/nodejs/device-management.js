/**
 * Contoh Kode: Device Management (Session Management)
 * 
 * File ini berisi contoh implementasi untuk mengelola device/session WhatsApp
 * termasuk membuat device baru, mendapatkan QR code, dan mengecek status device.
 */

const { makeRequest, displayResponse } = require('./config');

async function main() {
    console.log('=== WACloud - Device Management Examples ===\n\n');

    // ============================================
    // 1. Membuat Device Baru
    // ============================================
    console.log('1. Membuat Device Baru');
    console.log('-'.repeat(50));

    const deviceData = {
        name: 'Device Utama',
        phone_number: '81234567890' // Format: tanpa leading 0, 9-13 digit
    };

    const response = await makeRequest('POST', '/devices', deviceData);
    displayResponse(response);

    if (response.success && response.data?.id) {
        const deviceId = response.data.id;
        console.log(`Device ID: ${deviceId}`);
        console.log(`Status: ${response.data.status}\n\n`);

        // ============================================
        // 2. Mendapatkan QR Code untuk Pairing
        // ============================================
        console.log('2. Mendapatkan QR Code untuk Pairing');
        console.log('-'.repeat(50));

        const qrResponse = await makeRequest('GET', `/devices/${deviceId}/pair`);
        displayResponse(qrResponse);

        if (qrResponse.success && qrResponse.data?.qr_code) {
            const qrCode = qrResponse.data.qr_code;
            const expiresAt = qrResponse.data.expires_at || 'N/A';

            console.log(`QR Code (Base64): ${qrCode.substring(0, 50)}...`);
            console.log(`Expires At: ${expiresAt}`);
            console.log('\nUntuk menampilkan QR code di browser:');
            console.log(`<img src='${qrCode}' alt='QR Code' />\n\n`);
        }

        // ============================================
        // 3. Mendapatkan Status Device
        // ============================================
        console.log('3. Mendapatkan Status Device');
        console.log('-'.repeat(50));

        const statusResponse = await makeRequest('GET', `/devices/${deviceId}/status`);
        displayResponse(statusResponse);

        // ============================================
        // 4. Mendapatkan Detail Device
        // ============================================
        console.log('4. Mendapatkan Detail Device');
        console.log('-'.repeat(50));

        const detailResponse = await makeRequest('GET', `/devices/${deviceId}`);
        displayResponse(detailResponse);
    } else {
        console.log('Gagal membuat device. Pastikan API_KEY sudah benar.\n');
    }

    // ============================================
    // 5. Mendapatkan Daftar Semua Device
    // ============================================
    console.log('5. Mendapatkan Daftar Semua Device');
    console.log('-'.repeat(50));

    const devicesResponse = await makeRequest('GET', '/devices');
    displayResponse(devicesResponse);

    if (devicesResponse.success && Array.isArray(devicesResponse.data)) {
        console.log(`Total Devices: ${devicesResponse.data.length}`);
        devicesResponse.data.forEach(device => {
            console.log(`- ${device.name} (ID: ${device.id}, Status: ${device.status})`);
        });
    }

    console.log('\n=== Selesai ===');
}

main().catch(console.error);

