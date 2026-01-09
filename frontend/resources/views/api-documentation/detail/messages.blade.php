<div class="card mb-4">
    <div class="card-header" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
        <h3 class="mb-0"><i class="fas fa-paper-plane"></i> {{ __('Messages API') }}</h3>
    </div>
    <div class="card-body">
        <p class="mb-4">{{ __('Kirim berbagai jenis pesan WhatsApp melalui API: text, image, video, document, poll, button, dan list.') }}</p>

        <!-- POST /messages -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-post">POST</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/messages</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mengirim pesan WhatsApp. Mendukung berbagai jenis pesan: text, image, video, document, poll, button, dan list.') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'send-message-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'send-message-php')">PHP</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'send-message-python')">Python</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'send-message-nodejs')">Node.js</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'send-message-javascript')">JavaScript</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'send-message-golang')">Golang</button>
                </div>
                <div id="send-message-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X POST "{{ $baseUrl }}/api/v1/messages" \
  -H "X-Api-Key: waha_your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "abc123def456",
    "to": "81234567890",
    "message_type": "text",
    "text": "Hello, this is a test message"
  }'</code></div>
                </div>
                <div id="send-message-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'waha_your_api_key_here';
$url = '{{ $baseUrl }}/api/v1/messages';

$data = [
    'device_id' => 'abc123def456',
    'to' => '81234567890',
    'message_type' => 'text',
    'text' => 'Hello, this is a test message'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
if ($result['success']) {
    echo "Message ID: " . $result['data']['message_id'] . "\n";
}</code></div>
                </div>
                <div id="send-message-python" class="code-tab-content">
                    <div class="api-code mb-0"><code>import requests

api_key = 'YOUR_API_KEY'
url = '{{ $baseUrl }}/api/v1/messages'

headers = {
    'X-Api-Key': api_key,
    'Content-Type': 'application/json'
}

data = {
    'device_id': 'abc123def456',
    'to': '81234567890',
    'message_type': 'text',
    'text': 'Hello, this is a test message'
}

response = requests.post(url, headers=headers, json=data)

if response.status_code == 201:
    result = response.json()
    if result['success']:
        print(f"Message ID: {result['data']['message_id']}")</code></div>
                </div>
                <div id="send-message-nodejs" class="code-tab-content">
                    <div class="api-code mb-0"><code>const axios = require('axios');

const apiKey = 'YOUR_API_KEY';
const url = '{{ $baseUrl }}/api/v1/messages';

const data = {
    device_id: 'abc123def456',
    to: '81234567890',
    message_type: 'text',
    text: 'Hello, this is a test message'
};

axios.post(url, data, {
    headers: {
        'X-Api-Key': apiKey,
        'Content-Type': 'application/json'
    }
})
.then(response => {
    if (response.data.success) {
        console.log(`Message ID: ${response.data.data.message_id}`);
    }
})
.catch(error => {
    console.error('Error:', error.response?.data || error.message);
});</code></div>
                </div>
                <div id="send-message-javascript" class="code-tab-content">
                    <div class="api-code mb-0"><code>const apiKey = 'YOUR_API_KEY';
const url = '{{ $baseUrl }}/api/v1/messages';

const data = {
    device_id: 'abc123def456',
    to: '81234567890',
    message_type: 'text',
    text: 'Hello, this is a test message'
};

fetch(url, {
    method: 'POST',
    headers: {
        'X-Api-Key': apiKey,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(result => {
    if (result.success) {
        console.log(`Message ID: ${result.data.message_id}`);
    }
})
.catch(error => {
    console.error('Error:', error);
});</code></div>
                </div>
                <div id="send-message-golang" class="code-tab-content">
                    <div class="api-code mb-0"><code>package main

import (
    "bytes"
    "encoding/json"
    "fmt"
    "io"
    "net/http"
)

type SendMessageRequest struct {
    DeviceID    string `json:"device_id"`
    To          string `json:"to"`
    MessageType string `json:"message_type"`
    Text        string `json:"text"`
}

type SendMessageResponse struct {
    Success bool   `json:"success"`
    Data    struct {
        MessageID string `json:"message_id"`
        Status    string `json:"status"`
    } `json:"data"`
}

func main() {
    apiKey := "YOUR_API_KEY"
    url := "{{ $baseUrl }}/api/v1/messages"
    
    data := SendMessageRequest{
        DeviceID:    "abc123def456",
        To:          "81234567890",
        MessageType: "text",
        Text:        "Hello, this is a test message",
    }
    
    jsonData, _ := json.Marshal(data)
    
    req, _ := http.NewRequest("POST", url, bytes.NewBuffer(jsonData))
    req.Header.Set("X-Api-Key", apiKey)
    req.Header.Set("Content-Type", "application/json")
    
    client := &http.Client{}
    resp, err := client.Do(req)
    if err != nil {
        panic(err)
    }
    defer resp.Body.Close()
    
    body, _ := io.ReadAll(resp.Body)
    
    var result SendMessageResponse
    json.Unmarshal(body, &result)
    
    if result.Success {
        fmt.Printf("Message ID: %s\n", result.Data.MessageID)
    }
}</code></div>
                </div>
            </div>
            
            <p class="mt-3 mb-2"><strong>{{ __('Request Body (Text Message)') }}</strong></p>
            <div class="api-code mb-2"><code>{
  "device_id": "string (required)",
  "to": "string (required, phone number)",
  "message_type": "text",
  "text": "string (required)",
  "chat_type": "personal (optional, default: personal)"
}</code></div>
            
            <p class="mt-3 mb-2"><strong>{{ __('Response Example (Success)') }}</strong></p>
            <div class="api-code mb-0"><code>{
  "success": true,
  "data": {
    "message_id": "message-uuid",
    "whatsapp_message_id": "whatsapp-msg-id",
    "status": "sent",
    "ack": 1,
    "to": "81234567890"
  }
}</code></div>
        </div>

        <!-- GET /messages -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/messages</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan daftar pesan. Memerlukan device_id sebagai query parameter.') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'get-messages-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'get-messages-php')">PHP</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'get-messages-python')">Python</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'get-messages-nodejs')">Node.js</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'get-messages-javascript')">JavaScript</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'get-messages-golang')">Golang</button>
                </div>
                <div id="get-messages-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/messages?device_id=abc123def456" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="get-messages-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$url = '{{ $baseUrl }}/api/v1/messages?device_id=abc123def456';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data['success']) {
    foreach ($data['data'] as $message) {
        echo "Message: " . $message['content'] . "\n";
    }
}</code></div>
                </div>
                <div id="get-messages-python" class="code-tab-content">
                    <div class="api-code mb-0"><code>import requests

