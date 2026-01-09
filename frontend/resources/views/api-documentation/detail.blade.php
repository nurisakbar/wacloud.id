@extends('layouts.base')

@section('title', 'API Documentation - ' . ucfirst($module))

@push('styles')
<style>
    .api-code {
        background-color: #111827;
        color: #e5e7eb;
        border-radius: .35rem;
        padding: .75rem 1rem;
        font-size: 0.85rem;
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        overflow-x: auto;
        margin-bottom: 1rem;
    }
    .api-code code {
        color: inherit;
        background: transparent;
        padding: 0;
        border: 0;
        white-space: pre;
        display: block;
    }
    .endpoint-item {
        border-bottom: 1px solid #e5e7eb;
        padding: 1.5rem;
    }
    .endpoint-item:last-child {
        border-bottom: none;
    }
    .endpoint-method-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        margin-right: 0.75rem;
    }
    .badge-get { background-color: #3b82f6; color: white; }
    .badge-post { background-color: #10b981; color: white; }
    .badge-put { background-color: #f59e0b; color: white; }
    .badge-delete { background-color: #ef4444; color: white; }
    .badge-patch { background-color: #8b5cf6; color: white; }
    .api-endpoint-url {
        color: #22c55e;
        word-break: break-all;
        font-family: 'Courier New', monospace;
    }
    .code-tabs {
        margin: 1rem 0;
    }
    .code-tabs-header {
        display: flex;
        gap: 0.5rem;
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 1rem;
    }
    .code-tab {
        padding: 0.5rem 1rem;
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        font-weight: 600;
        color: #6b7280;
        transition: all 0.2s;
    }
    .code-tab:hover {
        color: #374151;
    }
    .code-tab.active {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
    }
    .code-tab-content {
        display: none;
    }
    .code-tab-content.active {
        display: block;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- Back Button -->
            <div class="mb-3">
                <a href="{{ route('api-docs.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> {{ __('Kembali ke Daftar Modul') }}
                </a>
            </div>

            <!-- API Key Format Notice -->
            <div class="alert alert-info mb-4">
                <h6 class="alert-heading">
                    <i class="fas fa-key"></i> {{ __('Format API Key') }}
                </h6>
                <p class="mb-2">
                    {{ __('Semua contoh di bawah menggunakan placeholder') }} <code>YOUR_API_KEY</code> {{ __('atau') }} <code>waha_your_api_key_here</code>.
                </p>
                <p class="mb-0">
                    <strong>{{ __('Format API Key yang benar:') }}</strong> <code>waha_</code> + 59 karakter random (total 64 karakter).
                    {{ __('Dapatkan API Key Anda di menu') }} <a href="{{ route('api-keys.index') }}" class="alert-link">{{ __('API Keys') }}</a>.
                </p>
            </div>

            <!-- Module Content -->
            @include('api-documentation.detail.' . $module, ['baseUrl' => $baseUrl, 'apiKeys' => $apiKeys])
        </div>
    </div>
</div>

@push('scripts')
<script>
function switchCodeTab(button, tabId) {
    // Hide all tab contents
    const tabContents = button.closest('.code-tabs').querySelectorAll('.code-tab-content');
    tabContents.forEach(content => content.classList.remove('active'));
    
    // Remove active class from all tabs
    const tabs = button.closest('.code-tabs-header').querySelectorAll('.code-tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Show selected tab content
    document.getElementById(tabId).classList.add('active');
    button.classList.add('active');
}
</script>
@endpush
@endsection

