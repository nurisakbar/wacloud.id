<div class="card mb-4">
    <div class="card-header" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: white;">
        <h3 class="mb-0"><i class="fas fa-address-book"></i> {{ __('Contacts API') }}</h3>
    </div>
    <div class="card-body">
        <p class="mb-4">{{ __('Kelola kontak WhatsApp melalui API. Dapatkan daftar kontak, cek nomor terdaftar, update kontak, dapatkan foto profil, blokir/unblokir kontak, dan kelola LIDs (Linked IDs).') }}</p>

        <!-- GET /devices/{session}/contacts -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/contacts</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan daftar semua kontak untuk device tertentu dengan pagination.') }}</p>
            
            <p class="mb-2"><strong>{{ __('Query Parameters:') }}</strong></p>
            <ul class="mb-3">
                <li><code>limit</code> (optional, default: 100) - Jumlah kontak yang dikembalikan</li>
                <li><code>offset</code> (optional, default: 0) - Offset untuk pagination</li>
                <li><code>sortBy</code> (optional, default: 'id') - Field untuk sorting ('id' atau 'name')</li>
                <li><code>sortOrder</code> (optional, default: 'asc') - Urutan sorting ('asc' atau 'desc')</li>
            </ul>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'contacts-list-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'contacts-list-php')">PHP</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'contacts-list-python')">Python</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'contacts-list-nodejs')">Node.js</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'contacts-list-javascript')">JavaScript</button>
                </div>
                <div id="contacts-list-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/devices/default/contacts?limit=100&offset=0&sortBy=name&sortOrder=asc" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="contacts-list-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/contacts';

$params = [
    'limit' => 100,
    'offset' => 0,
    'sortBy' => 'name',
    'sortOrder' => 'asc'
];

$ch = curl_init($url . '?' . http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data['success']) {
    foreach ($data['data'] as $contact) {
        echo "Contact: " . $contact['name'] . "\n";
    }
}</code></div>
                </div>
                <div id="contacts-list-python" class="code-tab-content">
                    <div class="api-code mb-0"><code>import requests

api_key = 'YOUR_API_KEY'
session = 'default'
url = '{{ $baseUrl }}/api/v1/devices/' + session + '/contacts'

headers = {
    'X-Api-Key': api_key,
    'Content-Type': 'application/json'
}

params = {
    'limit': 100,
    'offset': 0,
    'sortBy': 'name',
    'sortOrder': 'asc'
}

response = requests.get(url, headers=headers, params=params)

if response.status_code == 200:
    data = response.json()
    if data['success']:
        for contact in data['data']:
            print(f"Contact: {contact['name']}")</code></div>
                </div>
                <div id="contacts-list-nodejs" class="code-tab-content">
                    <div class="api-code mb-0"><code>const axios = require('axios');

const apiKey = 'YOUR_API_KEY';
const session = 'default';
const url = `{{ $baseUrl }}/api/v1/devices/${session}/contacts`;

axios.get(url, {
    headers: {
        'X-Api-Key': apiKey,
        'Content-Type': 'application/json'
    },
    params: {
        limit: 100,
        offset: 0,
        sortBy: 'name',
        sortOrder: 'asc'
    }
})
.then(response => {
    if (response.data.success) {
        response.data.data.forEach(contact => {
            console.log(`Contact: ${contact.name}`);
        });
    }
})
.catch(error => console.error(error));</code></div>
                </div>
                <div id="contacts-list-javascript" class="code-tab-content">
                    <div class="api-code mb-0"><code>const apiKey = 'YOUR_API_KEY';
const session = 'default';
const url = `{{ $baseUrl }}/api/v1/devices/${session}/contacts`;

