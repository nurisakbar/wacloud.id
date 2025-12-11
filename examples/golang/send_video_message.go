package main

import (
	"fmt"
)

/**
 * Contoh Kode: Mengirim Pesan Video
 * 
 * File ini berisi contoh implementasi untuk mengirim video melalui WhatsApp
 * 
 * Catatan: URL video harus dapat diakses secara publik (public URL)
 */

func main() {
	fmt.Println("=== WACloud - Send Video Message Example ===\n\n")

	// Device ID yang sudah terhubung (status: connected)
	deviceID := "550e8400-e29b-41d4-a716-446655440000"

	// Nomor tujuan (format: tanpa leading 0, dengan kode negara)
	to := "6281234567890"

	// URL video yang dapat diakses publik
	videoURL := "https://example.com/video.mp4"

	// Data pesan video
	messageData := map[string]interface{}{
		"device_id":    deviceID,
		"to":           to,
		"message_type": "video",
		"video_url":    videoURL,
		"caption":      "Ini adalah caption untuk video", // Optional
		"as_note":      false,                            // Optional: kirim sebagai video note (lingkaran)
		"convert":      false,                            // Optional: konversi video jika diperlukan
	}

	fmt.Println("Mengirim pesan video...")
	fmt.Printf("To: %s\n", to)
	fmt.Printf("Video URL: %s\n", videoURL)
	fmt.Printf("Caption: %s\n", messageData["caption"])
	fmt.Printf("As Note: %v\n", messageData["as_note"])
	fmt.Printf("Convert: %v\n\n", messageData["convert"])

	response, err := MakeRequest("POST", "/messages", messageData)
	if err != nil {
		fmt.Printf("Error: %v\n", err)
		return
	}

	DisplayResponse(response)

	if response.Success {
		if messageID, ok := response.Data["message_id"].(string); ok {
			fmt.Println("✓ Pesan video berhasil dikirim!")
			fmt.Printf("Message ID: %s\n", messageID)
			if status, ok := response.Data["status"].(string); ok {
				fmt.Printf("Status: %s\n", status)
			}
		}
	} else {
		fmt.Println("✗ Gagal mengirim pesan video.")
		if response.Error != "" {
			fmt.Printf("Error: %s\n", response.Error)
		}
		if response.Message != "" {
			fmt.Printf("Message: %s\n", response.Message)
		}

		fmt.Println("\nTips:")
		fmt.Println("- Pastikan URL video dapat diakses secara publik")
		fmt.Println("- Format video yang didukung: MP4, AVI, MOV, dll")
		fmt.Println("- Pastikan device dalam status 'connected'")
		fmt.Println("- Ukuran file maksimal sesuai limit WhatsApp (biasanya 64MB untuk video biasa, 16MB untuk video note)")
		fmt.Println("- Durasi video note maksimal 60 detik")
		fmt.Println("- Set 'as_note' ke true untuk mengirim sebagai video note (lingkaran)")
	}

	fmt.Println("\n=== Selesai ===")
}

