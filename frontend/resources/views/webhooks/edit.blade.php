@extends('layouts.base')

@section('title', 'Edit Webhook')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-edit text-primary"></i> {{ __('Edit Webhook') }}
                    </h2>
                    <p class="text-muted mb-0">{{ __('Perbarui konfigurasi webhook Anda') }}</p>
                </div>
                <a href="{{ route('webhooks.show', $webhook) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> {{ __('Kembali') }}
                </a>
            </div>

            <div class="row">
                <!-- Main Form -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-cog"></i> {{ __('Edit Konfigurasi Webhook') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-exclamation-circle"></i> {{ __('Terjadi Kesalahan') }}
                                    </h6>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('webhooks.update', $webhook) }}">
                                @csrf
                                @method('PUT')
                                
                                <!-- Webhook Name -->
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        {{ __('Webhook Name') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name" 
                                           name="name" 
                                           required 
                                           value="{{ old('name', $webhook->name) }}"
                                           placeholder="{{ __('Contoh: Production Webhook, Test Webhook') }}">
                                    <small class="form-text text-muted">
                                        {{ __('Nama untuk mengidentifikasi webhook ini. Nama ini hanya untuk referensi Anda sendiri.') }}
                                    </small>
                                </div>

                                <!-- Webhook URL -->
                                <div class="mb-3">
                                    <label for="url" class="form-label">
                                        {{ __('Webhook URL') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="url" 
                                           class="form-control" 
                                           id="url" 
                                           name="url" 
                                           required 
                                           value="{{ old('url', $webhook->url) }}"
                                           placeholder="https://example.com/webhook">
                                    <div class="mt-2">
                                        <div class="alert alert-light border mb-2">
                                            <small class="fw-bold d-block mb-2">
                                                <i class="fas fa-link text-primary"></i> {{ __('Contoh URL:') }}
                                            </small>
                                            <ul class="mb-0 small">
                                                <li class="mb-1">
                                                    <strong>Production:</strong> 
                                                    <code class="text-primary">https://api.yourapp.com/webhook/whatsapp</code>
                                                </li>
                                                <li class="mb-1">
                                                    <strong>Testing (webhook.site):</strong> 
                                                    <code class="text-info">https://webhook.site/unique-id-here</code>
                                                    <a href="https://webhook.site" target="_blank" class="btn btn-sm btn-outline-info ms-2">
                                                        <i class="fas fa-external-link-alt"></i> Buka webhook.site
                                                    </a>
                                                </li>
                                                <li>
                                                    <strong>Local (ngrok):</strong> 
                                                    <code class="text-warning">https://abc123.ngrok.io/webhook</code>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> 
                                        {{ __('URL endpoint yang akan menerima webhook. Harus menggunakan HTTPS (kecuali local dengan ngrok) dan dapat diakses dari internet.') }}
                                    </small>
                                </div>

                                <!-- Device Selection -->
                                <div class="mb-3">
                                    <label for="session_id" class="form-label">
                                        {{ __('Device') }} <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select @error('session_id') is-invalid @enderror" id="session_id" name="session_id" required>
                                        <option value="">{{ __('Pilih Device') }}</option>
                                        @foreach($sessions as $session)
                                            @php
                                                // Check if this device already has a webhook (excluding current webhook)
                                                $hasWebhook = \App\Models\Webhook::where('user_id', Auth::id())
                                                    ->where('session_id', $session->id)
                                                    ->where('id', '!=', $webhook->id)
                                                    ->exists();
                                            @endphp
                                            <option value="{{ $session->id }}" 
                                                    {{ old('session_id', $webhook->session_id) == $session->id ? 'selected' : '' }}
                                                    {{ $hasWebhook ? 'disabled' : '' }}>
                                                {{ $session->session_name }} ({{ $session->session_id }})
                                                @if($hasWebhook)
                                                    - {{ __('Sudah memiliki webhook') }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('session_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        {{ __('Pilih device yang akan menerima event. Setiap device hanya boleh memiliki 1 webhook.') }}
                                    </small>
                                </div>

                                <!-- Events Selection -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        {{ __('Events') }} <span class="text-danger">*</span>
                                    </label>
                                    <div class="border rounded p-3 bg-light">
                                        <div class="form-check mb-3 p-3 bg-white rounded border">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="events[]" 
                                                   value="message" 
                                                   id="event_message" 
                                                   {{ in_array('message', old('events', $webhook->events)) ? 'checked' : '' }}
                                                   required>
                                            <label class="form-check-label w-100" for="event_message">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-primary px-3 py-2 me-2">
                                                        <i class="fas fa-envelope"></i> {{ __('Message Events') }}
                                                    </span>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                                        <i class="fas fa-comment-dots"></i> Pesan Masuk/Keluar
                                                    </span>
                                                </div>
                                                <p class="mb-2 text-muted">
                                                    {{ __('Menerima notifikasi ketika ada pesan masuk atau keluar, termasuk:') }}
                                                </p>
                                                <ul class="small text-muted mb-0">
                                                    <li>Pesan teks (text messages)</li>
                                                    <li>Pesan gambar (images)</li>
                                                    <li>Pesan video (videos)</li>
                                                    <li>Pesan dokumen (documents)</li>
                                                    <li>Pesan suara (voice notes)</li>
                                                    <li>Pesan lokasi, kontak, dan lainnya</li>
                                                </ul>
                                            </label>
                                        </div>
                                        <div class="form-check mb-3 p-3 bg-white rounded border">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="events[]" 
                                                   value="status" 
                                                   id="event_status"
                                                   {{ in_array('status', old('events', $webhook->events)) ? 'checked' : '' }}>
                                            <label class="form-check-label w-100" for="event_status">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-warning text-dark px-3 py-2 me-2">
                                                        <i class="fas fa-info-circle"></i> {{ __('Status Events') }}
                                                    </span>
                                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning">
                                                        <i class="fas fa-check-double"></i> Status Pesan
                                                    </span>
                                                </div>
                                                <p class="mb-2 text-muted">
                                                    {{ __('Menerima notifikasi tentang perubahan status pesan yang dikirim:') }}
                                                </p>
                                                <ul class="small text-muted mb-0">
                                                    <li><span class="badge bg-secondary">pending</span> - Pesan sedang dikirim</li>
                                                    <li><span class="badge bg-info">sent</span> - Pesan terkirim ke server</li>
                                                    <li><span class="badge bg-success">delivered</span> - Pesan terkirim ke penerima</li>
                                                    <li><span class="badge bg-primary">read</span> - Pesan dibaca oleh penerima</li>
                                                    <li><span class="badge bg-danger">failed</span> - Pesan gagal dikirim</li>
                                                </ul>
                                            </label>
                                        </div>
                                        <div class="form-check p-3 bg-white rounded border">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="events[]" 
                                                   value="session" 
                                                   id="event_session"
                                                   {{ in_array('session', old('events', $webhook->events)) ? 'checked' : '' }}>
                                            <label class="form-check-label w-100" for="event_session">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-info px-3 py-2 me-2">
                                                        <i class="fas fa-mobile-alt"></i> {{ __('Device Events') }}
                                                    </span>
                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                                        <i class="fas fa-link"></i> Status Device
                                                    </span>
                                                </div>
                                                <p class="mb-2 text-muted">
                                                    {{ __('Menerima notifikasi tentang perubahan status device/session:') }}
                                                </p>
                                                <ul class="small text-muted mb-0">
                                                    <li><span class="badge bg-success">connected</span> - Device terhubung</li>
                                                    <li><span class="badge bg-warning text-dark">pairing</span> - Sedang pairing (scan QR)</li>
                                                    <li><span class="badge bg-secondary">disconnected</span> - Device terputus</li>
                                                    <li><span class="badge bg-danger">failed</span> - Device gagal terhubung</li>
                                                </ul>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="alert alert-warning mb-0 mt-2">
                                        <small>
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <strong>{{ __('Penting:') }}</strong> {{ __('Pilih minimal satu event. Anda dapat memilih beberapa event sekaligus untuk menerima semua jenis notifikasi.') }}
                                        </small>
                                    </div>
                                </div>

                                <!-- Secret -->
                                <div class="mb-3">
                                    <label for="secret" class="form-label">
                                        {{ __('Secret Key') }} 
                                        <small class="text-muted">({{ __('Optional') }})</small>
                                        <span class="badge bg-warning text-dark ms-2">
                                            <i class="fas fa-shield-alt"></i> {{ __('Direkomendasikan') }}
                                        </span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-key text-muted"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control" 
                                               id="secret" 
                                               name="secret" 
                                               placeholder="{{ __('Kosongkan jika tidak ingin mengubah secret key') }}"
                                               minlength="16">
                                        <button type="button" 
                                                class="btn btn-outline-secondary" 
                                                onclick="generateSecret()"
                                                title="Generate random secret">
                                            <i class="fas fa-random"></i> Generate
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        <div class="alert alert-light border">
                                            <small class="fw-bold d-block mb-2">
                                                <i class="fas fa-info-circle text-info"></i> {{ __('Tentang Secret Key:') }}
                                            </small>
                                            <ul class="mb-0 small">
                                                <li class="mb-1">{{ __('Kosongkan field ini jika tidak ingin mengubah secret key yang sudah ada') }}</li>
                                                <li class="mb-1">{{ __('Secret key digunakan untuk memverifikasi bahwa webhook request benar-benar berasal dari sistem kami') }}</li>
                                                <li class="mb-1">{{ __('Panjang minimal 16 karakter. Disarankan menggunakan random string yang kuat.') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-shield-alt text-warning"></i> 
                                        {{ __('Meskipun optional, sangat disarankan untuk menambahkan secret key untuk keamanan webhook Anda.') }}
                                    </small>
                                </div>

                                <!-- Active Status -->
                                <div class="mb-3">
                                    <div class="card border-success">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="is_active" 
                                                       id="is_active" 
                                                       value="1" 
                                                       {{ old('is_active', $webhook->is_active) ? 'checked' : '' }}>
                                                <label class="form-check-label w-100" for="is_active">
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-success px-3 py-2 me-2">
                                                            <i class="fas fa-check-circle"></i> {{ __('Aktifkan Webhook') }}
                                                        </span>
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                            <i class="fas fa-bolt"></i> {{ __('Akan Menerima Event') }}
                                                        </span>
                                                    </div>
                                                </label>
                                            </div>
                                            <small class="text-muted d-block mt-2">
                                                <i class="fas fa-info-circle text-info"></i> 
                                                {{ __('Webhook yang tidak aktif tidak akan menerima event. Anda dapat mengaktifkan atau menonaktifkan webhook kapan saja.') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="{{ route('webhooks.show', $webhook) }}" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times"></i> {{ __('Batal') }}
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> {{ __('Simpan Perubahan') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Information -->
                <div class="col-md-4 mb-4">
                    <!-- Current Webhook Info -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> {{ __('Informasi Saat Ini') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="small mb-2">
                                <strong>{{ __('Nama:') }}</strong><br>
                                {{ $webhook->name }}
                            </p>
                            <p class="small mb-2">
                                <strong>{{ __('URL:') }}</strong><br>
                                <code class="text-break" style="font-size: 0.75rem;">{{ $webhook->url }}</code>
                            </p>
                            <p class="small mb-2">
                                <strong>{{ __('Status:') }}</strong><br>
                                @if($webhook->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Tidak Aktif</span>
                                @endif
                            </p>
                            <p class="small mb-0">
                                <strong>{{ __('Events:') }}</strong><br>
                                @foreach($webhook->events as $event)
                                    <span class="badge bg-primary me-1">{{ $event }}</span>
                                @endforeach
                            </p>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-lightbulb"></i> {{ __('Tips') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="small mb-0">
                                <li class="mb-2">
                                    {{ __('Pastikan URL webhook dapat diakses sebelum menyimpan perubahan') }}
                                </li>
                                <li class="mb-2">
                                    {{ __('Gunakan test webhook untuk memverifikasi bahwa webhook berfungsi') }}
                                </li>
                                <li>
                                    {{ __('Jangan lupa untuk mengupdate secret key jika diperlukan') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function generateSecret() {
    // Generate random secret (32 characters)
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let secret = '';
    for (let i = 0; i < 32; i++) {
        secret += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    document.getElementById('secret').value = secret;
    
    // Show toast notification
    const toast = document.createElement('div');
    toast.className = 'alert alert-success position-fixed top-0 end-0 m-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = '<i class="fas fa-check-circle"></i> Secret key berhasil di-generate!';
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="{{ route('webhooks.update', $webhook) }}"]');
    const eventCheckboxes = document.querySelectorAll('input[name="events[]"]');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const checkedEvents = Array.from(eventCheckboxes).filter(cb => cb.checked);
            
            if (checkedEvents.length === 0) {
                e.preventDefault();
                alert('{{ __('Pilih minimal satu event untuk webhook.') }}');
                return false;
            }

            // Validate URL
            const urlInput = document.getElementById('url');
            const url = urlInput.value.trim();
            
            if (url && !url.startsWith('http://') && !url.startsWith('https://')) {
                e.preventDefault();
                alert('{{ __('URL webhook harus dimulai dengan http:// atau https://') }}');
                urlInput.focus();
                return false;
            }

            // Validate secret if provided
            const secretInput = document.getElementById('secret');
            const secret = secretInput.value.trim();
            
            if (secret && secret.length < 16) {
                e.preventDefault();
                alert('{{ __('Secret key minimal 16 karakter') }}');
                secretInput.focus();
                return false;
            }
        });
    }
});
</script>
@endpush
@endsection