api_key = 'YOUR_API_KEY'
url = '{{ $baseUrl }}/api/v1/messages'

headers = {
    'X-Api-Key': api_key,
    'Content-Type': 'application/json'
}

params = {
    'device_id': 'abc123def456'
}

response = requests.get(url, headers=headers, params=params)

if response.status_code == 200:
    data = response.json()
    if data['success']:
        for message in data['data']:
            print(f"Message: {message['content']}")</code></div>
                </div>
                <div id="get-messages-nodejs" class="code-tab-content">
                    <div class="api-code mb-0"><code>const axios = require('axios');

const apiKey = 'YOUR_API_KEY';
const url = '{{ $baseUrl }}/api/v1/messages';

axios.get(url, {
    params: { device_id: 'abc123def456' },
    headers: {
        'X-Api-Key': apiKey,
        'Content-Type': 'application/json'
    }
})
.then(response => {
    if (response.data.success) {
        response.data.data.forEach(message => {
            console.log(`Message: ${message.content}`);
        });
    }
})
.catch(error => {
    console.error('Error:', error.response?.data || error.message);
});</code></div>
                </div>
                <div id="get-messages-javascript" class="code-tab-content">
                    <div class="api-code mb-0"><code>const apiKey = 'YOUR_API_KEY';
const url = '{{ $baseUrl }}/api/v1/messages?device_id=abc123def456';

fetch(url, {
    method: 'GET',
    headers: {
        'X-Api-Key': apiKey,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        data.data.forEach(message => {
            console.log(`Message: ${message.content}`);
        });
    }
})
.catch(error => {
    console.error('Error:', error);
});</code></div>
                </div>
                <div id="get-messages-golang" class="code-tab-content">
                    <div class="api-code mb-0"><code>package main

import (
    "encoding/json"
    "fmt"
    "io"
    "net/http"
    "net/url"
)

type MessagesResponse struct {
    Success bool     `json:"success"`
    Data    []Message `json:"data"`
}

type Message struct {
    ID        string `json:"id"`
    Content   string `json:"content"`
    Status    string `json:"status"`
    CreatedAt string `json:"created_at"`
}

func main() {
    apiKey := "YOUR_API_KEY"
    baseURL := "{{ $baseUrl }}/api/v1/messages"
    
    params := url.Values{}
    params.Add("device_id", "abc123def456")
    url := baseURL + "?" + params.Encode()
    
    req, _ := http.NewRequest("GET", url, nil)
    req.Header.Set("X-Api-Key", apiKey)
    req.Header.Set("Content-Type", "application/json")
    
    client := &http.Client{}
    resp, err := client.Do(req)
    if err != nil {
        panic(err)
    }
    defer resp.Body.Close()
    
    body, _ := io.ReadAll(resp.Body)
    
    var result MessagesResponse
    json.Unmarshal(body, &result)
    
    if result.Success {
        for _, message := range result.Data {
            fmt.Printf("Message: %s\n", message.Content)
        }
    }
}</code></div>
                </div>
            </div>
            
            <p class="mt-3 mb-2"><strong>{{ __('Query Parameters') }}</strong></p>
            <ul class="mb-2">
                <li><code>device_id</code> - Device ID (required)</li>
                <li><code>per_page</code> - Items per page (default: 20)</li>
            </ul>
            
            <p class="mt-3 mb-2"><strong>{{ __('Response Example') }}</strong></p>
            <div class="api-code mb-0"><code>{
  "success": true,
  "data": [
    {
      "id": "message-uuid",
      "from_number": null,
      "to_number": "81234567890",
      "message_type": "text",
      "content": "Hello, this is a test message",
      "status": "sent",
      "created_at": "2025-11-30T12:00:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}</code></div>
        </div>

        <!-- GET /messages/{id} -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/messages/{message_id}</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan detail pesan berdasarkan ID.') }}</p>
            
            <p class="mt-3 mb-2"><strong>{{ __('Response Example') }}</strong></p>
            <div class="api-code mb-0"><code>{
  "success": true,
  "data": {
    "id": "message-uuid",
    "from_number": null,
    "to_number": "81234567890",
    "message_type": "text",
    "content": "Hello, this is a test message",
    "status": "sent",
    "whatsapp_message_id": "whatsapp-msg-id",
    "created_at": "2025-11-30T12:00:00Z",
    "sent_at": "2025-11-30T12:00:01Z"
  }
}</code></div>
        </div>
    </div>
</div>
