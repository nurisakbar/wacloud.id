@extends('layouts.base')

@section('title', 'Device Details')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <!-- Device Information Card -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> Informasi Device
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <tbody>
                                        <tr>
                                            <th width="35%" class="text-muted">Nama Device</th>
                                            <td>
                                                <div id="session-name-display">
                                                    <div class="d-flex align-items-center">
                                                        <strong id="session-name-text">{{ $session->session_name }}</strong>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-primary ml-2" 
                                                                id="edit-session-name-btn"
                                                                onclick="editSessionName()">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                    </div>
                                                </div>
                                                <div id="session-name-edit" style="display: none;">
                                                    <form id="session-name-form" onsubmit="saveSessionName(event)">
                                                        <div class="input-group">
                                                            <input type="text" 
                                                                   class="form-control" 
                                                                   id="session-name-input" 
                                                                   name="session_name" 
                                                                   value="{{ $session->session_name }}" 
                                                                   required 
                                                                   maxlength="255">
                                                            <div class="input-group-append">
                                                                <button type="submit" class="btn btn-success" id="save-session-name-btn">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-secondary" onclick="cancelEditSessionName()">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Device ID</th>
                                            <td>
                                                <div class="input-group">
                                                    <input type="text" 
                                                           class="form-control font-monospace" 
                                                           id="sessionIdInput" 
                                                           value="{{ $session->session_id }}" 
                                                           readonly
                                                           style="background-color: #f8f9fa; font-size: 0.9rem;">
                                                    <button class="btn btn-outline-secondary" 
                                                            type="button" 
                                                            id="copySessionIdBtn"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="top" 
                                                            title="Salin ke clipboard">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted mt-1 d-block">
                                                    <i class="fas fa-info-circle"></i> Klik tombol salin untuk menyalin Device ID
                                                </small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Status</th>
                                            <td>
                                                @if ($session->status === 'connected')
                                                    <span class="badge bg-success fs-6">
                                                        <i class="fas fa-check-circle"></i> Terhubung
                                                    </span>
                                                @elseif ($session->status === 'pairing')
                                                    <span class="badge bg-warning text-dark fs-6">
                                                        <i class="fas fa-qrcode"></i> Pairing
                                                    </span>
                                                @elseif ($session->status === 'disconnected')
                                                    <span class="badge bg-secondary fs-6">
                                                        <i class="fas fa-times-circle"></i> Terputus
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger fs-6">
                                                        <i class="fas fa-exclamation-circle"></i> Gagal
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if ($session->status === 'connected')
                                        <tr>
                                            <th class="text-muted">Webhook URL</th>
                                            <td>
                                                <div class="input-group">
                                                    <input type="text" 
                                                           class="form-control font-monospace" 
                                                           id="webhookUrlInput" 
                                                           value="{{ config('app.url', 'http://localhost:8000') }}/webhook/receive/{{ $session->session_id }}" 
                                                           readonly
                                                           style="background-color: #f8f9fa; font-size: 0.85rem;">
                                                    <button class="btn btn-outline-secondary" 
                                                            type="button" 
                                                            onclick="copyWebhookUrl()"
                                                            data-bs-toggle="tooltip" 
                                                            data-bs-placement="top" 
                                                            title="Salin Webhook URL">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted mt-1 d-block">
                                                    <i class="fas fa-info-circle"></i> Webhook ini dikonfigurasi otomatis untuk menerima pesan masuk secara real-time
                                                </small>
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <th class="text-muted">Aktivitas Terakhir</th>
                                            <td>
                                                @if ($session->last_activity_at)
                                                    @php
                                                        \Carbon\Carbon::setLocale('id');
                                                    @endphp
                                                    <i class="fas fa-clock text-muted"></i>
                                                    {{ $session->last_activity_at->diffForHumans() }}
                                                    <small class="text-muted d-block">
                                                        ({{ $session->last_activity_at->format('d/m/Y H:i') }})
                                                    </small>
                                                @else
                                                    <span class="text-muted">Belum pernah</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Terhubung Pada</th>
                                            <td>
                                                @if ($session->connected_at)
                                                    @php
                                                        \Carbon\Carbon::setLocale('id');
                                                    @endphp
                                                    <i class="fas fa-link text-success"></i>
                                                    {{ $session->connected_at->format('d/m/Y H:i') }}
                                                    <small class="text-muted d-block">
                                                        ({{ $session->connected_at->diffForHumans() }})
                                                    </small>
                                                @else
                                                    <span class="text-muted">Belum terhubung</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">Dibuat Pada</th>
                                            <td>
                                                @php
                                                    \Carbon\Carbon::setLocale('id');
                                                @endphp
                                                <i class="fas fa-calendar text-muted"></i>
                                                {{ $session->created_at->format('d/m/Y H:i') }}
                                                <small class="text-muted d-block">
                                                    ({{ $session->created_at->diffForHumans() }})
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
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-cog"></i> Aksi
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <!-- Back Button -->
                                <a href="{{ route('sessions.index') }}" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-arrow-left"></i> Kembali ke Devices
                                </a>

                                <!-- Send Message Button (Always visible if connected) -->
                                @if ($session->status === 'connected')
                                    <a href="{{ route('messages.create') }}?session_id={{ $session->id }}" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane"></i> Kirim Pesan
                                    </a>
                                @endif

                                <!-- Pairing/Stop Device Button -->
                                @if ($session->status === 'pairing')
                                    <a href="{{ route('sessions.pair', $session) }}" class="btn btn-warning w-100">
                                        <i class="fas fa-qrcode"></i> Lanjutkan Pairing
                                    </a>
                                @elseif ($session->status === 'connected')
                                    <form action="{{ route('sessions.stop', $session) }}" method="POST" class="mb-0">
                                        @csrf
                                        <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Apakah Anda yakin ingin menghentikan device ini?')">
                                            <i class="fas fa-stop"></i> Hentikan Device
                                        </button>
                                    </form>
                                    
                                    <!-- Update Webhook Button -->
                                    <button type="button" class="btn btn-info w-100" onclick="updateWebhook()">
                                        <i class="fas fa-sync"></i> Update Webhook
                                    </button>
                                @else
                                    <!-- Hubungkan Ulang Button for disconnected or failed status -->
                                    <a href="{{ route('sessions.pair', $session) }}" class="btn btn-primary w-100">
                                        <i class="fas fa-sync-alt"></i> Hubungkan Ulang
                                    </a>
                                @endif

                                <!-- Divider -->
                                <hr class="my-2">

                                <!-- Delete Device Button -->
                                <form action="{{ route('sessions.destroy', $session) }}" method="POST" class="mb-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Apakah Anda yakin ingin menghapus device ini? Tindakan ini tidak dapat dibatalkan.')">
                                        <i class="fas fa-trash"></i> Hapus Device
                                    </button>
                                </form>
                            </div>
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

    // Copy Device ID functionality
    const copyBtn = document.getElementById('copySessionIdBtn');
    const sessionIdInput = document.getElementById('sessionIdInput');

    if (copyBtn && sessionIdInput) {
        copyBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const originalHTML = copyBtn.innerHTML;
            const originalClasses = copyBtn.className;
            const textToCopy = sessionIdInput.value;
            
            let copySuccess = false;
            
            // Method 1: Try modern Clipboard API (requires HTTPS or localhost)
            if (navigator.clipboard && window.isSecureContext) {
                try {
                    await navigator.clipboard.writeText(textToCopy);
                    copySuccess = true;
                } catch (err) {
                    console.log('Clipboard API failed, trying fallback:', err);
                }
            }
            
            // Method 2: Try execCommand on the input element
            if (!copySuccess) {
                try {
                    sessionIdInput.focus();
                    sessionIdInput.select();
                    sessionIdInput.setSelectionRange(0, 99999); // For mobile devices
                    
                    if (document.execCommand('copy')) {
                        copySuccess = true;
                    }
                } catch (err) {
                    console.log('execCommand failed, trying temporary element:', err);
                }
            }
            
            // Method 3: Create temporary input element
            if (!copySuccess) {
                try {
                    const tempInput = document.createElement('input');
                    tempInput.value = textToCopy;
                    tempInput.style.position = 'fixed';
                    tempInput.style.opacity = '0';
                    tempInput.style.left = '-9999px';
                    tempInput.style.top = '-9999px';
                    document.body.appendChild(tempInput);
                    tempInput.focus();
                    tempInput.select();
                    tempInput.setSelectionRange(0, 99999);
                    
                    if (document.execCommand('copy')) {
                        copySuccess = true;
                    }
                    
                    document.body.removeChild(tempInput);
                } catch (err) {
                    console.error('All copy methods failed:', err);
                }
            }
            
            if (copySuccess) {
                // Update button icon and show feedback
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                copyBtn.className = 'btn btn-success';
                
                // Update tooltip
                const tooltip = bootstrap.Tooltip.getInstance(copyBtn);
                if (tooltip) {
                    tooltip.setContent({'.tooltip-inner': 'Tersalin!'});
                }
                
                // Show toast notification
                showToast('Device ID berhasil disalin!', 'success');

                // Reset after 2 seconds
                setTimeout(function() {
                    copyBtn.innerHTML = originalHTML;
                    copyBtn.className = originalClasses;
                    if (tooltip) {
                        tooltip.setContent({'.tooltip-inner': 'Salin ke clipboard'});
                    }
                }, 2000);
            } else {
                // All methods failed - select text for manual copy
                sessionIdInput.focus();
                sessionIdInput.select();
                sessionIdInput.setSelectionRange(0, 99999);
                
                // Show error feedback
                copyBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                copyBtn.className = 'btn btn-danger';
                
                const tooltip = bootstrap.Tooltip.getInstance(copyBtn);
                if (tooltip) {
                    tooltip.setContent({'.tooltip-inner': 'Gagal menyalin. Teks sudah dipilih, tekan Ctrl+C'});
                }
                
                showToast('Gagal menyalin otomatis. Teks sudah dipilih, tekan Ctrl+C (atau Cmd+C di Mac) untuk menyalin.', 'warning');
                
                setTimeout(function() {
                    copyBtn.innerHTML = originalHTML;
                    copyBtn.className = originalClasses;
                    if (tooltip) {
                        tooltip.setContent({'.tooltip-inner': 'Salin ke clipboard'});
                    }
                }, 3000);
            }
        });
    }
    
    // Toast notification function
    function showToast(message, type) {
        // Remove existing toast if any
        const existingToast = document.querySelector('.toast-notification');
        if (existingToast) {
            existingToast.remove();
        }
        
        const bgColor = type === 'success' ? 'bg-success' : type === 'warning' ? 'bg-warning' : 'bg-danger';
        const toast = document.createElement('div');
        toast.className = 'toast-notification ' + bgColor;
        toast.style.cssText = 'position: fixed; top: 20px; right: 20px; padding: 12px 20px; color: white; border-radius: 4px; z-index: 9999; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: fadeIn 0.3s;';
        toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') + ' mr-2"></i>' + message;
        
        document.body.appendChild(toast);
        
        // Auto remove after 3 seconds
        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 3000);
    }
    
    // Add fadeIn animation if not exists
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = '@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }';
        document.head.appendChild(style);
    }
});

