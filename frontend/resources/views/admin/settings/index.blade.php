@extends('layouts.base')

@section('title', 'Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1">Settings</h1>
            <p class="text-muted mb-0">Pengaturan umum sistem</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bell"></i> Pengaturan Notifikasi
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.settings.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="notification_api_key" class="font-weight-bold">
                                <i class="fas fa-key"></i> API Key Notifikasi
                            </label>
                            <input type="text" 
                                   class="form-control @error('notification_api_key') is-invalid @enderror" 
                                   id="notification_api_key" 
                                   name="notification_api_key" 
                                   value="{{ old('notification_api_key', $notificationApiKey) }}" 
                                   placeholder="Masukkan API Key untuk notifikasi">
                            <small class="form-text text-muted">
                                API Key yang digunakan untuk mengirim notifikasi ke client (contoh: password reset via WhatsApp).
                            </small>
                            @error('notification_api_key')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="notification_device_id" class="font-weight-bold">
                                <i class="fas fa-mobile-alt"></i> Device ID Notifikasi
                            </label>
                            <input type="text" 
                                   class="form-control @error('notification_device_id') is-invalid @enderror" 
                                   id="notification_device_id" 
                                   name="notification_device_id" 
                                   value="{{ old('notification_device_id', $notificationDeviceId) }}" 
                                   placeholder="Masukkan Device ID untuk notifikasi">
                            <small class="form-text text-muted">
                                Device ID yang digunakan untuk mengirim notifikasi ke client (contoh: password reset via WhatsApp).
                            </small>
                            @error('notification_device_id')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="notification_base_url" class="font-weight-bold">
                                <i class="fas fa-link"></i> Base URL Notifikasi
                            </label>
                            <input type="url" 
                                   class="form-control @error('notification_base_url') is-invalid @enderror" 
                                   id="notification_base_url" 
                                   name="notification_base_url" 
                                   value="{{ old('notification_base_url', $notificationBaseUrl) }}" 
                                   placeholder="https://app.wacloud.id/api/v1">
                            <small class="form-text text-muted">
                                Base URL yang digunakan untuk mengirim notifikasi ke client (contoh: https://app.wacloud.id/api/v1).
                            </small>
                            @error('notification_base_url')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <hr>

                        <!-- Test Connection Result -->
                        <div id="test-connection-result" class="alert" style="display: none;" role="alert">
                            <div id="test-connection-message"></div>
                            <div id="test-connection-details" class="mt-2 small"></div>
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Simpan Pengaturan
                            </button>
                            <button type="button" id="test-connection-btn" class="btn btn-info">
                                <i class="fas fa-plug mr-2"></i>Test Koneksi
                            </button>
                            <a href="{{ route('admin.dashboard.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Information Card -->
            <div class="card shadow mb-4 border-left-info">
                <div class="card-body">
                    <h6 class="font-weight-bold text-info mb-3">
                        <i class="fas fa-info-circle"></i> Informasi
                    </h6>
                    <ul class="mb-0 pl-3">
                        <li class="mb-2">API Key, Device ID, dan Base URL digunakan untuk mengirim notifikasi sistem seperti password reset via WhatsApp.</li>
                        <li class="mb-2">Pastikan API Key dan Device ID yang dimasukkan valid dan aktif.</li>
                        <li class="mb-0">Semua konfigurasi dibaca langsung dari database. Pastikan semua field diisi dengan benar.</li>
                    </ul>
                </div>
            </div>
            {{-- ── Debug Mode Card ─────────────────────────────────────────── --}}
            <div class="card shadow mb-4 border-left-{{ $debugModeEnabled ? 'warning' : 'secondary' }}" id="debug-card">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold {{ $debugModeEnabled ? 'text-warning' : 'text-secondary' }}">
                        <i class="fas fa-bug mr-1"></i> Debug Mode
                    </h6>
                    <span id="debug-badge"
                          class="badge badge-{{ $debugModeEnabled ? 'warning' : 'secondary' }} badge-pill px-3 py-2"
                          style="font-size:.8rem;">
                        {{ $debugModeEnabled ? 'ON' : 'OFF' }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Aktifkan untuk mencatat log detail ke <code>storage/logs/laravel.log</code>.
                        Mencakup: pembuatan/pair/hapus device, pengiriman pesan, dan webhook.
                        Bisa diaktifkan di environment manapun (local maupun production).
                    </p>

                    {{-- Alert feedback --}}
                    <div id="debug-alert" class="alert py-2 small" role="alert"
                         style="{{ $debugModeEnabled ? '' : 'display:none;' }}
                                background: #fff3cd; border-color: #ffc107; color: #856404;">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Debug mode sedang aktif.</strong>
                        Log verbose ditulis ke <code>laravel.log</code> — nonaktifkan jika tidak dibutuhkan.
                    </div>

                    {{-- Toggle --}}
                    <div class="d-flex align-items-center mt-3">
                        <div class="custom-control custom-switch mr-3">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="debug-toggle"
                                   {{ $debugModeEnabled ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold" for="debug-toggle">
                                <span id="debug-label">
                                    {{ $debugModeEnabled ? 'Debug mode aktif' : 'Debug mode nonaktif' }}
                                </span>
                            </label>
                        </div>
                        <span id="debug-spinner" class="spinner-border spinner-border-sm text-warning ml-2" role="status" style="display:none;">
                            <span class="sr-only">Loading...</span>
                        </span>
                    </div>

                    <div id="debug-result" class="mt-2 small text-muted"></div>

                    <hr>
                    <div class="small text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Log dapat dilihat di:
                        <a href="{{ route('admin.logs.index') }}" target="_blank">
                            <i class="fas fa-external-link-alt ml-1"></i> Admin › Log Viewer
                        </a>
                        atau via terminal:
                        <code>tail -f storage/logs/laravel.log | grep '\[DEBUG\]'</code>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quota Information Card --}}
        <div class="col-lg-4">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-chart-pie"></i> Informasi Quota WACloud
                    </h6>
                </div>
                <div class="card-body" id="quota-stats">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Memuat data quota dari WACloud API...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch quota statistics from WACloud API
    fetch('{{ route("admin.settings.quota-stats") }}', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const quota = data.data;
            const quotaStatsHtml = `
                <div class="mb-3">
                    <div class="font-weight-bold text-muted mb-1">
                        <i class="fas fa-coins"></i> Balance
                    </div>
                    <div class="h5 text-primary">
                        Rp ${formatNumber(quota.total_balance || 0)}
                    </div>
                </div>

                <div class="mb-3">
                    <div class="font-weight-bold text-muted mb-1">
                        <i class="fas fa-comment"></i> Text Quota
                    </div>
                    <div class="h5 text-info">
                        ${formatNumber(quota.total_text_quota || 0)}
                    </div>
                </div>

                <div class="mb-3">
                    <div class="font-weight-bold text-muted mb-1">
                        <i class="fas fa-image"></i> Multimedia Quota
                    </div>
                    <div class="h5 text-success">
                        ${formatNumber(quota.total_multimedia_quota || 0)}
                    </div>
                </div>

                <div class="mb-0">
                    <div class="font-weight-bold text-muted mb-1">
                        <i class="fas fa-gift"></i> Free Text Quota
                    </div>
                    <div class="h5 text-warning">
                        ${formatNumber(quota.total_free_text_quota || 0)}
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> Data dari WACloud API
                    </small>
                </div>
            `;
            document.getElementById('quota-stats').innerHTML = quotaStatsHtml;
        } else {
            const errorMsg = data.message || data.error || 'Gagal memuat data quota';
            document.getElementById('quota-stats').innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Peringatan:</strong> ${errorMsg}
                    ${data.details ? '<br><small class="mt-2 d-block">Pastikan API Key dan Base URL sudah dikonfigurasi dengan benar.</small>' : ''}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error fetching quota stats:', error);
        document.getElementById('quota-stats').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> Terjadi kesalahan saat memuat data quota dari WACloud API.
                <br><small class="mt-2 d-block">Pastikan API Key dan Base URL sudah dikonfigurasi dengan benar.</small>
            </div>
        `;
    });

    // Test Connection Handler
    const testConnectionBtn = document.getElementById('test-connection-btn');
    const testResultDiv = document.getElementById('test-connection-result');
    const testMessageDiv = document.getElementById('test-connection-message');
    const testDetailsDiv = document.getElementById('test-connection-details');

    testConnectionBtn.addEventListener('click', function() {
        const apiKey = document.getElementById('notification_api_key').value;
        const deviceId = document.getElementById('notification_device_id').value;
        const baseUrl = document.getElementById('notification_base_url').value;

        // Disable button and show loading
        testConnectionBtn.disabled = true;
        testConnectionBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Menguji Koneksi...';
        testResultDiv.style.display = 'none';

        // Prepare form data
        const formData = new FormData();
        formData.append('api_key', apiKey);
        formData.append('device_id', deviceId);
        formData.append('base_url', baseUrl);
        formData.append('_token', '{{ csrf_token() }}');

        // Send test connection request
        fetch('{{ route("admin.settings.test-connection") }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            testResultDiv.style.display = 'block';
            
            if (data.success) {
                testResultDiv.className = 'alert alert-success';
                testMessageDiv.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Koneksi Berhasil!</strong> ' + (data.message || 'Konfigurasi valid dan dapat terhubung ke WACloud.');
                
                let detailsHtml = '<strong>Detail:</strong><ul class="mb-0 mt-2">';
                if (data.details) {
                    if (data.details.status_code) {
                        detailsHtml += `<li>Status Code: ${data.details.status_code}</li>`;
                    }
                    if (data.details.device_id) {
                        detailsHtml += `<li>Device ID: ${data.details.device_id}</li>`;
                    }
                    if (data.details.base_url) {
                        detailsHtml += `<li>Base URL: ${data.details.base_url}</li>`;
                    }
                }
                detailsHtml += '</ul>';
                testDetailsDiv.innerHTML = detailsHtml;
            } else {
                testResultDiv.className = 'alert alert-danger';
                testMessageDiv.innerHTML = '<i class="fas fa-times-circle"></i> <strong>Koneksi Gagal!</strong> ' + (data.message || data.error || 'Tidak dapat terhubung ke WACloud.');
                
                let detailsHtml = '<strong>Detail Error:</strong><ul class="mb-0 mt-2">';
                if (data.details) {
                    if (data.details.status_code) {
                        detailsHtml += `<li>Status Code: ${data.details.status_code}</li>`;
                    }
                    if (data.details.message) {
                        detailsHtml += `<li>Pesan: ${data.details.message}</li>`;
                    }
                    if (data.details.device_id) {
                        detailsHtml += `<li>Device ID: ${data.details.device_id}</li>`;
                    }
                    if (data.details.base_url) {
                        detailsHtml += `<li>Base URL: ${data.details.base_url}</li>`;
                    }
                }
                if (data.error) {
                    detailsHtml += `<li>Error: ${data.error}</li>`;
                }
                detailsHtml += '</ul>';
                testDetailsDiv.innerHTML = detailsHtml;
            }
        })
        .catch(error => {
            console.error('Error testing connection:', error);
            testResultDiv.style.display = 'block';
            testResultDiv.className = 'alert alert-danger';
            testMessageDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> <strong>Terjadi Kesalahan!</strong> Tidak dapat menguji koneksi.';
            testDetailsDiv.innerHTML = '<strong>Error:</strong> ' + error.message;
        })
        .finally(() => {
            // Re-enable button
            testConnectionBtn.disabled = false;
            testConnectionBtn.innerHTML = '<i class="fas fa-plug mr-2"></i>Test Koneksi';
        });
    });

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    // ── Debug Mode Toggle ─────────────────────────────────────────────────
    const debugToggle  = document.getElementById('debug-toggle');
    const debugSpinner = document.getElementById('debug-spinner');
    const debugBadge   = document.getElementById('debug-badge');
    const debugLabel   = document.getElementById('debug-label');
    const debugAlert   = document.getElementById('debug-alert');
    const debugCard    = document.getElementById('debug-card');
    const debugResult  = document.getElementById('debug-result');

    if (debugToggle) {
        debugToggle.addEventListener('change', function () {
            debugToggle.disabled = true;
            debugSpinner.style.display = 'inline-block';
            debugResult.textContent = '';

            fetch('{{ route("admin.settings.toggle-debug") }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const on = data.enabled;

                    // Badge
                    debugBadge.textContent = on ? 'ON' : 'OFF';
                    debugBadge.className = 'badge badge-' + (on ? 'warning' : 'secondary') + ' badge-pill px-3 py-2';

                    // Card border + header color
                    debugCard.className = 'card shadow mb-4 border-left-' + (on ? 'warning' : 'secondary');
                    debugCard.querySelector('.card-header h6').className =
                        'm-0 font-weight-bold ' + (on ? 'text-warning' : 'text-secondary');

                    // Label
                    debugLabel.textContent = on ? 'Debug mode aktif' : 'Debug mode nonaktif';

                    // Alert banner
                    debugAlert.style.display = on ? 'block' : 'none';

                    // Result feedback
                    debugResult.innerHTML =
                        '<span class="text-' + (on ? 'warning' : 'secondary') + '">' +
                        '<i class="fas fa-' + (on ? 'check' : 'check') + '-circle mr-1"></i>' +
                        data.message + '</span>';
                } else {
                    // Revert toggle on error
                    debugToggle.checked = !debugToggle.checked;
                    debugResult.innerHTML =
                        '<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>' +
                        (data.message || 'Gagal mengubah status debug.') + '</span>';
                }
            })
            .catch(() => {
                debugToggle.checked = !debugToggle.checked;
                debugResult.innerHTML =
                    '<span class="text-danger"><i class="fas fa-times-circle mr-1"></i>Koneksi error.</span>';
            })
            .finally(() => {
                debugToggle.disabled = false;
                debugSpinner.style.display = 'none';
            });
        });
    }
    // ─────────────────────────────────────────────────────────────────────
});
</script>
@endpush
@endsection

