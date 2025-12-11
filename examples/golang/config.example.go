package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
)

// Contoh File Konfigurasi WACloud API
// Copy file ini menjadi config.go dan isi dengan API Key Anda
// JANGAN commit file config.go ke repository!

const (
	// API Key Anda dari dashboard
	API_KEY = "YOUR_API_KEY"
	// Base URL API
	BASE_URL = "https://app.wacloud.id/api/v1"
)

// Response structure
type APIResponse struct {
	Success  bool                   `json:"success"`
	HTTPCode int                    `json:"http_code"`
	Data     map[string]interface{} `json:"data"`
	Message  string                 `json:"message"`
	Error    string                 `json:"error"`
}

// MakeRequest membuat HTTP request ke API
func MakeRequest(method, endpoint string, data interface{}) (*APIResponse, error) {
	url := BASE_URL + endpoint

	var body io.Reader
	if data != nil && (method == "POST" || method == "PUT") {
		jsonData, err := json.Marshal(data)
		if err != nil {
			return nil, err
		}
		body = bytes.NewBuffer(jsonData)
	}

	req, err := http.NewRequest(method, url, body)
	if err != nil {
		return nil, err
	}

	req.Header.Set("X-Api-Key", API_KEY)
	req.Header.Set("Content-Type", "application/json")

	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	bodyBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		return nil, err
	}

	var apiResp APIResponse
	apiResp.HTTPCode = resp.StatusCode

	if err := json.Unmarshal(bodyBytes, &apiResp); err != nil {
		// Jika response bukan JSON, set error
		apiResp.Success = false
		apiResp.Error = string(bodyBytes)
		return &apiResp, nil
	}

	// Extract data if exists
	var result map[string]interface{}
	if err := json.Unmarshal(bodyBytes, &result); err == nil {
		if data, ok := result["data"].(map[string]interface{}); ok {
			apiResp.Data = data
		}
	}

	return &apiResp, nil
}

// DisplayResponse menampilkan response ke console
func DisplayResponse(resp *APIResponse) {
	fmt.Printf("HTTP Code: %d\n", resp.HTTPCode)
	fmt.Printf("Success: %v\n", resp.Success)

	if resp.Message != "" {
		fmt.Printf("Message: %s\n", resp.Message)
	}

	if resp.Data != nil && len(resp.Data) > 0 {
		fmt.Println("Data:")
		jsonData, _ := json.MarshalIndent(resp.Data, "", "  ")
		fmt.Println(string(jsonData))
	}

	if resp.Error != "" {
		fmt.Printf("Error: %s\n", resp.Error)
	}

	fmt.Println("\n" + strings.Repeat("-", 50) + "\n")
}

