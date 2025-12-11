package main

import (
	"fmt"
)

/**
 * Contoh Kode: Mengirim Pesan Text
 * 
 * File ini berisi contoh implementasi untuk mengirim pesan text melalui WhatsApp
 */

func main() {
	fmt.Println("=== WACloud - Send Text Message Example ===\n\n")

	// Device ID yang sudah terhubung (status: connected)
	deviceID := "550e8400-e29b-41d4-a716-446655440000"

	// Nomor tujuan (format: tanpa leading 0, dengan kode negara)
	// Contoh: 6281234567890 (Indonesia)
	to := "6281234567890"

	// Data pesan
	messageData := map[string]interface{}{
		"device_id":    deviceID,
		"to":           to,
		"message_type": "text",
		"text":         "Halo, ini pesan dari API WACloud!",
	}

	fmt.Println("Mengirim pesan text...")
	fmt.Printf("To: %s\n", to)
	fmt.Printf("Message: %s\n\n", messageData["text"])

	response, err := MakeRequest("POST", "/messages", messageData)
	if err != nil {
		fmt.Printf("Error: %v\n", err)
		return
	}

	DisplayResponse(response)

	if response.Success {
		if messageID, ok := response.Data["message_id"].(string); ok {
			fmt.Println("✓ Pesan berhasil dikirim!")
			fmt.Printf("Message ID: %s\n", messageID)
			if whatsappID, ok := response.Data["whatsapp_message_id"].(string); ok {
				fmt.Printf("WhatsApp Message ID: %s\n", whatsappID)
			}
			if status, ok := response.Data["status"].(string); ok {
				fmt.Printf("Status: %s\n", status)
			}
		}
	} else {
		fmt.Println("✗ Gagal mengirim pesan.")
		if response.Error != "" {
			fmt.Printf("Error: %s\n", response.Error)
		}
		if response.Message != "" {
			fmt.Printf("Message: %s\n", response.Message)
		}
	}

	fmt.Println("\n=== Selesai ===")
}

