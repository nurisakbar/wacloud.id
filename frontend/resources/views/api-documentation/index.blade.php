@extends('layouts.base')

@section('title', 'API Documentation')

@push('styles')
<style>
    .api-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
        height: 100%;
    }
    .api-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        transform: translateY(-2px);
    }
    .api-card-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 0.5rem 0.5rem 0 0;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .api-card-header i {
        font-size: 2rem;
    }
    .api-card-body {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        min-height: 300px;
    }
    .api-card-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
    }
    .api-card-description {
        color: #6b7280;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        line-height: 1.5;
    }
    .api-card-endpoints {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 1rem;
        flex-grow: 1;
    }
    .endpoint-badge {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background-color: #f3f4f6;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 400;
        width: 100%;
        line-height: 1.5;
    }
    .endpoint-method {
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        font-weight: 700;
        font-size: 0.875rem;
        text-transform: uppercase;
        min-width: 60px;
        text-align: center;
    }
    .method-get { background-color: #3b82f6; color: white; }
    .method-post { background-color: #10b981; color: white; }
    .method-put { background-color: #f59e0b; color: white; }
    .method-delete { background-color: #ef4444; color: white; }
    .method-patch { background-color: #8b5cf6; color: white; }
    .endpoint-url {
        color: #374151;
        font-family: inherit;
        font-size: 0.875rem;
        font-weight: 400;
    }
    .view-detail-btn {
        margin-top: auto;
        width: 100%;
        padding: 0.75rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- Overview Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="api-section-title">{{ __('Overview') }}</span>
                    <span class="badge bg-primary api-badge">API</span>
                </div>
                <div class="card-body">
                    <p>
                        {{ __('Gunakan endpoint di bawah ini untuk mengirim pesan WhatsApp menggunakan WACLoud melalui API.') }}
                    </p>
                    <p class="mb-1"><strong>{{ __('Base URL') }}</strong></p>
                    <div class="api-code mb-3"><code>{{ $baseUrl }}/api/v1</code></div>

                    <p class="mb-1"><strong>{{ __('Autentikasi') }}</strong></p>
                    <p class="mb-0">
                        {{ __('Setiap request memerlukan API Key yang dapat Anda buat di menu "API Keys".') }}
                    </p>
                </div>
            </div>

            <!-- API Modules Grid -->
            <div class="row">
                <!-- Devices Module -->
                <div class="col-md-6 mb-4">
                    <div class="api-card" onclick="window.location.href='{{ route('api-docs.detail', 'devices') }}'">
                        <div class="api-card-header">
                    <i class="fas fa-mobile-alt"></i>
                            <h3 class="api-card-title mb-0">{{ __('Devices') }}</h3>
                        </div>
                        <div class="api-card-body">
                            <p class="api-card-description">
                                {{ __('Kelola device WhatsApp Anda. Buat device baru, dapatkan QR code untuk pairing, dan cek status koneksi.') }}
                            </p>
                            <div class="api-card-endpoints">
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/devices</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-post">POST</span>
                                    <span class="endpoint-url">/devices</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/devices/{id}</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/devices/{id}/pair</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/devices/{id}/status</span>
                                </span>
                            </div>
                            <button class="btn btn-primary btn-sm view-detail-btn">
                                <i class="fas fa-arrow-right"></i> {{ __('Lihat Detail') }}
                            </button>
                    </div>
                </div>
            </div>

                <!-- Messages Module -->
                <div class="col-md-6 mb-4">
                    <div class="api-card" onclick="window.location.href='{{ route('api-docs.detail', 'messages') }}'">
                        <div class="api-card-header" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <i class="fas fa-paper-plane"></i>
                            <h3 class="api-card-title mb-0">{{ __('Messages') }}</h3>
                        </div>
                        <div class="api-card-body">
                            <p class="api-card-description">
                                {{ __('Kirim berbagai jenis pesan WhatsApp: text, image, video, document, poll, button, dan list.') }}
                            </p>
                            <div class="api-card-endpoints">
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-post">POST</span>
                                    <span class="endpoint-url">/messages</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/messages</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-post">POST</span>
                                    <span class="endpoint-url">/devices/{id}/messages</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/devices/{id}/messages</span>
                                </span>
                            </div>
                            <button class="btn btn-primary btn-sm view-detail-btn">
                                <i class="fas fa-arrow-right"></i> {{ __('Lihat Detail') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Templates Module -->
                <div class="col-md-6 mb-4">
                    <div class="api-card" onclick="window.location.href='{{ route('api-docs.detail', 'templates') }}'">
                        <div class="api-card-header" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                            <i class="fas fa-file-alt"></i>
                            <h3 class="api-card-title mb-0">{{ __('Templates') }}</h3>
                            </div>
                        <div class="api-card-body">
                            <p class="api-card-description">
                                {{ __('Kelola template pesan dengan variabel dinamis. Buat, edit, dan preview template sebelum digunakan.') }}
                            </p>
                            <div class="api-card-endpoints">
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/templates</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-post">POST</span>
                                    <span class="endpoint-url">/templates</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-put">PUT</span>
                                    <span class="endpoint-url">/templates/{id}</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-delete">DELETE</span>
                                    <span class="endpoint-url">/templates/{id}</span>
                                </span>
                            </div>
                            <button class="btn btn-primary btn-sm view-detail-btn">
                                <i class="fas fa-arrow-right"></i> {{ __('Lihat Detail') }}
                            </button>
                    </div>
                </div>
            </div>

                <!-- Account Module -->
                <div class="col-md-6 mb-4">
                    <div class="api-card" onclick="window.location.href='{{ route('api-docs.detail', 'account') }}'">
                        <div class="api-card-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="fas fa-user"></i>
                            <h3 class="api-card-title mb-0">{{ __('Account') }}</h3>
                        </div>
                        <div class="api-card-body">
                            <p class="api-card-description">
                                {{ __('Informasi akun dan penggunaan quota. Cek detail akun dan statistik penggunaan API.') }}
                            </p>
                            <div class="api-card-endpoints">
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/account</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/account/usage</span>
                                </span>
                            </div>
                            <button class="btn btn-primary btn-sm view-detail-btn">
                                <i class="fas fa-arrow-right"></i> {{ __('Lihat Detail') }}
                            </button>
                    </div>
                </div>
            </div>

                <!-- OTP Module -->
                <div class="col-md-6 mb-4">
                    <div class="api-card" onclick="window.location.href='{{ route('api-docs.detail', 'otp') }}'">
                        <div class="api-card-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                            <i class="fas fa-key"></i>
                            <h3 class="api-card-title mb-0">{{ __('OTP') }}</h3>
                        </div>
                        <div class="api-card-body">
                            <p class="api-card-description">
                                {{ __('Kirim dan verifikasi kode OTP melalui WhatsApp. Mendukung template OTP dengan variabel dinamis.') }}
                            </p>
                            <div class="api-card-endpoints">
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-post">POST</span>
                                    <span class="endpoint-url">/messages/otp</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-post">POST</span>
                                    <span class="endpoint-url">/messages/verify-otp</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/messages/otp/{id}/status</span>
                                </span>
                    </div>
                            <button class="btn btn-primary btn-sm view-detail-btn">
                                <i class="fas fa-arrow-right"></i> {{ __('Lihat Detail') }}
                            </button>
                    </div>
                </div>
            </div>

                <!-- Contacts Module -->
                <div class="col-md-6 mb-4">
                    <div class="api-card" onclick="window.location.href='{{ route('api-docs.detail', 'contacts') }}'">
                        <div class="api-card-header" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);">
                            <i class="fas fa-address-book"></i>
                            <h3 class="api-card-title mb-0">{{ __('Contacts') }}</h3>
                        </div>
                        <div class="api-card-body">
                            <p class="api-card-description">
                                {{ __('Kelola kontak WhatsApp. Dapatkan daftar kontak, cek nomor terdaftar, update kontak, dan kelola LIDs (Linked IDs).') }}
                            </p>
                            <div class="api-card-endpoints">
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/devices/{id}/contacts</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/devices/{id}/contacts/{contactId}</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-put">PUT</span>
                                    <span class="endpoint-url">/devices/{id}/contacts/{chatId}</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/devices/{id}/contacts/check-exists</span>
                                </span>
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-post">POST</span>
                                    <span class="endpoint-url">/devices/{id}/contacts/{contactId}/block</span>
                                </span>
                            </div>
                            <button class="btn btn-primary btn-sm view-detail-btn">
                                <i class="fas fa-arrow-right"></i> {{ __('Lihat Detail') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

                <!-- Health Check Module -->
                <div class="col-md-6 mb-4">
                    <div class="api-card" onclick="window.location.href='{{ route('api-docs.detail', 'health') }}'">
                        <div class="api-card-header" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);">
                            <i class="fas fa-heartbeat"></i>
                            <h3 class="api-card-title mb-0">{{ __('Health Check') }}</h3>
                        </div>
                        <div class="api-card-body">
                            <p class="api-card-description">
                                {{ __('Cek status kesehatan API. Endpoint ini tidak memerlukan autentikasi.') }}
                            </p>
                            <div class="api-card-endpoints">
                                <span class="endpoint-badge">
                                    <span class="endpoint-method method-get">GET</span>
                                    <span class="endpoint-url">/health</span>
                                </span>
                    </div>
                            <button class="btn btn-primary btn-sm view-detail-btn">
                                <i class="fas fa-arrow-right"></i> {{ __('Lihat Detail') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