// Edit Device Name Functions - Global scope
function editSessionName() {
    document.getElementById('session-name-display').style.display = 'none';
    document.getElementById('session-name-edit').style.display = 'block';
    document.getElementById('session-name-input').focus();
    document.getElementById('session-name-input').select();
}

function cancelEditSessionName() {
    document.getElementById('session-name-edit').style.display = 'none';
    document.getElementById('session-name-display').style.display = 'block';
    // Reset input value to original
    document.getElementById('session-name-input').value = document.getElementById('session-name-text').textContent;
}

function saveSessionName(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const saveBtn = document.getElementById('save-session-name-btn');
    const originalBtnHtml = saveBtn.innerHTML;
    
    // Disable button and show loading
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch('{{ route("sessions.update", $session) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-HTTP-Method-Override': 'PUT',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            session_name: formData.get('session_name'),
            _method: 'PUT'
        })
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        // Check content type
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // If not JSON, try to get text
            return response.text().then(text => {
                throw new Error('Response is not JSON: ' + text.substring(0, 100));
            });
        }
    })
    .then(data => {
        if (data.success) {
            // Update display text
            document.getElementById('session-name-text').textContent = data.session_name;
            // Hide edit form, show display
            document.getElementById('session-name-edit').style.display = 'none';
            document.getElementById('session-name-display').style.display = 'block';
            
            // Show success message
            if (typeof showToast === 'function') {
                showToast(data.message || 'Nama device berhasil diperbarui.', 'success');
            } else {
                alert(data.message || 'Nama device berhasil diperbarui.');
            }
        } else {
            throw new Error(data.message || 'Gagal memperbarui nama device.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMessage = error.message || 'Gagal memperbarui nama device. Silakan coba lagi.';
        if (typeof showToast === 'function') {
            showToast(errorMessage, 'error');
        } else {
            alert(errorMessage);
        }
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalBtnHtml;
    });
}

// Copy Webhook URL function
function copyWebhookUrl() {
    const input = document.getElementById('webhookUrlInput');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile devices
    document.execCommand('copy');
    
    // Show feedback
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i>';
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-success');
    
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
    
    if (typeof showToast === 'function') {
        showToast('Webhook URL berhasil disalin', 'success');
    } else {
        alert('Webhook URL berhasil disalin');
    }
}

// Update Webhook function
function updateWebhook() {
    if (!confirm('Update webhook akan memperbarui konfigurasi webhook untuk menerima pesan masuk secara real-time. Lanjutkan?')) {
        return;
    }
    
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    fetch('{{ route("sessions.updateWebhook", $session) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const webhookUrl = data.webhook_url || '{{ config("app.url", "http://localhost:8000") }}/webhook/receive/{{ $session->session_id }}';
            document.getElementById('webhookUrlInput').value = webhookUrl;
            
            alert('Webhook URL: ' + webhookUrl + '\n\nCatatan:\n- Pastikan URL ini dapat diakses dari WAHA server\n- Jika WAHA berjalan di Docker, gunakan host.docker.internal atau IP host\n- Webhook dikonfigurasi otomatis saat session dibuat');
            if (typeof showToast === 'function') {
                showToast('Webhook berhasil diupdate', 'success');
            }
        } else {
            throw new Error(data.message || 'Gagal update webhook');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>
@endpush
@endsection