fetch(url + '?limit=100&offset=0&sortBy=name&sortOrder=asc', {
    method: 'GET',
    headers: {
        'X-Api-Key': apiKey,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        data.data.forEach(contact => {
            console.log(`Contact: ${contact.name}`);
        });
    }
})
.catch(error => console.error(error));</code></div>
                </div>
            </div>
        </div>

        <!-- GET /devices/{session}/contacts/{contactId} -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/contacts/{contactId}</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan informasi kontak tertentu. Contact ID bisa berupa nomor telepon (123123123) atau chat ID (123123@c.us atau 123123@lid).') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'contact-get-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'contact-get-php')">PHP</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'contact-get-python')">Python</button>
                </div>
                <div id="contact-get-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/devices/default/contacts/123123123@c.us" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="contact-get-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$contactId = '123123123@c.us';
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/contacts/' . $contactId;

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
    echo "Contact Name: " . $data['data']['name'] . "\n";
}</code></div>
                </div>
                <div id="contact-get-python" class="code-tab-content">
                    <div class="api-code mb-0"><code>import requests

api_key = 'YOUR_API_KEY'
session = 'default'
contact_id = '123123123@c.us'
url = f'{{ $baseUrl }}/api/v1/devices/{session}/contacts/{contact_id}'

headers = {
    'X-Api-Key': api_key,
    'Content-Type': 'application/json'
}

response = requests.get(url, headers=headers)

if response.status_code == 200:
    data = response.json()
    if data['success']:
        print(f"Contact Name: {data['data']['name']}")</code></div>
                </div>
            </div>
        </div>

        <!-- PUT /devices/{session}/contacts/{chatId} -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-put">PUT</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/contacts/{chatId}</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Memperbarui informasi kontak di buku alamat telepon (dan di WhatsApp).') }}</p>
            
            <p class="mb-2"><strong>{{ __('Request Body:') }}</strong></p>
            <div class="api-code mb-3"><code>{
  "firstName": "John",
  "lastName": "Doe"
}</code></div>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'contact-update-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'contact-update-php')">PHP</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'contact-update-python')">Python</button>
                </div>
                <div id="contact-update-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X PUT "{{ $baseUrl }}/api/v1/devices/default/contacts/123123123@c.us" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "John",
    "lastName": "Doe"
  }'</code></div>
                </div>
                <div id="contact-update-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$chatId = '123123123@c.us';
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/contacts/' . $chatId;

$data = [
    'firstName' => 'John',
    'lastName' => 'Doe'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
if ($result['success']) {
    echo "Contact updated successfully\n";
}</code></div>
                </div>
                <div id="contact-update-python" class="code-tab-content">
                    <div class="api-code mb-0"><code>import requests

api_key = 'YOUR_API_KEY'
session = 'default'
chat_id = '123123123@c.us'
url = f'{{ $baseUrl }}/api/v1/devices/{session}/contacts/{chat_id}'

headers = {
    'X-Api-Key': api_key,
    'Content-Type': 'application/json'
}

data = {
    'firstName': 'John',
    'lastName': 'Doe'
}

response = requests.put(url, headers=headers, json=data)

if response.status_code == 200:
    result = response.json()
    if result['success']:
        print("Contact updated successfully")</code></div>
                </div>
            </div>
        </div>

        <!-- GET /devices/{session}/contacts/check-exists -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/contacts/check-exists</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Memeriksa apakah nomor telepon terdaftar di WhatsApp (bahkan jika nomor tidak ada di daftar kontak Anda).') }}</p>
            
            <p class="mb-2"><strong>{{ __('Query Parameters:') }}</strong></p>
            <ul class="mb-3">
                <li><code>phone</code> (required) - Nomor telepon yang akan dicek (contoh: 11231231231)</li>
            </ul>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'check-exists-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'check-exists-php')">PHP</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'check-exists-python')">Python</button>
                </div>
                <div id="check-exists-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/devices/default/contacts/check-exists?phone=11231231231" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="check-exists-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$phone = '11231231231';
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/contacts/check-exists?phone=' . $phone;

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
    if ($data['data']['numberExists']) {
        echo "Number exists: " . $data['data']['chatId'] . "\n";
    } else {
        echo "Number does not exist\n";
    }
}</code></div>
                </div>
                <div id="check-exists-python" class="code-tab-content">
                    <div class="api-code mb-0"><code>import requests

