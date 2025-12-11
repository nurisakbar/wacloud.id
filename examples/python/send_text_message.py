"""
Contoh Kode: Mengirim Pesan Text

File ini berisi contoh implementasi untuk mengirim pesan text melalui WhatsApp
"""

from config import make_request, display_response


def main():
    print('=== WACloud - Send Text Message Example ===\n\n')

    # Device ID yang sudah terhubung (status: connected)
    device_id = '550e8400-e29b-41d4-a716-446655440000'

    # Nomor tujuan (format: tanpa leading 0, dengan kode negara)
    # Contoh: 6281234567890 (Indonesia)
    to = '6281234567890'

    # Data pesan
    message_data = {
        'device_id': device_id,
        'to': to,
        'message_type': 'text',
        'text': 'Halo, ini pesan dari API WACloud!'
    }

    print('Mengirim pesan text...')
    print(f'To: {to}')
    print(f"Message: {message_data['text']}\n")

    response = make_request('POST', '/messages', message_data)
    display_response(response)

    if response.get('success') and response.get('data', {}).get('message_id'):
        print('✓ Pesan berhasil dikirim!')
        print(f"Message ID: {response['data']['message_id']}")
        print(f"WhatsApp Message ID: {response['data']['whatsapp_message_id']}")
        print(f"Status: {response['data']['status']}")
    else:
        print('✗ Gagal mengirim pesan.')
        if response.get('error'):
            print(f"Error: {response['error']}")
        if response.get('message'):
            print(f"Message: {response['message']}")

    print('\n=== Selesai ===')


if __name__ == '__main__':
    main()

