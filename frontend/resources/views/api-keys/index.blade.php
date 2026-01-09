@extends('layouts.base')

@section('title', 'API Keys')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-key mr-2"></i>API Key
                    </h6>
                    <form action="{{ route('api-keys.regenerate') }}" method="POST" class="d-inline" id="regenerateApiKeyForm">
                        @csrf
                        <button type="button" class="btn btn-warning btn-sm" id="regenerateApiKeyBtn">
                            <i class="fas fa-sync-alt mr-2"></i>Regenerate Key
                        </button>
                    </form>
                </div>
                <div class="card-body">

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($apiKey)
                        <div class="row">
                            <!-- Left Column - API Key Display -->
                            <div class="col-lg-8">
                                <div class="card border-left-primary mb-4">
                                    <div class="card-body">
                                        <h6 class="mb-3">
                                            <i class="fas fa-key text-primary mr-2"></i>API Key Anda
                                        </h6>
                                        
                                        @php
                                            $userApiKeys = session('user_api_keys', []);
                                            $plainKey = $userApiKeys[$apiKey->id] ?? null;
                                            
                                            // Also check flash for backward compatibility
                                            if (!$plainKey && session('api_key_plain') && $apiKey->id === session('api_key_id')) {
                                                $plainKey = session('api_key_plain');
                                            }
                                        @endphp
                                        
                                        @if($plainKey)
                                            {{-- Show masked key with show/hide toggle --}}
                                            @php
                                                $keyLength = strlen($plainKey);
                                                $showChars = 8; // Show first and last 8 characters
                                                $maskedKey = substr($plainKey, 0, $showChars) . str_repeat('•', max(0, $keyLength - ($showChars * 2))) . substr($plainKey, -$showChars);
                                            @endphp
                                            <div class="input-group mb-3">
                                                <input type="text" 
                                                       class="form-control font-monospace" 
                                                       id="apiKeyDisplay" 
                                                       value="{{ $maskedKey }}" 
                                                       data-full-key="{{ $plainKey }}"
                                                       data-masked-key="{{ $maskedKey }}"
                                                       data-is-masked="true"
                                                       readonly 
                                                       style="font-size: 14px; letter-spacing: 1px;">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-secondary" 
                                                            type="button" 
                                                            id="toggleApiKey" 
                                                            onclick="toggleApiKeyVisibility()"
                                                            title="Tampilkan/Sembunyikan API Key">
                                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                                    </button>
                                                    <button class="btn btn-primary" 
                                                            type="button" 
                                                            onclick="copyApiKey('apiKeyDisplay', this)" 
                                                            style="min-width: 100px;">
                                                        <i class="fas fa-copy mr-2"></i>Salin
                                                    </button>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-lightbulb mr-1"></i>
                                                Gunakan di Postman: <code>X-Api-Key: [your_key]</code>
                                            </small>
                                        @else
                                            {{-- Plain key not in session - user needs to regenerate to see it --}}
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                <strong>API Key Anda sudah dibuat.</strong><br>
                                                <small>Untuk melihat API key lengkap, silakan klik tombol "Regenerate Key" di atas. 
                                                Key yang sudah ada akan tetap berfungsi sampai Anda regenerate.</small>
                                            </div>
                                            <div class="card border-left-secondary mt-3">
                                                <div class="card-body">
                                                    <div class="small text-gray-500 mb-1">Key Prefix</div>
                                                    <div class="font-monospace font-weight-bold">{{ $apiKey->key_prefix }}...</div>
                                                    <small class="text-muted">Gunakan prefix ini untuk mengidentifikasi key Anda</small>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- API Key Information -->
                                <div class="card border-left-info">
                                    <div class="card-body">
                                        <h6 class="mb-3">
                                            <i class="fas fa-info-circle text-info mr-2"></i>Informasi Key
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="small text-gray-500 mb-1">Status</div>
                                                <div>
                                                    @if($apiKey->is_active)
                                                        <span class="badge badge-success badge-lg">Aktif</span>
                                                    @else
                                                        <span class="badge badge-secondary badge-lg">Tidak Aktif</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="small text-gray-500 mb-1">Dibuat</div>
                                                <div class="font-weight-bold">{{ $apiKey->created_at->format('d F Y, H:i') }}</div>
                                                <small class="text-muted">{{ $apiKey->created_at->diffForHumans() }}</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="small text-gray-500 mb-1">
                                                    <i class="fas fa-check-circle mr-1"></i>Terakhir Digunakan
                                                </div>
                                                <div class="font-weight-bold">
                                                    {{ $apiKey->last_used_at ? $apiKey->last_used_at->diffForHumans() : 'Belum pernah' }}
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="small text-gray-500 mb-1">
                                                    <i class="fas fa-history mr-1"></i>Terakhir Diubah
                                                </div>
                                                <div class="font-weight-bold">
                                                    @if($apiKey->updated_at && $apiKey->updated_at->ne($apiKey->created_at))
                                                        {{ $apiKey->updated_at->format('d F Y, H:i') }}
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock mr-1"></i>{{ $apiKey->updated_at->diffForHumans() }}
                                                        </small>
                                                    @else
                                                        <span class="text-muted">
                                                            <i class="fas fa-info-circle mr-1"></i>Belum pernah diubah
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($apiKey->expires_at)
                                            <div class="col-md-6 mb-3">
                                                <div class="small text-gray-500 mb-1">Kedaluwarsa</div>
                                                <div class="font-weight-bold">{{ $apiKey->expires_at->format('d F Y') }}</div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Usage Instructions -->
                            <div class="col-lg-4">
                                <div class="card border-left-success">
                                    <div class="card-body">
                                        <h6 class="mb-3">
                                            <i class="fas fa-book text-success mr-2"></i>Cara Menggunakan
                                        </h6>
                                        <ol class="pl-3">
                                            <li class="mb-2">Salin API key Anda dari atas</li>
                                            <li class="mb-2">Tambahkan ke header request Anda:<br>
                                                <code class="small">X-Api-Key: your_key</code>
                                            </li>
                                            <li class="mb-2">Lakukan request API ke:<br>
                                                <code class="small">{{ config('app.url', 'http://localhost:8000') }}/api/v1/*</code>
                                            </li>
                                        </ol>
                                        <hr>
                                        <div class="small text-muted">
                                            <i class="fas fa-shield-alt mr-1"></i>
                                            <strong>Tips Keamanan:</strong>
                                            <ul class="mt-2 mb-0 pl-3">
                                                <li>Jangan pernah membagikan API key Anda</li>
                                                <li>Regenerate jika API key terkompromi</li>
                                                <li>Gunakan HTTPS di production</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <p class="mb-0">API key tidak ditemukan. Satu akan dibuat secara otomatis.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endpush

@push('scripts')
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/**
 * Handle API key regeneration with SweetAlert confirmation
 */
