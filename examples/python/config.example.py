"""
Contoh File Konfigurasi WACloud API

Copy file ini menjadi config.py dan isi dengan API Key Anda

JANGAN commit file config.py ke repository!
"""

import requests
import json

# API Key Anda dari dashboard
API_KEY = 'YOUR_API_KEY'

# Base URL API
BASE_URL = 'https://app.wacloud.id/api/v1'


def make_request(method, endpoint, data=None):
    """
    Helper function untuk membuat HTTP request
    """
    url = BASE_URL + endpoint
    headers = {
        'X-Api-Key': API_KEY,
        'Content-Type': 'application/json'
    }

    try:
        if method.upper() == 'GET':
            response = requests.get(url, headers=headers, params=data)
        elif method.upper() == 'POST':
            response = requests.post(url, headers=headers, json=data)
        elif method.upper() == 'PUT':
            response = requests.put(url, headers=headers, json=data)
        elif method.upper() == 'DELETE':
            response = requests.delete(url, headers=headers)
        else:
            return {
                'success': False,
                'error': f'Unsupported method: {method}'
            }

        response.raise_for_status()
        result = response.json()
        
        return {
            'success': True,
            'http_code': response.status_code,
            'data': result.get('data', result),
            'message': result.get('message'),
            **result
        }
    except requests.exceptions.RequestException as e:
        error_data = {}
        if hasattr(e.response, 'json'):
            try:
                error_data = e.response.json()
            except:
                pass
        
        return {
            'success': False,
            'http_code': e.response.status_code if e.response else 500,
            'error': error_data.get('error', str(e)),
            'message': error_data.get('message')
        }


def display_response(response):
    """
    Helper function untuk menampilkan response
    """
    print(f"HTTP Code: {response.get('http_code', 'N/A')}")
    print(f"Success: {'Yes' if response.get('success') else 'No'}")
    
    if response.get('message'):
        print(f"Message: {response['message']}")
    
    if response.get('data'):
        print('Data:')
        print(json.dumps(response['data'], indent=2, ensure_ascii=False))
    
    if response.get('error'):
        print(f"Error: {response['error']}")
    
    print('\n' + '-' * 50 + '\n')

