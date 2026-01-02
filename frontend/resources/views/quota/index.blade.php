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
        </div>
    </div>

    <!-- Purchase History - Full Width -->
    <div class="row">
        <div class="col-12">
            <!-- Purchase History -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history"></i> Riwayat Pembelian
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="purchasesTable" class="table table-bordered table-hover" style="width:100%">
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
                                <!-- DataTables will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('#purchasesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("quota.purchases.datatable") }}',
            type: 'GET',
            error: function(xhr, error, thrown) {
                console.error('DataTables Ajax Error:', error);
                alert('Terjadi kesalahan saat memuat data. Silakan refresh halaman.');
            }
        },
        columns: [
            { data: 0, name: 'purchase_number', orderable: true, searchable: true },
            { data: 1, name: 'created_at', orderable: true, searchable: false },
            { data: 2, name: 'amount', orderable: true, searchable: true },
            { data: 3, name: 'status', orderable: true, searchable: true },
            { data: 4, name: 'action', orderable: false, searchable: false },
        ],
        order: [[1, 'desc']], // Default order by date (descending)
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
            processing: '<i class="fas fa-spinner fa-spin"></i> Memuat data...',
            emptyTable: 'Belum ada riwayat pembelian.',
            zeroRecords: 'Tidak ada data yang cocok dengan pencarian Anda.',
            lengthMenu: 'Tampilkan _MENU_ data per halaman',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
            infoFiltered: '(disaring dari _MAX_ total data)',
            search: 'Cari:',
            paginate: {
                first: 'Pertama',
                last: 'Terakhir',
                next: 'Selanjutnya',
                previous: 'Sebelumnya'
            }
        },
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        drawCallback: function() {
            // Re-initialize any dynamic elements after table redraw
        }
    });
});

function createXenditInvoice(purchaseId) {
    const btn = document.getElementById('btn-create-invoice-' + purchaseId);
    if (!btn) {
        console.error('Button not found for purchase ID:', purchaseId);
        return;
    }
    
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
            
            // Reload DataTable after a moment to update the invoice URL
            setTimeout(() => {
                $('#purchasesTable').DataTable().ajax.reload(null, false);
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
