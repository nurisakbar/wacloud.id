<div class="card mb-4">
    <div class="card-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
        <h3 class="mb-0"><i class="fas fa-user"></i> {{ __('Account API') }}</h3>
    </div>
    <div class="card-body">
        <p class="mb-4">{{ __('Informasi akun dan penggunaan quota. Cek detail akun dan statistik penggunaan API.') }}</p>

        <!-- GET /account -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/account</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan informasi akun lengkap termasuk quota, subscription, dan statistik.') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'account-info-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'account-info-php')">PHP</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'account-info-python')">Python</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'account-info-nodejs')">Node.js</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'account-info-javascript')">JavaScript</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'account-info-golang')">Golang</button>
                </div>
                <div id="account-info-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/account" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="account-info-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$url = '{{ $baseUrl }}/api/v1/account';

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
    echo "Balance: Rp " . number_format($data['data']['quota']['balance'], 0, ',', '.') . "\n";
    echo "Text Quota: " . $data['data']['quota']['text_quota'] . "\n";
}</code></div>
                </div>
                <div id="account-info-python" class="code-tab-content">
                    <div class="api-code mb-0"><code>import requests

api_key = 'YOUR_API_KEY'
url = '{{ $baseUrl }}/api/v1/account'

headers = {
    'X-Api-Key': api_key,
    'Content-Type': 'application/json'
}

response = requests.get(url, headers=headers)

if response.status_code == 200:
    data = response.json()
    if data['success']:
        quota = data['data']['quota']
        print(f"Balance: Rp {quota['balance']:,.0f}")
        print(f"Text Quota: {quota['text_quota']}")</code></div>
                </div>
                <div id="account-info-nodejs" class="code-tab-content">
                    <div class="api-code mb-0"><code>const axios = require('axios');

const apiKey = 'YOUR_API_KEY';
const url = '{{ $baseUrl }}/api/v1/account';

axios.get(url, {
    headers: {
        'X-Api-Key': apiKey,
        'Content-Type': 'application/json'
    }
})
.then(response => {
    if (response.data.success) {
        const quota = response.data.data.quota;
        console.log(`Balance: Rp ${quota.balance.toLocaleString()}`);
        console.log(`Text Quota: ${quota.text_quota}`);
    }
})
.catch(error => {
    console.error('Error:', error.response?.data || error.message);
});</code></div>
                </div>
                <div id="account-info-javascript" class="code-tab-content">
                    <div class="api-code mb-0"><code>const apiKey = 'YOUR_API_KEY';
const url = '{{ $baseUrl }}/api/v1/account';

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
        const quota = data.data.quota;
        console.log(`Balance: Rp ${quota.balance.toLocaleString()}`);
        console.log(`Text Quota: ${quota.text_quota}`);
    }
})
.catch(error => {
    console.error('Error:', error);
});</code></div>
                </div>
                <div id="account-info-golang" class="code-tab-content">
                    <div class="api-code mb-0"><code>package main

import (
    "encoding/json"
    "fmt"
    "io"
    "net/http"
)

type AccountResponse struct {
    Success bool `json:"success"`
    Data    struct {
        Quota struct {
            Balance        float64 `json:"balance"`
            TextQuota      int     `json:"text_quota"`
            MultimediaQuota int    `json:"multimedia_quota"`
        } `json:"quota"`
    } `json:"data"`
}

func main() {
    apiKey := "YOUR_API_KEY"
    url := "{{ $baseUrl }}/api/v1/account"
    
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
    
    var result AccountResponse
    json.Unmarshal(body, &result)
    
    if result.Success {
        fmt.Printf("Balance: Rp %.0f\n", result.Data.Quota.Balance)
        fmt.Printf("Text Quota: %d\n", result.Data.Quota.TextQuota)
    }
}</code></div>
                </div>
            </div>
            
            <p class="mt-3 mb-2"><strong>{{ __('Response Example') }}</strong></p>
            <div class="api-code mb-0"><code>{
  "success": true,
  "data": {
    "user": {
      "id": "user-uuid",
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "81234567890",
      "created_at": "2025-01-01T00:00:00Z"
    },
    "quota": {
      "balance": 100000.00,
      "text_quota": 1000,
      "multimedia_quota": 500,
      "free_text_quota": 100,
      "total_text_quota": 1100
    },
    "subscription": {
      "plan_name": "Premium",
      "plan_id": 2,
      "status": "active",
      "expires_at": "2025-12-31T23:59:59Z"
    },
    "statistics": {
      "total_messages": 1250,
      "total_sessions": 3,
      "connected_sessions": 2
    },
    "devices": [
      {
        "device_id": "device-uuid-1",
        "name": "Samsung S21"
      },
      {
        "device_id": "device-uuid-2",
        "name": "iPhone 13"
      }
    ]
  }
}</code></div>
        </div>

        <!-- GET /account/usage -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/account/usage</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan statistik penggunaan API. Dapat difilter berdasarkan tanggal.') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'account-usage-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'account-usage-php')">PHP</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'account-usage-python')">Python</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'account-usage-nodejs')">Node.js</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'account-usage-javascript')">JavaScript</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'account-usage-golang')">Golang</button>
                </div>
                <div id="account-usage-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/account/usage?start_date=2025-11-01&end_date=2025-11-30" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="account-usage-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$url = '{{ $baseUrl }}/api/v1/account/usage?start_date=2025-11-01&end_date=2025-11-30';

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
    echo "Total Requests: " . $data['data']['total_requests'] . "\n";
    echo "Average Response Time: " . $data['data']['average_response_time'] . "ms\n";
}</code></div>
                </div>
                <div id="account-usage-python" class="code-tab-content">
                    <div class="api-code mb-0"><code>import requests

