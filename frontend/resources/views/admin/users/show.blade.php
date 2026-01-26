@extends('layouts.base')

@section('title', 'User Details')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-user"></i> User Details
                </h1>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Users List
                </a>
            </div>
        </div>
    </div>

    <!-- User Information -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">User Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Name:</div>
                        <div class="col-md-9">
                            <div class="d-flex align-items-center">
                                @if($user->avatar)
                                    <img src="{{ $user->avatar }}" alt="{{ $user->name }}" 
                                         class="rounded-circle mr-2" width="48" height="48">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2" 
                                         style="width: 48px; height: 48px; font-size: 20px;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                                <span>{{ $user->name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Email:</div>
                        <div class="col-md-9">{{ $user->email }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Phone:</div>
                        <div class="col-md-9">{{ $user->phone ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Role:</div>
                        <div class="col-md-9">
                            <span class="badge badge-secondary">{{ ucfirst($user->role ?? 'user') }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Registered:</div>
                        <div class="col-md-9">{{ $user->created_at->format('d M Y H:i') }}</div>
                    </div>
                    @if($user->last_login_at)
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Last Login:</div>
                        <div class="col-md-9">{{ $user->last_login_at->format('d M Y H:i') }}</div>
                    </div>
                    @endif
                    @if($user->referral_code)
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Referral Code:</div>
                        <div class="col-md-9">
                            <code>{{ $user->referral_code }}</code>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quota Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quota Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="font-weight-bold text-muted mb-1">Balance:</div>
                            <div class="h5 text-primary">Rp {{ number_format($quota->balance, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="font-weight-bold text-muted mb-1">Text Quota:</div>
                            <div class="h5 text-info">{{ number_format($quota->text_quota, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="font-weight-bold text-muted mb-1">Multimedia Quota:</div>
                            <div class="h5 text-success">{{ number_format($quota->multimedia_quota, 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="font-weight-bold text-muted mb-1">Free Text Quota:</div>
                            <div class="h5 text-warning">{{ number_format($quota->free_text_quota, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Up Quota -->
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3 bg-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-plus-circle"></i> Top Up Quota
                    </h6>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> 
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.users.top-up-quota', $user) }}" id="top-up-form">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="balance" class="font-weight-bold">
                                    <i class="fas fa-coins"></i> Balance (Rp)
                                </label>
                                <input type="number" 
                                       class="form-control @error('balance') is-invalid @enderror" 
                                       id="balance" 
                                       name="balance" 
                                       value="{{ old('balance', 0) }}" 
                                       min="0" 
                                       step="0.01"
                                       placeholder="0">
                                <small class="form-text text-muted">Tambahkan balance untuk user</small>
                                @error('balance')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="text_quota" class="font-weight-bold">
                                    <i class="fas fa-comment"></i> Text Quota
                                </label>
                                <input type="number" 
                                       class="form-control @error('text_quota') is-invalid @enderror" 
                                       id="text_quota" 
                                       name="text_quota" 
                                       value="{{ old('text_quota', 0) }}" 
                                       min="0" 
                                       placeholder="0">
                                <small class="form-text text-muted">Tambahkan text quota</small>
                                @error('text_quota')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="multimedia_quota" class="font-weight-bold">
                                    <i class="fas fa-image"></i> Multimedia Quota
                                </label>
                                <input type="number" 
                                       class="form-control @error('multimedia_quota') is-invalid @enderror" 
                                       id="multimedia_quota" 
                                       name="multimedia_quota" 
                                       value="{{ old('multimedia_quota', 0) }}" 
                                       min="0" 
                                       placeholder="0">
                                <small class="form-text text-muted">Tambahkan multimedia quota</small>
                                @error('multimedia_quota')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="free_text_quota" class="font-weight-bold">
                                    <i class="fas fa-gift"></i> Free Text Quota
                                </label>
                                <input type="number" 
                                       class="form-control @error('free_text_quota') is-invalid @enderror" 
                                       id="free_text_quota" 
                                       name="free_text_quota" 
                                       value="{{ old('free_text_quota', 0) }}" 
                                       min="0" 
                                       placeholder="0">
                                <small class="form-text text-muted">Tambahkan free text quota</small>
                                @error('free_text_quota')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="notes" class="font-weight-bold">
                                    <i class="fas fa-sticky-note"></i> Notes (Optional)
                                </label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" 
                                          name="notes" 
                                          rows="2" 
                                          placeholder="Catatan untuk top up ini (opsional)">{{ old('notes') }}</textarea>
                                <small class="form-text text-muted">Tambahkan catatan untuk audit trail</small>
                                @error('notes')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                <i class="fas fa-info-circle"></i> Pastikan setidaknya satu jenis quota diisi
                            </div>
                            <button type="submit" class="btn btn-success" id="submit-btn">
                                <i class="fas fa-plus-circle"></i> Top Up Quota
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subscription Information -->
            @if($user->activeSubscription || $user->subscriptionPlan)
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Subscription Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Plan:</div>
                        <div class="col-md-9">
                            <span class="badge badge-success">{{ $user->subscriptionPlan->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                    @if($user->activeSubscription)
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Status:</div>
                        <div class="col-md-9">
                            <span class="badge badge-{{ $user->activeSubscription->status === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($user->activeSubscription->status) }}
                            </span>
                        </div>
                    </div>
                    @if($user->activeSubscription->current_period_end)
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Expires At:</div>
                        <div class="col-md-9">{{ $user->activeSubscription->current_period_end->format('d M Y H:i') }}</div>
                    </div>
                    @endif
                    @elseif($user->subscription_status)
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Status:</div>
                        <div class="col-md-9">
                            <span class="badge badge-{{ $user->subscription_status === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($user->subscription_status) }}
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- API Key & Device IDs -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-key"></i> API Key & Device IDs
                    </h6>
                </div>
                <div class="card-body">
                    <!-- API Keys -->
                    <div class="mb-4">
                        <h6 class="font-weight-bold mb-3">API Keys</h6>
                        @if($user->apiKeys->count() > 0)
                            @foreach($user->apiKeys as $apiKey)
                                <div class="mb-3 p-3 border rounded">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="font-weight-bold text-muted small mb-1">API Key Name:</div>
                                            <div>{{ $apiKey->name ?? 'Default' }}</div>
                                        </div>
                                        <div>
                                            @if($apiKey->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="font-weight-bold text-muted small mb-1">API Key:</div>
                                        <div class="input-group">
                                            <input type="text" 
                                                   class="form-control font-monospace" 
                                                   id="api-key-{{ $apiKey->id }}" 
                                                   value="{{ $apiKey->plain_key ?? $apiKey->key_prefix . '...' }}" 
                                                   readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" 
                                                        type="button" 
                                                        onclick="copyToClipboard('api-key-{{ $apiKey->id }}', this)">
                                                    <i class="fas fa-copy"></i> Copy
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @if($apiKey->last_used_at)
                                        <div class="small text-muted">
                                            Last used: {{ $apiKey->last_used_at->format('d M Y H:i') }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted mb-0">No API keys found</p>
                        @endif
                    </div>

                    <!-- Device IDs -->
                    <div>
                        <h6 class="font-weight-bold mb-3">Device IDs (Session IDs)</h6>
                        @if($user->whatsappSessions->count() > 0)
                            @foreach($user->whatsappSessions as $session)
                                <div class="mb-2 p-2 border rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="font-weight-bold text-muted small mb-1">Device Name:</div>
                                            <div>{{ $session->session_name }}</div>
                                        </div>
                                        <div>
                                            <span class="badge badge-{{ $session->status === 'connected' ? 'success' : ($session->status === 'pairing' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($session->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <div class="font-weight-bold text-muted small mb-1">Device ID:</div>
                                        <div class="input-group">
                                            <input type="text" 
                                                   class="form-control font-monospace small" 
                                                   id="device-id-{{ $session->id }}" 
                                                   value="{{ $session->session_id }}" 
                                                   readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary btn-sm" 
                                                        type="button" 
                                                        onclick="copyToClipboard('device-id-{{ $session->id }}', this)">
                                                    <i class="fas fa-copy"></i> Copy
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted mb-0">No devices found</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Total Devices</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_sessions'] }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Connected Devices</div>
                        <div class="h5 mb-0 font-weight-bold text-success">{{ $stats['connected_sessions'] }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Total Messages</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_messages'], 0, ',', '.') }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Messages Sent</div>
                        <div class="h5 mb-0 font-weight-bold text-primary">{{ number_format($stats['messages_sent'], 0, ',', '.') }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-1">Messages Received</div>
                        <div class="h5 mb-0 font-weight-bold text-info">{{ number_format($stats['messages_received'], 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Devices List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Devices</h6>
        </div>
        <div class="card-body">
            @if($user->whatsappSessions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Device Name</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->whatsappSessions as $session)
                                <tr>
                                    <td>{{ $session->session_name }}</td>
                                    <td>
                                        <span class="badge badge-{{ $session->status === 'connected' ? 'success' : ($session->status === 'pairing' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($session->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $session->created_at->format('d M Y H:i') }}</td>
                                    <td>{{ $session->last_activity_at ? $session->last_activity_at->format('d M Y H:i') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted text-center py-4">No devices found</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Top Up Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('top-up-form');
    const submitBtn = document.getElementById('submit-btn');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const balance = parseFloat(document.getElementById('balance').value) || 0;
            const textQuota = parseInt(document.getElementById('text_quota').value) || 0;
            const multimediaQuota = parseInt(document.getElementById('multimedia_quota').value) || 0;
            const freeTextQuota = parseInt(document.getElementById('free_text_quota').value) || 0;
            
            if (balance === 0 && textQuota === 0 && multimediaQuota === 0 && freeTextQuota === 0) {
                e.preventDefault();
                alert('Please provide at least one quota type to top up.');
                return false;
            }
            
            // Confirm before submitting
            if (!confirm('Are you sure you want to top up quota for this user?')) {
                e.preventDefault();
                return false;
            }
            
            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        });
    }
});

function copyToClipboard(inputId, button) {
    const input = document.getElementById(inputId);
    const textToCopy = input.value;
    
    // Use modern Clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(textToCopy).then(() => {
            showCopyFeedback(button);
        }).catch(err => {
            console.error('Failed to copy:', err);
            fallbackCopy(input, button);
        });
    } else {
        // Fallback for older browsers
        fallbackCopy(input, button);
    }
}

function fallbackCopy(input, button) {
    input.select();
    input.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        showCopyFeedback(button);
    } catch (err) {
        console.error('Failed to copy:', err);
        alert('Failed to copy to clipboard');
    }
}

function showCopyFeedback(button) {
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i> Copied!';
    button.classList.remove('btn-outline-secondary');
    button.classList.add('btn-success');
    
    setTimeout(() => {
        button.innerHTML = originalHtml;
        button.classList.remove('btn-success');
        button.classList.add('btn-outline-secondary');
    }, 2000);
}
</script>
@endpush
@endsection

