"""
Contoh Kode: Mengirim Pesan Video

File ini berisi contoh implementasi untuk mengirim video melalui WhatsApp

Catatan: URL video harus dapat diakses secara publik (public URL)
"""

from config import make_request, display_response


def main():
    print('=== WACloud - Send Video Message Example ===\n\n')

    # Device ID yang sudah terhubung (status: connected)
    device_id = '550e8400-e29b-41d4-a716-446655440000'

    # Nomor tujuan (format: tanpa leading 0, dengan kode negara)
    to = '6281234567890'

    # URL video yang dapat diakses publik
    video_url = 'https://example.com/video.mp4'

    # Data pesan video
    message_data = {
        'device_id': device_id,
        'to': to,
        'message_type': 'video',
        'video_url': video_url,
        'caption': 'Ini adalah caption untuk video',  # Optional
        'as_note': False,  # Optional: kirim sebagai video note (lingkaran)
        'convert': False  # Optional: konversi video jika diperlukan
    }

    print('Mengirim pesan video...')
    print(f'To: {to}')
    print(f'Video URL: {video_url}')
    print(f"Caption: {message_data['caption']}")
    print(f"As Note: {'Yes' if message_data['as_note'] else 'No'}")
    print(f"Convert: {'Yes' if message_data['convert'] else 'No'}\n")

    response = make_request('POST', '/messages', message_data)
    display_response(response)

    if response.get('success') and response.get('data', {}).get('message_id'):
        print('✓ Pesan video berhasil dikirim!')
        print(f"Message ID: {response['data']['message_id']}")
        print(f"Status: {response['data']['status']}")
    else:
        print('✗ Gagal mengirim pesan video.')
        if response.get('error'):
            print(f"Error: {response['error']}")
        if response.get('message'):
            print(f"Message: {response['message']}")

        print('\nTips:')
        print('- Pastikan URL video dapat diakses secara publik')
        print('- Format video yang didukung: MP4, AVI, MOV, dll')
        print('- Pastikan device dalam status \'connected\'')
        print('- Ukuran file maksimal sesuai limit WhatsApp (biasanya 64MB untuk video biasa, 16MB untuk video note)')
        print('- Durasi video note maksimal 60 detik')
        print('- Set \'as_note\' ke true untuk mengirim sebagai video note (lingkaran)')

    print('\n=== Selesai ===')


if __name__ == '__main__':
    main()