document.addEventListener('DOMContentLoaded', function() {
    const regenerateBtn = document.getElementById('regenerateApiKeyBtn');
    const regenerateForm = document.getElementById('regenerateApiKeyForm');
    
    if (regenerateBtn && regenerateForm) {
        regenerateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Regenerate API Key?',
                html: '<div class="text-left">' +
                      '<p class="mb-2">Apakah Anda yakin ingin regenerate API key?</p>' +
                      '<div class="alert alert-warning text-left mb-0">' +
                      '<i class="fas fa-exclamation-triangle"></i> <strong>Peringatan:</strong><br>' +
                      '<ul class="mb-0 mt-2 pl-3">' +
                      '<li>API key lama akan berhenti bekerja segera</li>' +
                      '<li>Semua aplikasi yang menggunakan key lama akan gagal</li>' +
                      '<li>Anda perlu mengupdate key di semua tempat yang menggunakannya</li>' +
                      '</ul>' +
                      '</div>' +
                      '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f6c23e',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-sync-alt mr-2"></i>Ya, Regenerate',
                cancelButtonText: '<i class="fas fa-times mr-2"></i>Batal',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    popup: 'text-left'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Sedang regenerate API key',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the form
                    regenerateForm.submit();
                }
            });
        });
    }
});

/**
 * Toggle API key visibility
 */
