"""
Contoh Kode: Device Management (Session Management)

File ini berisi contoh implementasi untuk mengelola device/session WhatsApp
termasuk membuat device baru, mendapatkan QR code, dan mengecek status device.
"""

from config import make_request, display_response


def main():
    print('=== WACloud - Device Management Examples ===\n\n')

    # ============================================
    # 1. Membuat Device Baru
    # ============================================
    print('1. Membuat Device Baru')
    print('-' * 50)

    device_data = {
        'name': 'Device Utama',
        'phone_number': '81234567890'  # Format: tanpa leading 0, 9-13 digit
    }

    response = make_request('POST', '/devices', device_data)
    display_response(response)

    if response.get('success') and response.get('data', {}).get('id'):
        device_id = response['data']['id']
        print(f"Device ID: {device_id}")
        print(f"Status: {response['data']['status']}\n\n")

        # ============================================
        # 2. Mendapatkan QR Code untuk Pairing
        # ============================================
        print('2. Mendapatkan QR Code untuk Pairing')
        print('-' * 50)

        qr_response = make_request('GET', f'/devices/{device_id}/pair')
        display_response(qr_response)

        if qr_response.get('success') and qr_response.get('data', {}).get('qr_code'):
            qr_code = qr_response['data']['qr_code']
            expires_at = qr_response['data'].get('expires_at', 'N/A')

            print(f"QR Code (Base64): {qr_code[:50]}...")
            print(f"Expires At: {expires_at}")
            print('\nUntuk menampilkan QR code di browser:')
            print(f"<img src='{qr_code}' alt='QR Code' />\n\n")

        # ============================================
        # 3. Mendapatkan Status Device
        # ============================================
        print('3. Mendapatkan Status Device')
        print('-' * 50)

        status_response = make_request('GET', f'/devices/{device_id}/status')
        display_response(status_response)

        # ============================================
        # 4. Mendapatkan Detail Device
        # ============================================
        print('4. Mendapatkan Detail Device')
        print('-' * 50)

        detail_response = make_request('GET', f'/devices/{device_id}')
        display_response(detail_response)
    else:
        print('Gagal membuat device. Pastikan API_KEY sudah benar.\n')

    # ============================================
    # 5. Mendapatkan Daftar Semua Device
    # ============================================
    print('5. Mendapatkan Daftar Semua Device')
    print('-' * 50)

    devices_response = make_request('GET', '/devices')
    display_response(devices_response)

    if devices_response.get('success') and isinstance(devices_response.get('data'), list):
        print(f"Total Devices: {len(devices_response['data'])}")
        for device in devices_response['data']:
            print(f"- {device['name']} (ID: {device['id']}, Status: {device['status']})")

    print('\n=== Selesai ===')


if __name__ == '__main__':
    main()

