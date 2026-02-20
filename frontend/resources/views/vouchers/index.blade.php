@extends('layouts.base')

@section('title', 'Redeem Voucher')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-ticket-alt"></i> Redeem Voucher
            </h1>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-gift"></i> Masukkan Kode Voucher
                    </h6>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('vouchers.redeem') }}" id="redeem-form">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="code" class="font-weight-bold">Kode Voucher</label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code') }}" 
                                   required
                                   placeholder="Masukkan kode voucher"
                                   style="text-transform: uppercase; font-size: 1.2rem; letter-spacing: 2px; text-align: center;">
                            @error('code')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted mt-2">
                                <i class="fas fa-info-circle"></i> Setiap kode voucher hanya bisa digunakan 1 kali per user
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-gift"></i> Redeem Voucher
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Riwayat Redeem</h6>
                </div>
                <div class="card-body">
                    @if($redemptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th>Text Quota</th>
                                        <th>Multimedia Quota</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($redemptions as $redemption)
                                        <tr>
                                            <td><code>{{ $redemption->voucher->code }}</code></td>
                                            <td>{{ $redemption->voucher->name }}</td>
                                            <td>{{ number_format($redemption->text_quota_received, 0, ',', '.') }}</td>
                                            <td>{{ number_format($redemption->multimedia_quota_received, 0, ',', '.') }}</td>
                                            <td>{{ $redemption->created_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $redemptions->links() }}
                        </div>
                    @else
                        <p class="text-muted text-center py-4 mb-0">
                            <i class="fas fa-inbox fa-2x mb-2"></i><br>
                            Belum ada voucher yang diredeem
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('code').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>
@endpush
@endsection