function toggleApiKeyVisibility() {
    const input = document.getElementById('apiKeyDisplay');
    const toggleIcon = document.getElementById('toggleIcon');
    const toggleBtn = document.getElementById('toggleApiKey');
    
    if (input) {
        const fullKey = input.getAttribute('data-full-key');
        const maskedKey = input.getAttribute('data-masked-key');
        const isMasked = input.getAttribute('data-is-masked') === 'true';
        
        if (isMasked) {
            // Show full key
            input.value = fullKey;
            input.setAttribute('data-is-masked', 'false');
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
            toggleBtn.classList.remove('btn-outline-secondary');
            toggleBtn.classList.add('btn-secondary');
            input.style.color = '#28a745'; // Green color when shown
        } else {
            // Hide key (mask it)
            input.value = maskedKey;
            input.setAttribute('data-is-masked', 'true');
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
            toggleBtn.classList.remove('btn-secondary');
            toggleBtn.classList.add('btn-outline-secondary');
            input.style.color = ''; // Reset color
        }
    }
}

/**
 * Copy API key from input field
 */
function copyApiKey(inputId, button) {
    const input = document.getElementById(inputId);
    if (input) {
        // Always copy the full key from data attribute or value
        const fullKey = input.getAttribute('data-full-key') || input.value;
        copyText(fullKey, button);
    }
}

/**
 * Copy text to clipboard (general purpose)
 */
function copyText(text, button) {
    // Use modern Clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(function() {
            // Show feedback
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check text-success"></i> Tersalin!';
            button.classList.add('btn-success');
            button.classList.remove('btn-primary', 'btn-outline-secondary');
            
            // Reset after 2 seconds
            setTimeout(function() {
                button.innerHTML = originalHtml;
                button.classList.remove('btn-success');
                button.classList.add('btn-primary');
            }, 2000);
            
            showToast('Berhasil disalin ke clipboard!', 'success');
        }).catch(function(err) {
            console.error('Failed to copy:', err);
            // Fallback to execCommand
            copyTextFallback(text, button);
        });
    } else {
        // Fallback for older browsers
        copyTextFallback(text, button);
    }
}

/**
 * Fallback copy method using execCommand
 */
function copyTextFallback(text, button) {
    // Create temporary input element
    const tempInput = document.createElement('input');
    tempInput.value = text;
    tempInput.style.position = 'fixed';
    tempInput.style.opacity = '0';
    document.body.appendChild(tempInput);
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        // Copy to clipboard
        document.execCommand('copy');
        
        // Show feedback
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check text-success"></i> {{ __('Copied!') }}';
        button.classList.add('btn-success');
        button.classList.remove('btn-primary', 'btn-outline-secondary');
        
        // Reset after 2 seconds
        setTimeout(function() {
            button.innerHTML = originalHtml;
            button.classList.remove('btn-success');
            button.classList.add('btn-primary');
        }, 2000);
        
        // Show toast notification
        showToast('Berhasil disalin ke clipboard!', 'success');
    } catch (err) {
        console.error('Failed to copy:', err);
        showToast('Gagal menyalin. Silakan coba lagi.', 'error');
    }
    
    // Remove temporary input
    document.body.removeChild(tempInput);
}

/**
 * Show toast notification
 */
function showToast(message, type) {
    // Remove existing toast if any
    $('.toast-notification').remove();
    
    const bgColor = type === 'success' ? 'bg-success' : 'bg-danger';
    const toast = $('<div>')
        .addClass('toast-notification ' + bgColor)
        .css({
            'position': 'fixed',
            'top': '20px',
            'right': '20px',
            'padding': '12px 20px',
            'color': 'white',
            'border-radius': '4px',
            'z-index': '9999',
            'box-shadow': '0 4px 6px rgba(0,0,0,0.1)',
            'animation': 'fadeIn 0.3s'
        })
        .html('<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' mr-2"></i>' + message);
    
    $('body').append(toast);
    
    // Auto remove after 3 seconds
    setTimeout(function() {
        toast.fadeOut(300, function() {
            $(this).remove();
        });
    }, 3000);
}

// Add fadeIn animation
if (!$('#toast-styles').length) {
    $('head').append('<style id="toast-styles">@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }</style>');
}
</script>
@endpush
