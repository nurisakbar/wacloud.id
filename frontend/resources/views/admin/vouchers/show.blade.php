@extends('layouts.base')

@section('title', 'Detail Voucher')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-ticket-alt"></i> Detail Voucher
                </h1>
                <div>
                    <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Voucher</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Kode:</div>
                        <div class="col-md-9">
                            <code class="h5">{{ $voucher->code }}</code>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Nama:</div>
                        <div class="col-md-9">{{ $voucher->name }}</div>
                    </div>
                    @if($voucher->description)
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Deskripsi:</div>
                        <div class="col-md-9">{{ $voucher->description }}</div>
                    </div>
                    @endif
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Text Quota:</div>
                        <div class="col-md-9">
                            <span class="h5 text-info">{{ number_format($voucher->text_quota, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Multimedia Quota:</div>
                        <div class="col-md-9">
                            <span class="h5 text-success">{{ number_format($voucher->multimedia_quota, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Penggunaan:</div>
                        <div class="col-md-9">
                            {{ $voucher->used_count }} 
                            @if($voucher->max_uses)
                                / {{ $voucher->max_uses }}
                            @else
                                / ∞
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Status:</div>
                        <div class="col-md-9">
                            @if($voucher->isValid())
                                <span class="badge badge-success">Aktif</span>
                            @elseif(!$voucher->is_active)
                                <span class="badge badge-secondary">Tidak Aktif</span>
                            @elseif($voucher->expires_at && $voucher->expires_at->isPast())
                                <span class="badge badge-danger">Kadaluarsa</span>
                            @elseif($voucher->max_uses && $voucher->used_count >= $voucher->max_uses)
                                <span class="badge badge-warning">Habis</span>
                            @else
                                <span class="badge badge-secondary">Tidak Valid</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Kadaluarsa:</div>
                        <div class="col-md-9">
                            @if($voucher->expires_at)
                                @if($voucher->expires_at->isPast())
                                    <span class="text-danger">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        {{ $voucher->expires_at->format('d M Y H:i') }}
                                        <br><small>(Sudah kadaluarsa)</small>
                                    </span>
                                @else
                                    {{ $voucher->expires_at->format('d M Y H:i') }}
                                    <br><small class="text-muted">({{ $voucher->expires_at->diffForHumans() }})</small>
                                @endif
                            @else
                                <span class="text-muted">Tidak ada kadaluarsa</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Aturan:</div>
                        <div class="col-md-9">
                            <ul class="mb-0 pl-3">
                                <li>1 kode voucher hanya bisa digunakan 1 kali oleh 1 user</li>
                                @if($voucher->expires_at)
                                    <li>Voucher akan kadaluarsa pada {{ $voucher->expires_at->format('d M Y H:i') }}</li>
                                @else
                                    <li>Voucher tidak memiliki masa kadaluarsa</li>
                                @endif
                                @if($voucher->max_uses)
                                    <li>Maksimal {{ $voucher->max_uses }} kali penggunaan</li>
                                @else
                                    <li>Tidak ada batas penggunaan</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Dibuat Oleh:</div>
                        <div class="col-md-9">{{ $voucher->creator->name ?? '-' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3 font-weight-bold">Dibuat Pada:</div>
                        <div class="col-md-9">{{ $voucher->created_at->format('d M Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Redemptions List -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Riwayat Penggunaan</h6>
                </div>
                <div class="card-body">
                    @if($voucher->redemptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Text Quota</th>
                                        <th>Multimedia Quota</th>
                                        <th>Tanggal Redeem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($voucher->redemptions as $redemption)
                                        <tr>
                                            <td>{{ $redemption->user->name }} ({{ $redemption->user->email }})</td>
                                            <td>{{ number_format($redemption->text_quota_received, 0, ',', '.') }}</td>
                                            <td>{{ number_format($redemption->multimedia_quota_received, 0, ',', '.') }}</td>
                                            <td>{{ $redemption->created_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-4 mb-0">Belum ada yang menggunakan voucher ini</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
