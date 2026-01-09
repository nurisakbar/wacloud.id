@extends('layouts.base')

@section('title', 'Webhook Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-link text-primary"></i> {{ __('Detail Webhook') }}
                    </h2>
                    <p class="text-muted mb-0">{{ __('Informasi lengkap tentang webhook Anda') }}</p>
                </div>
                <a href="{{ route('webhooks.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> {{ __('Kembali ke Webhooks') }}
                </a>
            </div>

            <div class="row">
                <!-- Webhook Information -->
                <div class="col-lg-8 mb-4">
            <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> {{ __('Informasi Webhook') }}
                            </h5>
                </div>
                <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <tbody>
                                        <tr>
                                            <th width="35%" class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary text-white px-3 py-2 me-2">
                                                        <i class="fas fa-fingerprint"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('ID Webhook') }}</strong>
                                                </div>
                                            </th>
                                            <td>
                                                <div class="input-group">
                                                    <input type="text" 
                                                           class="form-control font-monospace" 
                                                           id="webhookIdInput" 
                                                           value="{{ $webhook->id }}" 
                                                           readonly
                                                           style="background-color: #f8f9fa; font-size: 0.9rem;">
                                                    <button class="btn btn-outline-secondary" 
                                                            type="button" 
                                                            id="copyWebhookIdBtn"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="top" 
                                                            title="Salin ke clipboard">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted mt-1 d-block">
                                                    <i class="fas fa-info-circle"></i> {{ __('UUID Webhook untuk referensi') }}
                                                </small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-info text-white px-3 py-2 me-2">
                                                        <i class="fas fa-tag"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('Nama Webhook') }}</strong>
                                                </div>
                                            </th>
                                            <td><strong>{{ $webhook->name }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-success text-white px-3 py-2 me-2">
                                                        <i class="fas fa-link"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('URL Webhook') }}</strong>
                                                </div>
                                            </th>
                                            <td>
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text bg-light">
                                                        <i class="fas fa-link text-muted"></i>
                                                    </span>
                                                    <input type="text" 
                                                           class="form-control font-monospace" 
                                                           id="webhookUrlInput" 
                                                           value="{{ $webhook->url }}" 
                                                           readonly
                                                           style="background-color: #f8f9fa; font-size: 0.9rem;">
                                                    <button class="btn btn-outline-secondary" 
                                                            type="button" 
                                                            id="copyWebhookUrlBtn"
                                                            onclick="copyToClipboard('{{ $webhook->url }}')"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="top" 
                                                            title="Salin URL">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if(str_starts_with($webhook->url, 'https://'))
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-lock"></i> HTTPS
                                                        </span>
                                                    @elseif(str_starts_with($webhook->url, 'http://'))
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-unlock"></i> HTTP
                                                        </span>
                                                    @endif
                                                    @if(str_contains($webhook->url, 'webhook.site'))
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-vial"></i> Testing URL
                                                        </span>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-info-circle"></i> {{ __('URL endpoint yang akan menerima webhook request') }}
                                                </small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-warning text-dark px-3 py-2 me-2">
                                                        <i class="fas fa-mobile-alt"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('Device') }}</strong>
                                                </div>
                                            </th>
                                            <td>
                                                @if($webhook->session)
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <span class="badge bg-info text-white px-3 py-2 fs-6">
                                                            <i class="fas fa-mobile-alt me-1"></i> 
                                                            <strong>{{ $webhook->session->session_name }}</strong>
                                                        </span>
                                                        @if($webhook->session->status === 'connected')
                                                            <span class="badge bg-success text-white">
                                                                <i class="fas fa-check-circle"></i> Connected
                                                            </span>
                                                        @elseif($webhook->session->status === 'pairing')
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="fas fa-qrcode"></i> Pairing
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary text-white">
                                                                <i class="fas fa-times-circle"></i> Disconnected
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light">
                                                            <i class="fas fa-fingerprint text-muted"></i> Session ID
                                                        </span>
                                                        <input type="text" 
                                                               class="form-control font-monospace" 
                                                               value="{{ $webhook->session->session_id }}" 
                                                               readonly
                                                               style="background-color: #f8f9fa; font-size: 0.85rem;">
                                                        <button class="btn btn-outline-secondary" 
                                                                type="button" 
                                                                onclick="copyToClipboard('{{ $webhook->session->session_id }}')"
                                                                title="Salin Session ID">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="fas fa-info-circle text-info"></i> {{ __('Webhook hanya menerima event dari device ini') }}
                                                    </small>
                                                @else
                                                    <span class="badge bg-secondary px-3 py-2 fs-6 mb-2">
                                                        <i class="fas fa-globe me-1"></i> 
                                                        <strong>{{ __('Semua Device') }}</strong>
                                                    </span>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                                        <i class="fas fa-layer-group"></i> Multi-Device
                                                    </span>
                                                    <small class="text-muted d-block mt-2">
                                                        <i class="fas fa-info-circle text-info"></i> {{ __('Webhook akan menerima event dari semua device yang terhubung') }}
                                                    </small>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge text-white px-3 py-2 me-2" style="background-color: #6f42c1;">
                                                        <i class="fas fa-bell"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('Events') }}</strong>
                                                </div>
                                            </th>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2 mb-2">
                                                    @foreach($webhook->events as $event)
                                                        @if($event === 'message')
                                                            <span class="badge bg-primary px-3 py-2 fs-6">
                                                                <i class="fas fa-envelope me-1"></i> 
                                                                <strong>{{ __('Message Events') }}</strong>
                                                            </span>
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                                                <i class="fas fa-comment-dots"></i> Pesan Masuk/Keluar
                                                            </span>
                                                        @elseif($event === 'status')
                                                            <span class="badge bg-warning text-dark px-3 py-2 fs-6">
                                                                <i class="fas fa-info-circle me-1"></i> 
                                                                <strong>{{ __('Status Events') }}</strong>
                                                            </span>
                                                            <span class="badge bg-warning bg-opacity-10 text-dark border border-warning">
                                                                <i class="fas fa-check-double"></i> Status Pesan
                                                            </span>
                                                        @elseif($event === 'session')
                                                            <span class="badge bg-info px-3 py-2 fs-6">
                                                                <i class="fas fa-mobile-alt me-1"></i> 
                                                                <strong>{{ __('Device Events') }}</strong>
                                                            </span>
                                                            <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                                                <i class="fas fa-link"></i> Status Device
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                                <div class="alert alert-info mb-0 mt-2 py-2">
                                                    <small>
                                                        <i class="fas fa-lightbulb me-1"></i>
                                                        <strong>{{ __('Info:') }}</strong> {{ __('Webhook akan menerima notifikasi real-time untuk event yang dipilih di atas') }}
                                                    </small>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-success text-white px-3 py-2 me-2">
                                                        <i class="fas fa-power-off"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('Status') }}</strong>
                                                </div>
                                            </th>
                                            <td>
                            @if($webhook->is_active)
                                                    <span class="badge bg-success fs-6 px-3 py-2">
                                                        <i class="fas fa-check-circle"></i> {{ __('AKTIF') }}
                                                    </span>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success ms-2">
                                                        <i class="fas fa-bolt"></i> {{ __('Menerima Event') }}
                                                    </span>
                                                    <small class="text-muted d-block mt-2">
                                                        <i class="fas fa-info-circle text-info"></i> {{ __('Webhook aktif dan akan menerima event secara real-time') }}
                                                    </small>
                                                @else
                                                    <span class="badge bg-secondary fs-6 px-3 py-2">
                                                        <i class="fas fa-pause-circle"></i> {{ __('TIDAK AKTIF') }}
                                                    </span>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary ms-2">
                                                        <i class="fas fa-ban"></i> {{ __('Tidak Menerima Event') }}
                                                    </span>
                                                    <small class="text-muted d-block mt-2">
                                                        <i class="fas fa-exclamation-triangle text-warning"></i> {{ __('Webhook tidak aktif dan tidak akan menerima event. Aktifkan untuk mulai menerima notifikasi.') }}
                                                    </small>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-danger text-white px-3 py-2 me-2">
                                                        <i class="fas fa-key"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('Secret Key') }}</strong>
                                                </div>
                                            </th>
                                            <td>
                                                @if($webhook->secret)
                                                    <span class="badge bg-success px-3 py-2">
                                                        <i class="fas fa-key me-1"></i> 
                                                        <strong>{{ __('TERKONFIGURASI') }}</strong>
                                                    </span>
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success ms-2">
                                                        <i class="fas fa-shield-alt"></i> {{ __('Aman') }}
                                                    </span>
                                                    <small class="text-muted d-block mt-2">
                                                        <i class="fas fa-check-circle text-success"></i> {{ __('Secret key telah dikonfigurasi. Gunakan secret ini untuk memverifikasi bahwa webhook request berasal dari sistem kami.') }}
                                                    </small>
                                                @else
                                                    <span class="badge bg-warning text-dark px-3 py-2">
                                                        <i class="fas fa-exclamation-triangle me-1"></i> 
                                                        <strong>{{ __('TIDAK TERKONFIGURASI') }}</strong>
                                                    </span>
                                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning ms-2">
                                                        <i class="fas fa-unlock"></i> {{ __('Tidak Aman') }}
                                                    </span>
                                                    <small class="text-muted d-block mt-2">
                                                        <i class="fas fa-info-circle text-warning"></i> {{ __('Secret key tidak dikonfigurasi. Disarankan untuk menambahkan secret key untuk keamanan webhook.') }}
                                                    </small>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-info text-white px-3 py-2 me-2">
                                                        <i class="fas fa-clock"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('Terakhir Dipicu') }}</strong>
                                                </div>
                                            </th>
                                            <td>
                                                @if($webhook->last_triggered_at)
                                                    @php
                                                        \Carbon\Carbon::setLocale('id');
                                                        $lastTriggered = $webhook->last_triggered_at;
                                                        $isRecent = $lastTriggered->diffInMinutes(now()) < 5;
                                                    @endphp
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <span class="badge {{ $isRecent ? 'bg-success' : 'bg-info' }} px-3 py-2">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <strong>{{ $lastTriggered->diffForHumans() }}</strong>
                                                        </span>
                                                        @if($isRecent)
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                                <i class="fas fa-bolt"></i> {{ __('Baru Saja') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="fas fa-calendar-alt text-muted"></i>
                                                        <span class="text-muted">
                                                            {{ $lastTriggered->format('d/m/Y') }} 
                                                            <span class="badge bg-light text-dark border">{{ $lastTriggered->format('H:i:s') }}</span>
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="badge bg-secondary px-3 py-2">
                                                        <i class="fas fa-hourglass-half me-1"></i>
                                                        <strong>{{ __('Belum Pernah Dipicu') }}</strong>
                                                    </span>
                                                    <small class="text-muted d-block mt-2">
                                                        <i class="fas fa-info-circle text-info"></i> {{ __('Webhook belum pernah menerima event. Pastikan device terhubung dan event terjadi.') }}
                                                    </small>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary text-white px-3 py-2 me-2">
                                                        <i class="fas fa-chart-bar"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('Statistik') }}</strong>
                                                </div>
                                            </th>
                                            <td>
                                                <div class="row g-2 mb-2">
                                                    <div class="col-auto">
                                                        <div class="card border-0 bg-light">
                                                            <div class="card-body p-2 text-center">
                                                                <div class="fw-bold text-primary fs-5">
                                                                    {{ $totalLogs ?? ($webhook->logs ? $webhook->logs()->count() : 0) }}
                                                                </div>
                                                                <small class="text-muted">Total Trigger</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <div class="card border-0 bg-light">
                                                            <div class="card-body p-2 text-center">
                                                                <div class="fw-bold text-success fs-5">
                                                                    {{ $successLogs ?? ($webhook->logs ? $webhook->logs()->where('response_status', '>=', 200)->where('response_status', '<', 300)->count() : 0) }}
                                                                </div>
                                                                <small class="text-muted">Berhasil</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <div class="card border-0 bg-light">
                                                            <div class="card-body p-2 text-center">
                                                                <div class="fw-bold text-danger fs-5">
                                                                    {{ $webhook->failure_count }}
                                                                </div>
                                                                <small class="text-muted">Kegagalan</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if($webhook->failure_count > 0)
                                                    <div class="alert alert-warning mb-0 py-2">
                                                        <small>
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            <strong>{{ __('Peringatan:') }}</strong> {{ __('Webhook mengalami') }} <strong>{{ $webhook->failure_count }}</strong> {{ __('kegagalan saat mengirim request. Periksa URL webhook dan pastikan server dapat diakses.') }}
                                                        </small>
                                                    </div>
                            @else
                                                    <div class="alert alert-success mb-0 py-2">
                                                        <small>
                                                            <i class="fas fa-check-circle me-1"></i>
                                                            <strong>{{ __('Sempurna:') }}</strong> {{ __('Tidak ada kegagalan. Webhook berfungsi dengan baik!') }}
                                                        </small>
                                                    </div>
                            @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-secondary text-white px-3 py-2 me-2">
                                                        <i class="fas fa-calendar-plus"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('Dibuat Pada') }}</strong>
                                                </div>
                                            </th>
                                            <td>
                                                @php
                                                    \Carbon\Carbon::setLocale('id');
                                                @endphp
                                                <i class="fas fa-calendar text-muted"></i>
                                                {{ $webhook->created_at->format('d/m/Y H:i:s') }}
                                                <small class="text-muted d-block">
                                                    ({{ $webhook->created_at->diffForHumans() }})
                                                </small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-secondary text-white px-3 py-2 me-2">
                                                        <i class="fas fa-calendar-check"></i>
                                                    </span>
                                                    <strong class="text-dark">{{ __('Diperbarui Pada') }}</strong>
                                                </div>
                                            </th>
                                            <td>
                                                <i class="fas fa-calendar text-muted"></i>
                                                {{ $webhook->updated_at->format('d/m/Y H:i:s') }}
                                                <small class="text-muted d-block">
                                                    ({{ $webhook->updated_at->diffForHumans() }})
                                                </small>
                                            </td>
                                        </tr>
                                    </tbody>
                    </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-cog"></i> {{ __('Aksi') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <!-- Back Button -->
                                <a href="{{ route('webhooks.index') }}" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-arrow-left"></i> {{ __('Kembali ke Webhooks') }}
                                </a>

                                <!-- Edit Button -->
                                <a href="{{ route('webhooks.edit', $webhook) }}" class="btn btn-primary w-100">
                                    <i class="fas fa-edit"></i> {{ __('Edit Webhook') }}
                                </a>

                                <!-- Divider -->
                                <hr class="my-2">

                                <!-- Delete Button -->
                                <form action="{{ route('webhooks.destroy', $webhook) }}" method="POST" class="mb-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('{{ __('Apakah Anda yakin ingin menghapus webhook ini? Tindakan ini tidak dapat dibatalkan.') }}')">
                                        <i class="fas fa-trash"></i> {{ __('Hapus Webhook') }}
                                    </button>
                    </form>
                            </div>
                        </div>
                    </div>

                    <!-- Simulasi Webhook Manual -->
                    <div class="card mt-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-paper-plane"></i> {{ __('Simulasi Webhook Manual') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">
                                {{ __('Kirim test webhook ke URL webhook Anda untuk memverifikasi bahwa webhook berfungsi dengan baik.') }}
                            </p>
                            
                            <form id="testWebhookForm">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">
                                        <i class="fas fa-list"></i> {{ __('Pilih Event Type') }}
                                    </label>
                                    <select class="form-select" id="testEventType" required>
                                        <option value="message">{{ __('Message Event') }}</option>
                                        <option value="message.ack">{{ __('Message ACK Event') }}</option>
                                        <option value="status">{{ __('Status Event') }}</option>
                                        <option value="session">{{ __('Session Event') }}</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">
                                        <i class="fas fa-comment"></i> {{ __('Test Message Body') }} <small class="text-muted">(Optional)</small>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="testMessageBody" 
                                           placeholder="Test pesan dari simulasi webhook"
                                           value="Test pesan dari simulasi webhook manual">
                                </div>
                                
                                <button type="submit" class="btn btn-warning w-100" id="testWebhookBtn">
                                    <i class="fas fa-paper-plane"></i> {{ __('Kirim Test Webhook') }}
                                </button>
                            </form>
                            
                            <div id="testWebhookResult" class="mt-3" style="display: none;">
                                <div class="alert" id="testWebhookAlert">
                                    <div id="testWebhookMessage"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Webhook Testing Guide -->
                    <div class="card mt-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-vial"></i> {{ __('Cara Testing Webhook') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">{{ __('Metode 1: Menggunakan Webhook.site (Paling Mudah)') }}</h6>
                            <ol class="mb-4">
                                <li class="mb-2">
                                    <strong>Buka Webhook.site:</strong>
                                    <a href="https://webhook.site" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-external-link-alt"></i> Buka webhook.site
                                    </a>
                                </li>
                                <li class="mb-2">Copy URL unik yang diberikan (contoh: <code>https://webhook.site/unique-id</code>)</li>
                                <li class="mb-2">Edit webhook ini dan ganti URL dengan URL dari webhook.site</li>
                                <li class="mb-2">Pastikan webhook <strong>Aktif</strong></li>
                                <li class="mb-2">Kirim pesan WhatsApp ke device yang terhubung</li>
                                <li class="mb-2">Refresh halaman webhook.site untuk melihat request yang masuk</li>
                            </ol>

                            <h6 class="fw-bold mb-3">{{ __('Metode 2: Testing Manual dengan cURL') }}</h6>
                            <p class="small text-muted mb-2">{{ __('Simulasikan webhook dengan mengirim request manual:') }}</p>
                            <div class="bg-dark text-light p-3 rounded mb-3" style="font-size: 0.75rem; overflow-x: auto;">
                                <code>curl -X POST "{{ $webhook->url }}" \
  -H "Content-Type: application/json" \
  -d '{
    "event": "message",
    "session": "{{ $webhook->session ? $webhook->session->session_id : 'session_id' }}",
    "payload": {
      "id": "test_message_123",
      "timestamp": {{ time() }},
      "from": "6281234567890@c.us",
      "fromMe": false,
      "to": "6289876543210@c.us",
      "body": "Test pesan masuk dari webhook",
      "hasMedia": false
    },
    "timestamp": "{{ now()->toIso8601String() }}"
  }'</code>
                            </div>

                            <h6 class="fw-bold mb-3">{{ __('Metode 3: Testing Real dengan WhatsApp') }}</h6>
                            <ol class="mb-0">
                                <li class="mb-2">Pastikan device WhatsApp <strong>Connected</strong></li>
                                <li class="mb-2">Pastikan webhook ini <strong>Aktif</strong> dan event <strong>Message Events</strong> dipilih</li>
                                <li class="mb-2">Kirim pesan dari nomor lain ke device yang terhubung</li>
                                <li class="mb-2">Cek webhook logs di bawah untuk melihat hasil</li>
                                <li class="mb-2">Cek halaman <strong>Messages</strong> untuk melihat pesan tersimpan</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Webhook Payload Example -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-code"></i> {{ __('Contoh Payload') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-2">
                                {{ __('Format payload yang akan dikirim ke webhook URL Anda:') }}
                            </p>
                            <pre class="bg-dark text-light p-3 rounded" style="font-size: 0.75rem; overflow-x: auto;"><code>{
  "event": "message",
  "session": "{{ $webhook->session ? $webhook->session->session_id : 'session_id' }}",
  "payload": {
    "id": "true_6281234567890@c.us_1234567890",
    "timestamp": 1234567890,
    "from": "6281234567890@c.us",
    "fromMe": false,
    "to": "6289876543210@c.us",
    "body": "Pesan masuk",
    "hasMedia": false
  },
  "timestamp": "{{ now()->toIso8601String() }}"
}</code></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Webhook Statistics -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar"></i> {{ __('Statistik Webhook') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <div class="display-6 text-primary mb-2">
                                                <i class="fas fa-bolt"></i>
                                            </div>
                                            <div class="h3 fw-bold text-primary mb-1">
                                                {{ $totalLogs ?? ($webhook->logs ? $webhook->logs()->count() : 0) }}
                                            </div>
                                            <div class="text-muted small">Total Trigger</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <div class="display-6 text-success mb-2">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                            <div class="h3 fw-bold text-success mb-1">
                                                {{ $successLogs ?? ($webhook->logs ? $webhook->logs()->where('response_status', '>=', 200)->where('response_status', '<', 300)->count() : 0) }}
                                            </div>
                                            <div class="text-muted small">Berhasil</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-danger">
                                        <div class="card-body text-center">
                                            <div class="display-6 text-danger mb-2">
                                                <i class="fas fa-times-circle"></i>
                                            </div>
                                            <div class="h3 fw-bold text-danger mb-1">
                                                {{ $webhook->failure_count }}
                                            </div>
                                            <div class="text-muted small">Kegagalan</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card border-info">
                                        <div class="card-body text-center">
                                            <div class="display-6 text-info mb-2">
                                                <i class="fas fa-percentage"></i>
                                            </div>
                                            <div class="h3 fw-bold text-info mb-1">
                                                @php
                                                    $total = $totalLogs ?? ($webhook->logs ? $webhook->logs()->count() : 0);
                                                    $success = $successLogs ?? ($webhook->logs ? $webhook->logs()->where('response_status', '>=', 200)->where('response_status', '<', 300)->count() : 0);
                                                    $rate = $total > 0 ? round(($success / $total) * 100, 1) : 0;
                                                @endphp
                                                {{ $rate }}%
                                            </div>
                                            <div class="text-muted small">Success Rate</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Webhook Logs -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> {{ __('Log Webhook') }}
                            @if(($totalLogs ?? ($webhook->logs ? $webhook->logs()->count() : 0)) > 0)
                                <span class="badge bg-light text-dark ms-2">{{ $totalLogs ?? ($webhook->logs ? $webhook->logs()->count() : 0) }}</span>
                            @endif
                            </h5>
                            @if(($totalLogs ?? ($webhook->logs ? $webhook->logs()->count() : 0)) > 20)
                                <small class="text-white-50">{{ __('Menampilkan 20 log terbaru dari') }} {{ $totalLogs ?? ($webhook->logs ? $webhook->logs()->count() : 0) }} {{ __('total') }}</small>
                            @endif
                        </div>
                        <div class="card-body">
                            @if(($totalLogs ?? ($webhook->logs ? $webhook->logs()->count() : 0)) > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="15%">{{ __('Waktu') }}</th>
                                                <th width="15%">{{ __('Event') }}</th>
                                                <th width="10%">{{ __('Status') }}</th>
                                                <th width="15%">{{ __('Response Time') }}</th>
                                                <th width="45%">{{ __('Response') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($webhook->logs->take(20) as $log)
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="fw-bold">{{ $log->created_at->format('d/m/Y') }}</span>
                                                        <span class="text-muted small">{{ $log->created_at->format('H:i:s') }}</span>
                                                        <span class="badge bg-light text-dark border mt-1" style="font-size: 0.7rem;">
                                                            {{ $log->created_at->diffForHumans() }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($log->event_type === 'message')
                                                        <span class="badge bg-primary px-3 py-2">
                                                            <i class="fas fa-envelope me-1"></i> Message
                                                        </span>
                                                    @elseif($log->event_type === 'message.ack')
                                                        <span class="badge bg-info px-3 py-2">
                                                            <i class="fas fa-check-double me-1"></i> ACK
                                                        </span>
                                                    @elseif($log->event_type === 'status')
                                                        <span class="badge bg-warning text-dark px-3 py-2">
                                                            <i class="fas fa-info-circle me-1"></i> Status
                                                        </span>
                                                    @elseif($log->event_type === 'session')
                                                        <span class="badge bg-secondary px-3 py-2">
                                                            <i class="fas fa-mobile-alt me-1"></i> Session
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary px-3 py-2">
                                                            {{ $log->event_type }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($log->response_status >= 200 && $log->response_status < 300)
                                                        <span class="badge bg-success px-3 py-2">
                                                            <i class="fas fa-check-circle me-1"></i> {{ $log->response_status }}
                                                        </span>
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success d-block mt-1" style="font-size: 0.7rem;">
                                                            Success
                                                        </span>
                                                    @elseif($log->response_status >= 400 && $log->response_status < 500)
                                                        <span class="badge bg-warning text-dark px-3 py-2">
                                                            <i class="fas fa-exclamation-triangle me-1"></i> {{ $log->response_status }}
                                                        </span>
                                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning d-block mt-1" style="font-size: 0.7rem;">
                                                            Client Error
                                                        </span>
                                                    @elseif($log->response_status >= 500)
                                                        <span class="badge bg-danger px-3 py-2">
                                                            <i class="fas fa-times-circle me-1"></i> {{ $log->response_status }}
                                                        </span>
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger d-block mt-1" style="font-size: 0.7rem;">
                                                            Server Error
                                                        </span>
                                                    @else
                                                        <span class="badge bg-secondary px-3 py-2">
                                                            {{ $log->response_status ?? 'N/A' }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($log->triggered_at)
                                                        <span class="badge bg-light text-dark border">
                                                            {{ $log->triggered_at->format('H:i:s') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($log->response_status >= 200 && $log->response_status < 300)
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-check-circle text-success me-2"></i>
                                                            <code class="text-success" style="font-size: 0.85rem;">
                                                                {{ Str::limit($log->response_body ?? 'OK', 60) }}
                                                            </code>
                                                        </div>
                                                    @else
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                                            <code class="text-danger" style="font-size: 0.85rem;">
                                                                {{ Str::limit($log->response_body ?? $log->error_message ?? 'Error', 60) }}
                                                            </code>
                                                        </div>
                                                    @endif
                                                    @if($log->error_message)
                                                        <small class="text-danger d-block mt-1">
                                                            <i class="fas fa-bug"></i> {{ Str::limit($log->error_message, 80) }}
                                                        </small>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">{{ __('Belum ada log webhook. Webhook akan mencatat log setiap kali event terjadi.') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Copy Webhook ID functionality
    const copyBtn = document.getElementById('copyWebhookIdBtn');
    const webhookIdInput = document.getElementById('webhookIdInput');

    if (copyBtn && webhookIdInput) {
        copyBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            const textToCopy = webhookIdInput.value;
            
            try {
                await navigator.clipboard.writeText(textToCopy);
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                copyBtn.className = 'btn btn-success';
                
                const tooltip = bootstrap.Tooltip.getInstance(copyBtn);
                if (tooltip) {
                    tooltip.setContent({'.tooltip-inner': 'Tersalin!'});
                }
                
                setTimeout(function() {
                    copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                    copyBtn.className = 'btn btn-outline-secondary';
                    if (tooltip) {
                        tooltip.setContent({'.tooltip-inner': 'Salin ke clipboard'});
                    }
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
        });
    }

    // Copy Webhook URL functionality
    const copyUrlBtn = document.getElementById('copyWebhookUrlBtn');
    const webhookUrlInput = document.getElementById('webhookUrlInput');

    if (copyUrlBtn && webhookUrlInput) {
        copyUrlBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            const textToCopy = webhookUrlInput.value;
            
            try {
                await navigator.clipboard.writeText(textToCopy);
                copyUrlBtn.innerHTML = '<i class="fas fa-check"></i>';
                copyUrlBtn.className = 'btn btn-success';
                
                const tooltip = bootstrap.Tooltip.getInstance(copyUrlBtn);
                if (tooltip) {
                    tooltip.setContent({'.tooltip-inner': 'URL Tersalin!'});
                }
                
                setTimeout(function() {
                    copyUrlBtn.innerHTML = '<i class="fas fa-copy"></i>';
                    copyUrlBtn.className = 'btn btn-outline-secondary';
                    if (tooltip) {
                        tooltip.setContent({'.tooltip-inner': 'Salin URL'});
                    }
                }, 2000);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
        });
    }
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('{{ __('URL berhasil disalin!') }}');
    }, function(err) {
        console.error('Failed to copy:', err);
    });
}

// Test Webhook Form Handler
document.getElementById('testWebhookForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('testWebhookBtn');
    const resultDiv = document.getElementById('testWebhookResult');
    const alertDiv = document.getElementById('testWebhookAlert');
    const messageDiv = document.getElementById('testWebhookMessage');
    
    const originalBtnText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
    
    const eventType = document.getElementById('testEventType').value;
    const messageBody = document.getElementById('testMessageBody').value;
    
    try {
        const response = await fetch('{{ route('webhooks.test', $webhook) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                event_type: eventType,
                message_body: messageBody
            })
        });
        
        const data = await response.json();
        
        resultDiv.style.display = 'block';
        
        if (data.success) {
            alertDiv.className = 'alert alert-success';
            messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
        } else {
            alertDiv.className = 'alert alert-danger';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Gagal mengirim test webhook');
        }
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            resultDiv.style.display = 'none';
        }, 5000);
        
    } catch (error) {
        resultDiv.style.display = 'block';
        alertDiv.className = 'alert alert-danger';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error: ' + error.message;
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalBtnText;
    }
});
</script>
@endpush
@endsection

