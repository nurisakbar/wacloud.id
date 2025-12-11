package main

import (
	"fmt"
)

/**
 * Contoh Kode: Mengirim Pesan Document (PDF, DOC, dll)
 * 
 * File ini berisi contoh implementasi untuk mengirim dokumen melalui WhatsApp
 * 
 * Catatan: URL dokumen harus dapat diakses secara publik (public URL)
 */

func main() {
	fmt.Println("=== WACloud - Send Document Message Example ===\n\n")

	// Device ID yang sudah terhubung (status: connected)
	deviceID := "550e8400-e29b-41d4-a716-446655440000"

	// Nomor tujuan (format: tanpa leading 0, dengan kode negara)
	to := "6281234567890"

	// URL dokumen yang dapat diakses publik
	documentURL := "https://example.com/document.pdf"

	// Nama file (akan ditampilkan di WhatsApp)
	filename := "document.pdf"

	// Data pesan dokumen
	messageData := map[string]interface{}{
		"device_id":    deviceID,
		"to":           to,
		"message_type": "document",
		"document_url": documentURL,
		"filename":     filename,
		"caption":      "Ini adalah caption untuk dokumen", // Optional
	}

	fmt.Println("Mengirim pesan dokumen...")
	fmt.Printf("To: %s\n", to)
	fmt.Printf("Document URL: %s\n", documentURL)
	fmt.Printf("Filename: %s\n", filename)
	fmt.Printf("Caption: %s\n\n", messageData["caption"])

	response, err := MakeRequest("POST", "/messages", messageData)
	if err != nil {
		fmt.Printf("Error: %v\n", err)
		return
	}

	DisplayResponse(response)

	if response.Success {
		if messageID, ok := response.Data["message_id"].(string); ok {
			fmt.Println("✓ Pesan dokumen berhasil dikirim!")
			fmt.Printf("Message ID: %s\n", messageID)
			if status, ok := response.Data["status"].(string); ok {
				fmt.Printf("Status: %s\n", status)
			}
		}
	} else {
		fmt.Println("✗ Gagal mengirim pesan dokumen.")
		if response.Error != "" {
			fmt.Printf("Error: %s\n", response.Error)
		}
		if response.Message != "" {
			fmt.Printf("Message: %s\n", response.Message)
		}

		fmt.Println("\nTips:")
		fmt.Println("- Pastikan URL dokumen dapat diakses secara publik")
		fmt.Println("- Format dokumen yang didukung: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, dll")
		fmt.Println("- Pastikan device dalam status 'connected'")
		fmt.Println("- Ukuran file maksimal sesuai limit WhatsApp (biasanya 100MB)")
	}

	fmt.Println("\n=== Selesai ===")
}

