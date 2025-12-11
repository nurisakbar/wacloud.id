/**
 * Contoh File Konfigurasi WACloud API
 * 
 * Copy file ini menjadi config.js dan isi dengan API Key Anda
 * 
 * JANGAN commit file config.js ke repository!
 */

const axios = require('axios');

// API Key Anda dari dashboard
const API_KEY = 'YOUR_API_KEY';

// Base URL API
const BASE_URL = 'https://app.wacloud.id/api/v1';

/**
 * Helper function untuk membuat HTTP request
 */
async function makeRequest(method, endpoint, data = null) {
    const url = BASE_URL + endpoint;
    const config = {
        method: method,
        url: url,
        headers: {
            'X-Api-Key': API_KEY,
            'Content-Type': 'application/json'
        }
    };

    if (method === 'GET' && data) {
        config.params = data;
    } else if (['POST', 'PUT'].includes(method) && data) {
        config.data = data;
    }

    try {
        const response = await axios(config);
        return {
            success: true,
            http_code: response.status,
            data: response.data.data || response.data,
            message: response.data.message,
            ...response.data
        };
    } catch (error) {
        return {
            success: false,
            http_code: error.response?.status || 500,
            error: error.response?.data?.error || error.message,
            message: error.response?.data?.message
        };
    }
}

/**
 * Helper function untuk menampilkan response
 */
function displayResponse(response) {
    console.log(`HTTP Code: ${response.http_code || 'N/A'}`);
    console.log(`Success: ${response.success ? 'Yes' : 'No'}`);
    
    if (response.message) {
        console.log(`Message: ${response.message}`);
    }
    
    if (response.data) {
        console.log('Data:');
        console.log(JSON.stringify(response.data, null, 2));
    }
    
    if (response.error) {
        console.log(`Error: ${response.error}`);
    }
    
    console.log('\n' + '-'.repeat(50) + '\n');
}

module.exports = {
    makeRequest,
    displayResponse,
    API_KEY,
    BASE_URL
};