api_key = 'YOUR_API_KEY'
session = 'default'
phone = '11231231231'
url = f'{{ $baseUrl }}/api/v1/devices/{session}/contacts/check-exists'

headers = {
    'X-Api-Key': api_key,
    'Content-Type': 'application/json'
}

params = {
    'phone': phone
}

response = requests.get(url, headers=headers, params=params)

if response.status_code == 200:
    data = response.json()
    if data['success']:
        if data['data']['numberExists']:
            print(f"Number exists: {data['data']['chatId']}")
        else:
            print("Number does not exist")</code></div>
                </div>
            </div>
        </div>

        <!-- GET /devices/{session}/contacts/{contactId}/about -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/contacts/{contactId}/about</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan informasi "about" dari kontak.') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'contact-about-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'contact-about-php')">PHP</button>
                </div>
                <div id="contact-about-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/devices/default/contacts/123123123@c.us/about" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="contact-about-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$contactId = '123123123@c.us';
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/contacts/' . $contactId . '/about';

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
    echo "About: " . $data['data']['about'] . "\n";
}</code></div>
                </div>
            </div>
        </div>

        <!-- GET /devices/{session}/contacts/{contactId}/profile-picture -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/contacts/{contactId}/profile-picture</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan foto profil kontak. Secara default, gambar di-cache selama 24 jam. Gunakan parameter refresh=true untuk memaksa refresh.') }}</p>
            
            <p class="mb-2"><strong>{{ __('Query Parameters:') }}</strong></p>
            <ul class="mb-3">
                <li><code>refresh</code> (optional, default: false) - Paksa refresh gambar</li>
            </ul>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'profile-picture-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'profile-picture-php')">PHP</button>
                </div>
                <div id="profile-picture-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/devices/default/contacts/123123123@c.us/profile-picture?refresh=false" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="profile-picture-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$contactId = '123123123@c.us';
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/contacts/' . $contactId . '/profile-picture?refresh=false';

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
    echo "Profile Picture URL: " . $data['data']['profilePictureURL'] . "\n";
}</code></div>
                </div>
            </div>
        </div>

        <!-- POST /devices/{session}/contacts/{contactId}/block -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-post">POST</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/contacts/{contactId}/block</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Memblokir kontak.') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'block-contact-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'block-contact-php')">PHP</button>
                </div>
                <div id="block-contact-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X POST "{{ $baseUrl }}/api/v1/devices/default/contacts/123123123@c.us/block" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="block-contact-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$contactId = '123123123@c.us';
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/contacts/' . $contactId . '/block';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
if ($result['success']) {
    echo "Contact blocked successfully\n";
}</code></div>
                </div>
            </div>
        </div>

        <!-- POST /devices/{session}/contacts/{contactId}/unblock -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-post">POST</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/contacts/{contactId}/unblock</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Membuka blokir kontak.') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'unblock-contact-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'unblock-contact-php')">PHP</button>
                </div>
                <div id="unblock-contact-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X POST "{{ $baseUrl }}/api/v1/devices/default/contacts/123123123@c.us/unblock" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="unblock-contact-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$contactId = '123123123@c.us';
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/contacts/' . $contactId . '/unblock';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
if ($result['success']) {
    echo "Contact unblocked successfully\n";
}</code></div>
                </div>
            </div>
        </div>

        <!-- LIDs Section -->
        <div class="mt-4 mb-3">
            <h4 class="mb-3"><i class="fas fa-link"></i> {{ __('LIDs (Linked IDs)') }}</h4>
            <p class="mb-4">{{ __('WhatsApp menggunakan identifier Linked ID (lid) untuk menyembunyikan nomor telepon pengguna dari grup publik. Gunakan API di bawah ini untuk memetakan LID ke nomor telepon.') }}</p>
        </div>

        <!-- GET /devices/{session}/lids -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/lids</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan semua mapping LID ke nomor telepon untuk session.') }}</p>
            
            <p class="mb-2"><strong>{{ __('Query Parameters:') }}</strong></p>
            <ul class="mb-3">
                <li><code>limit</code> (optional, default: 100) - Jumlah record yang dikembalikan</li>
                <li><code>offset</code> (optional, default: 0) - Offset untuk pagination</li>
            </ul>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'lids-list-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'lids-list-php')">PHP</button>
                </div>
                <div id="lids-list-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/devices/default/lids?limit=100&offset=0" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="lids-list-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/lids?limit=100&offset=0';

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
    foreach ($data['data'] as $lid) {
        echo "LID: " . $lid['lid'] . " -> Phone: " . $lid['pn'] . "\n";
    }
}</code></div>
                </div>
            </div>
        </div>

        <!-- GET /devices/{session}/lids/count -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/lids/count</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan jumlah mapping LID yang diketahui untuk session.') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'lids-count-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'lids-count-php')">PHP</button>
                </div>
                <div id="lids-count-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/devices/default/lids/count" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="lids-count-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/lids/count';

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
    echo "LIDs Count: " . $data['data']['count'] . "\n";
}</code></div>
                </div>
            </div>
        </div>

        <!-- GET /devices/{session}/lids/{lid} -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/lids/{lid}</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan nomor telepon yang terkait dengan LID tertentu. Ingat untuk escape @ di lid dengan %40 atau gunakan hanya nomor.') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'lid-phone-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'lid-phone-php')">PHP</button>
                </div>
                <div id="lid-phone-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/devices/default/lids/123123123%40lid" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="lid-phone-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$lid = '123123123@lid';
