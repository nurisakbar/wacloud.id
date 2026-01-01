@extends('layouts.base')

@section('title', 'Manajemen Quota')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-wallet"></i> Manajemen Quota
            </h1>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <!-- Current Quota Cards -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-wallet"></i> Quota Saat Ini
                    </h6>
                    <a href="{{ route('quota.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Beli Quota
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card border-left-primary shadow-sm h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Saldo (IDR)
                                    </div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800">
                                        Rp {{ number_format($quota->balance, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-left-info shadow-sm h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Quota Teks
                                    </div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800">
                                        {{ number_format($quota->text_quota, 0, ',', '.') }} pesan
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card border-left-success shadow-sm h-100">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Quota Multimedia
                                    </div>
                                    <div class="h4 mb-0 font-weight-bold text-gray-800">
                                        {{ number_format($quota->multimedia_quota, 0, ',', '.') }} pesan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase History -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history"></i> Riwayat Pembelian
                    </h6>
                </div>
                <div class="card-body">
                    @if($purchases->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Nomor Pembelian</th>
                                        <th>Tanggal</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchases as $purchase)
                                        <tr>
                                            <td><strong>{{ $purchase->purchase_number }}</strong></td>
                                            <td>{{ $purchase->created_at->format('d/m/Y H:i') }}</td>
                                            <td><strong>Rp {{ number_format($purchase->amount, 0, ',', '.') }}</strong></td>
                                            <td>
                                                @if($purchase->status === 'completed')
                                                    <span class="badge badge-success">Selesai</span>
                                                @elseif($purchase->status === 'pending_verification')
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-clock"></i> Menunggu Konfirmasi Admin
                                                    </span>
                                                @elseif($purchase->status === 'waiting_payment')
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-money-bill-wave"></i> Menunggu Pembayaran
                                                    </span>
                                                @elseif($purchase->status === 'pending')
                                                    <span class="badge badge-warning">Menunggu</span>
                                                @elseif($purchase->status === 'failed')
                                                    <span class="badge badge-danger">Gagal</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ ucfirst($purchase->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    @if($purchase->status === 'completed')
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check-circle"></i> Selesai
                                                        </span>
                                                    @elseif($purchase->status === 'pending_verification')
                                                        <span class="badge badge-info">
                                                            <i class="fas fa-clock"></i> Menunggu verifikasi admin
                                                        </span>
                                                    @elseif($purchase->payment_method === 'xendit')
                                                        {{-- Xendit Payment --}}
                                                        @if($purchase->xendit_invoice_url)
                                                            @if(in_array($purchase->status, ['pending', 'waiting_payment']))
                                                                <a href="{{ $purchase->xendit_invoice_url }}" 
                                                                   target="_blank" 
                                                                   class="btn btn-sm btn-success"
                                                                   title="Klik untuk melakukan pembayaran via Xendit">
                                                                    <i class="fas fa-credit-card"></i> Bayar via Xendit
                                                                </a>
                                                            @else
                                                                <a href="{{ $purchase->xendit_invoice_url }}" 
                                                                   target="_blank" 
                                                                   class="btn btn-sm btn-outline-info"
                                                                   title="Lihat invoice Xendit">
                                                                    <i class="fas fa-external-link-alt"></i> Lihat Invoice
                                                                </a>
                                                            @endif
                                                        @elseif(in_array($purchase->status, ['pending', 'waiting_payment']))
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-primary" 
                                                                    onclick="createXenditInvoice({{ $purchase->id }})"
                                                                    id="btn-create-invoice-{{ $purchase->id }}"
                                                                    title="Buat invoice pembayaran Xendit">
                                                                <i class="fas fa-plus-circle"></i> Buat Invoice
                                                            </button>
                                                        @else
                                                            <span class="badge badge-warning">
                                                                <i class="fas fa-exclamation-triangle"></i> Invoice belum dibuat
                                                            </span>
                                                        @endif
                                                    @elseif($purchase->payment_method === 'manual')
                                                        {{-- Manual Payment --}}
                                                        @if(in_array($purchase->status, ['waiting_payment', 'pending']))
                                                            <a href="{{ route('quota.confirm-payment', $purchase) }}" 
                                                               class="btn btn-sm btn-primary"
                                                               title="Konfirmasi pembayaran manual dengan upload bukti transfer">
                                                                <i class="fas fa-upload"></i> Konfirmasi Pembayaran
                                                            </a>
                                                        @elseif($purchase->status === 'pending_verification')
                                                            <span class="badge badge-info">
                                                                <i class="fas fa-clock"></i> Menunggu verifikasi admin
                                                            </span>
                                                        @else
                                                            <span class="badge badge-secondary">
                                                                <i class="fas fa-info-circle"></i> Menunggu verifikasi
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $purchases->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada riwayat pembelian.</p>
                            <a href="{{ route('quota.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Beli Quota
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <!-- Pricing Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tag"></i> Informasi Harga
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-comment text-primary"></i> Quota Teks (Premium)</span>
                            <strong>Rp {{ number_format($pricing->text_without_watermark_price, 0, ',', '.') }}/pesan</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-image text-info"></i> Quota Multimedia</span>
                            <strong>Rp {{ number_format($pricing->multimedia_price, 0, ',', '.') }}/pesan</strong>
                        </div>
                    </div>
                    <hr>
                    <div class="alert alert-info mb-0">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            Pembelian minimum: <strong>Rp 1.000</strong>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt"></i> Aksi Cepat
                    </h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('quota.create') }}" class="btn btn-primary btn-block mb-2">
                        <i class="fas fa-shopping-cart"></i> Beli Quota
                    </a>
                    <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-credit-card"></i> Lihat Tagihan
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function createXenditInvoice(purchaseId) {
    const btn = document.getElementById('btn-create-invoice-' + purchaseId);
    const originalHtml = btn.innerHTML;
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membuat Invoice...';
    
    fetch('/payment/create-invoice', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            purchase_id: purchaseId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.invoice_url) {
            // Redirect to Xendit payment page
            window.open(data.invoice_url, '_blank');
            
            // Reload page after a moment to update the invoice URL
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Gagal membuat invoice: ' + (data.error || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat membuat invoice. Silakan coba lagi.');
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>
@endpush
@endsection
