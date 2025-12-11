"""
Contoh Kode: Mengirim Pesan Document (PDF, DOC, dll)

File ini berisi contoh implementasi untuk mengirim dokumen melalui WhatsApp

Catatan: URL dokumen harus dapat diakses secara publik (public URL)
"""

from config import make_request, display_response


def main():
    print('=== WACloud - Send Document Message Example ===\n\n')

    # Device ID yang sudah terhubung (status: connected)
    device_id = '550e8400-e29b-41d4-a716-446655440000'

    # Nomor tujuan (format: tanpa leading 0, dengan kode negara)
    to = '6281234567890'

    # URL dokumen yang dapat diakses publik
    document_url = 'https://example.com/document.pdf'

    # Nama file (akan ditampilkan di WhatsApp)
    filename = 'document.pdf'

    # Data pesan dokumen
    message_data = {
        'device_id': device_id,
        'to': to,
        'message_type': 'document',
        'document_url': document_url,
        'filename': filename,
        'caption': 'Ini adalah caption untuk dokumen'  # Optional
    }

    print('Mengirim pesan dokumen...')
    print(f'To: {to}')
    print(f'Document URL: {document_url}')
    print(f'Filename: {filename}')
    print(f"Caption: {message_data['caption']}\n")

    response = make_request('POST', '/messages', message_data)
    display_response(response)

    if response.get('success') and response.get('data', {}).get('message_id'):
        print('✓ Pesan dokumen berhasil dikirim!')
        print(f"Message ID: {response['data']['message_id']}")
        print(f"Status: {response['data']['status']}")
    else:
        print('✗ Gagal mengirim pesan dokumen.')
        if response.get('error'):
            print(f"Error: {response['error']}")
        if response.get('message'):
            print(f"Message: {response['message']}")

        print('\nTips:')
        print('- Pastikan URL dokumen dapat diakses secara publik')
        print('- Format dokumen yang didukung: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, dll')
        print('- Pastikan device dalam status \'connected\'')
        print('- Ukuran file maksimal sesuai limit WhatsApp (biasanya 100MB)')

    print('\n=== Selesai ===')


if __name__ == '__main__':
    main()