api_key = 'YOUR_API_KEY'
url = '{{ $baseUrl }}/api/v1/account/usage'

headers = {
    'X-Api-Key': api_key,
    'Content-Type': 'application/json'
}

params = {
    'start_date': '2025-11-01',
    'end_date': '2025-11-30'
}

response = requests.get(url, headers=headers, params=params)

if response.status_code == 200:
    data = response.json()
    if data['success']:
        print(f"Total Requests: {data['data']['total_requests']}")
        print(f"Average Response Time: {data['data']['average_response_time']}ms")</code></div>
                </div>
                <div id="account-usage-nodejs" class="code-tab-content">
                    <div class="api-code mb-0"><code>const axios = require('axios');

const apiKey = 'YOUR_API_KEY';
const url = '{{ $baseUrl }}/api/v1/account/usage';

axios.get(url, {
    params: {
        start_date: '2025-11-01',
        end_date: '2025-11-30'
    },
    headers: {
        'X-Api-Key': apiKey,
        'Content-Type': 'application/json'
    }
})
.then(response => {
    if (response.data.success) {
        console.log(`Total Requests: ${response.data.data.total_requests}`);
        console.log(`Average Response Time: ${response.data.data.average_response_time}ms`);
    }
})
.catch(error => {
    console.error('Error:', error.response?.data || error.message);
});</code></div>
                </div>
                <div id="account-usage-javascript" class="code-tab-content">
                    <div class="api-code mb-0"><code>const apiKey = 'YOUR_API_KEY';
const url = '{{ $baseUrl }}/api/v1/account/usage?start_date=2025-11-01&end_date=2025-11-30';

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
        console.log(`Total Requests: ${data.data.total_requests}`);
        console.log(`Average Response Time: ${data.data.average_response_time}ms`);
    }
})
.catch(error => {
    console.error('Error:', error);
});</code></div>
                </div>
                <div id="account-usage-golang" class="code-tab-content">
                    <div class="api-code mb-0"><code>package main

import (
    "encoding/json"
    "fmt"
    "io"
    "net/http"
    "net/url"
)

type UsageResponse struct {
    Success bool `json:"success"`
    Data    struct {
        TotalRequests        int     `json:"total_requests"`
        AverageResponseTime  float64 `json:"average_response_time"`
    } `json:"data"`
}

func main() {
    apiKey := "YOUR_API_KEY"
    baseURL := "{{ $baseUrl }}/api/v1/account/usage"
    
    params := url.Values{}
    params.Add("start_date", "2025-11-01")
    params.Add("end_date", "2025-11-30")
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
    
    var result UsageResponse
    json.Unmarshal(body, &result)
    
    if result.Success {
        fmt.Printf("Total Requests: %d\n", result.Data.TotalRequests)
        fmt.Printf("Average Response Time: %.2fms\n", result.Data.AverageResponseTime)
    }
}</code></div>
                </div>
            </div>
            
            <p class="mt-3 mb-2"><strong>{{ __('Query Parameters') }}</strong></p>
            <ul class="mb-2">
                <li><code>start_date</code> - Tanggal mulai (optional, format: YYYY-MM-DD)</li>
                <li><code>end_date</code> - Tanggal akhir (optional, format: YYYY-MM-DD)</li>
            </ul>
            
            <p class="mt-3 mb-2"><strong>{{ __('Response Example') }}</strong></p>
            <div class="api-code mb-0"><code>{
  "success": true,
  "data": {
    "total_requests": 1500,
    "successful_requests": 1450,
    "failed_requests": 50,
    "average_response_time": 125.5,
    "requests_by_endpoint": [
      {
        "endpoint": "/api/v1/messages",
        "count": 800
      },
      {
        "endpoint": "/api/v1/devices",
        "count": 200
      }
    ],
    "requests_by_status": [
      {
        "status_code": 200,
        "count": 1400
      },
      {
        "status_code": 201,
        "count": 50
      },
      {
        "status_code": 400,
        "count": 30
      }
    ]
  }
}</code></div>
        </div>
    </div>
</div>
