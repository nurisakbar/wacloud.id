@extends('layouts.base')

@section('title', 'WhatsApp Devices')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-comments mr-2"></i>Devices WhatsApp
                    </h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('api-keys.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-key mr-2"></i>Api Key
                        </a>
                        <a href="{{ route('quota.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-shopping-cart mr-2"></i>Beli Quota
                        </a>
                        <a href="{{ route('sessions.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-2"></i>Buat Device Baru
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if ($sessions->count() > 0)
                        <div class="row">
                            @foreach ($sessions as $session)
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card shadow-sm h-100 border-left-{{ $session->status === 'connected' ? 'success' : ($session->status === 'pairing' ? 'warning' : ($session->status === 'disconnected' ? 'secondary' : 'danger')) }}">
                                        <div class="card-body">
                                            <!-- Device Header -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="flex-grow-1">
                                                    <h5 class="card-title mb-1">
                                                        <i class="fas fa-comment-dots text-primary mr-2"></i>
                                                        {{ $session->session_name }}
                                                    </h5>
                                                    @if($session->phone_number)
                                                        <p class="text-muted mb-0 small">
                                                            <i class="fas fa-phone mr-1"></i>{{ $session->phone_number }}
                                                        </p>
                                                    @endif
                                                </div>
                                                <div>
                                                    @if ($session->status === 'connected')
                                                        <span class="badge badge-success badge-lg">
                                                            <i class="fas fa-check-circle"></i> Terhubung
                                                        </span>
                                                    @elseif ($session->status === 'pairing')
                                                        <span class="badge badge-warning badge-lg">
                                                            <i class="fas fa-qrcode"></i> Pairing
                                                        </span>
                                                    @elseif ($session->status === 'disconnected')
                                                        <span class="badge badge-secondary badge-lg">
                                                            <i class="fas fa-times-circle"></i> Terputus
                                                        </span>
                                                    @else
                                                        <span class="badge badge-danger badge-lg">
                                                            <i class="fas fa-exclamation-circle"></i> Gagal
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Device Info -->
                                            <div class="mb-3">
                                                <div class="small text-gray-500 mb-1">
                                                    <i class="fas fa-clock mr-1"></i>Aktivitas Terakhir
                                                </div>
                                                <div class="font-weight-bold">
                                                    @php
                                                        \Carbon\Carbon::setLocale('id');
                                                    @endphp
                                                    {{ $session->last_activity_at ? $session->last_activity_at->diffForHumans() : 'Belum pernah' }}
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="small text-gray-500 mb-1">
                                                    <i class="fas fa-calendar mr-1"></i>Dibuat
                                                </div>
                                                <div class="font-weight-bold">
                                                    {{ $session->created_at->format('d/m/Y H:i') }}
                                                </div>
                                            </div>

                                            @if($session->session_id)
                                            <div class="mb-3">
                                                <div class="small text-gray-500 mb-1">
                                                    <i class="fas fa-key mr-1"></i>Device ID
                                                </div>
                                                <div class="font-monospace small text-truncate" style="max-width: 100%;" title="{{ $session->session_id }}">
                                                    {{ $session->session_id }}
                                                </div>
                                            </div>
                                            @endif

                                            <!-- Actions -->
                                            <hr class="my-3">
                                            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                                <a href="{{ route('sessions.show', $session) }}" class="btn btn-sm btn-info" style="flex: 1 1 auto; min-width: 0; padding-top: 0.375rem; padding-bottom: 0.375rem;">
                                                    <i class="fas fa-eye mr-1"></i>Lihat
                                                </a>
                                                
                                                @if ($session->status === 'pairing')
                                                    <a href="{{ route('sessions.pair', $session) }}" class="btn btn-sm btn-warning" style="flex: 1 1 auto; min-width: 0; padding-top: 0.375rem; padding-bottom: 0.375rem;">
                                                        <i class="fas fa-qrcode mr-1"></i>Pair
                                                    </a>
                                                @endif
                                                
                                                @if ($session->status === 'connected')
                                                    <form action="{{ route('sessions.stop', $session) }}" method="POST" style="flex: 1 1 auto; min-width: 0; margin: 0;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning w-100" style="padding-top: 0.375rem; padding-bottom: 0.375rem;" onclick="return confirm('Apakah Anda yakin ingin menghentikan device ini?')">
                                                            <i class="fas fa-stop mr-1"></i>Hentikan
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                <form action="{{ route('sessions.destroy', $session) }}" method="POST" style="flex: 1 1 auto; min-width: 0; margin: 0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger w-100" style="padding-top: 0.375rem; padding-bottom: 0.375rem;" onclick="return confirm('Apakah Anda yakin ingin menghapus device ini?')">
                                                        <i class="fas fa-trash mr-1"></i>Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $sessions->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox text-muted" style="font-size: 64px;"></i>
                            <h5 class="mt-3 text-muted">Belum ada device</h5>
                            <p class="text-muted mb-4">Buat device pertama Anda untuk mulai menggunakan WhatsApp API.</p>
                            <a href="{{ route('sessions.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>Buat Device Pertama
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge-lg {
    font-size: 0.85rem;
    padding: 0.35rem 0.65rem;
}

.border-left-success {
    border-left: 4px solid #1cc88a !important;
}

.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}

.border-left-secondary {
    border-left: 4px solid #858796 !important;
}

.border-left-danger {
    border-left: 4px solid #e74a3b !important;
}

.gap-2 {
    gap: 0.5rem;
}

.flex-fill {
    flex: 1 1 auto;
    min-width: 0;
}

@media (max-width: 768px) {
    .flex-fill {
        flex: 1 1 100%;
        margin-bottom: 0.5rem;
    }
}
</style>
@endsection