$lidEscaped = str_replace('@', '%40', $lid);
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/lids/' . $lidEscaped;

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
    echo "Phone Number: " . $data['data']['pn'] . "\n";
}</code></div>
                </div>
            </div>
        </div>

        <!-- GET /devices/{session}/lids/phone/{phoneNumber} -->
        <div class="endpoint-item">
            <div class="d-flex align-items-center mb-2">
                <span class="endpoint-method-badge badge-get">GET</span>
                <span class="api-endpoint-url">{{ $baseUrl }}/api/v1/devices/{session}/lids/phone/{phoneNumber}</span>
            </div>
            <p class="endpoint-description mb-3">{{ __('Mendapatkan LID untuk nomor telepon tertentu. Ingat untuk escape @ di phoneNumber dengan %40 atau gunakan hanya nomor.') }}</p>
            
            <div class="code-tabs">
                <div class="code-tabs-header">
                    <button class="code-tab active" onclick="switchCodeTab(this, 'phone-lid-curl')">cURL</button>
                    <button class="code-tab" onclick="switchCodeTab(this, 'phone-lid-php')">PHP</button>
                </div>
                <div id="phone-lid-curl" class="code-tab-content active">
                    <div class="api-code mb-0"><code>curl -X GET "{{ $baseUrl }}/api/v1/devices/default/lids/phone/123456789%40c.us" \
  -H "X-Api-Key: YOUR_API_KEY" \
  -H "Content-Type: application/json"</code></div>
                </div>
                <div id="phone-lid-php" class="code-tab-content">
                    <div class="api-code mb-0"><code>&lt;?php
$apiKey = 'YOUR_API_KEY';
$session = 'default';
$phoneNumber = '123456789@c.us';
$phoneEscaped = str_replace('@', '%40', $phoneNumber);
$url = '{{ $baseUrl }}/api/v1/devices/' . $session . '/lids/phone/' . $phoneEscaped;

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
    echo "LID: " . $data['data']['lid'] . "\n";
}</code></div>
                </div>
            </div>
        </div>
    </div>
</div>

