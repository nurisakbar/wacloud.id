"""
Contoh Kode: Mengirim Pesan Image

File ini berisi contoh implementasi untuk mengirim pesan gambar melalui WhatsApp

Catatan: URL gambar harus dapat diakses secara publik (public URL)
"""

from config import make_request, display_response


def main():
    print('=== WACloud - Send Image Message Example ===\n\n')

    # Device ID yang sudah terhubung (status: connected)
    device_id = '550e8400-e29b-41d4-a716-446655440000'

    # Nomor tujuan (format: tanpa leading 0, dengan kode negara)
    to = '6281234567890'

    # URL gambar yang dapat diakses publik
    image_url = 'https://example.com/image.jpg'

    # Data pesan gambar
    message_data = {
        'device_id': device_id,
        'to': to,
        'message_type': 'image',
        'image_url': image_url,
        'caption': 'Ini adalah caption untuk gambar'  # Optional
    }

    print('Mengirim pesan gambar...')
    print(f'To: {to}')
    print(f'Image URL: {image_url}')
    print(f"Caption: {message_data['caption']}\n")

    response = make_request('POST', '/messages', message_data)
    display_response(response)

    if response.get('success') and response.get('data', {}).get('message_id'):
        print('✓ Pesan gambar berhasil dikirim!')
        print(f"Message ID: {response['data']['message_id']}")
        print(f"Status: {response['data']['status']}")
    else:
        print('✗ Gagal mengirim pesan gambar.')
        if response.get('error'):
            print(f"Error: {response['error']}")
        if response.get('message'):
            print(f"Message: {response['message']}")

        print('\nTips:')
        print('- Pastikan URL gambar dapat diakses secara publik')
        print('- Pastikan format gambar didukung (JPG, PNG, GIF, dll)')
        print('- Pastikan device dalam status \'connected\'')

    print('\n=== Selesai ===')


if __name__ == '__main__':
    main()

