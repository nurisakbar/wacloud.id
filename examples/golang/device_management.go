package main

import (
	"fmt"
	"strings"
)

/**
 * Contoh Kode: Device Management (Session Management)
 * 
 * File ini berisi contoh implementasi untuk mengelola device/session WhatsApp
 * termasuk membuat device baru, mendapatkan QR code, dan mengecek status device.
 */

func main() {
	fmt.Println("=== WACloud - Device Management Examples ===\n\n")

	// ============================================
	// 1. Membuat Device Baru
	// ============================================
	fmt.Println("1. Membuat Device Baru")
	fmt.Println(strings.Repeat("-", 50))

	deviceData := map[string]interface{}{
		"name":         "Device Utama",
		"phone_number": "81234567890", // Format: tanpa leading 0, 9-13 digit
	}

	response, err := MakeRequest("POST", "/devices", deviceData)
	if err != nil {
		fmt.Printf("Error: %v\n", err)
		return
	}

	DisplayResponse(response)

	if response.Success {
		if deviceID, ok := response.Data["id"].(string); ok {
			fmt.Printf("Device ID: %s\n", deviceID)
			if status, ok := response.Data["status"].(string); ok {
				fmt.Printf("Status: %s\n\n", status)
			}

			// ============================================
			// 2. Mendapatkan QR Code untuk Pairing
			// ============================================
			fmt.Println("2. Mendapatkan QR Code untuk Pairing")
			fmt.Println(strings.Repeat("-", 50))

			qrResponse, err := MakeRequest("GET", "/devices/"+deviceID+"/pair", nil)
			if err != nil {
				fmt.Printf("Error: %v\n", err)
				return
			}

			DisplayResponse(qrResponse)

			if qrResponse.Success {
				if qrCode, ok := qrResponse.Data["qr_code"].(string); ok {
					if len(qrCode) > 50 {
						fmt.Printf("QR Code (Base64): %s...\n", qrCode[:50])
					} else {
						fmt.Printf("QR Code (Base64): %s\n", qrCode)
					}
					if expiresAt, ok := qrResponse.Data["expires_at"].(string); ok {
						fmt.Printf("Expires At: %s\n", expiresAt)
					}
					fmt.Println("\nUntuk menampilkan QR code di browser:")
					fmt.Printf("<img src='%s' alt='QR Code' />\n\n", qrCode)
				}
			}

			// ============================================
			// 3. Mendapatkan Status Device
			// ============================================
			fmt.Println("3. Mendapatkan Status Device")
			fmt.Println(strings.Repeat("-", 50))

			statusResponse, err := MakeRequest("GET", "/devices/"+deviceID+"/status", nil)
			if err != nil {
				fmt.Printf("Error: %v\n", err)
				return
			}

			DisplayResponse(statusResponse)

			// ============================================
			// 4. Mendapatkan Detail Device
			// ============================================
			fmt.Println("4. Mendapatkan Detail Device")
			fmt.Println(strings.Repeat("-", 50))

			detailResponse, err := MakeRequest("GET", "/devices/"+deviceID, nil)
			if err != nil {
				fmt.Printf("Error: %v\n", err)
				return
			}

			DisplayResponse(detailResponse)
		}
	} else {
		fmt.Println("Gagal membuat device. Pastikan API_KEY sudah benar.\n")
	}

	// ============================================
	// 5. Mendapatkan Daftar Semua Device
	// ============================================
	fmt.Println("5. Mendapatkan Daftar Semua Device")
	fmt.Println(strings.Repeat("-", 50))

	devicesResponse, err := MakeRequest("GET", "/devices", nil)
	if err != nil {
		fmt.Printf("Error: %v\n", err)
		return
	}

	DisplayResponse(devicesResponse)

	fmt.Println("\n=== Selesai ===")
}

