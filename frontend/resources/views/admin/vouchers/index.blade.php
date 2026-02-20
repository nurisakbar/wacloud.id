@extends('layouts.base')

@section('title', 'Vouchers Management')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-ticket-alt"></i> Vouchers Management
                </h1>
                <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Buat Voucher Baru
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter and Search -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.vouchers.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Cari</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Kode atau nama voucher">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">Semua</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Kadaluarsa</option>
                        <option value="used_up" {{ request('status') === 'used_up' ? 'selected' : '' }}>Habis</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Cari
                    </button>
                    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Vouchers Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Vouchers</h6>
        </div>
        <div class="card-body">
            @if($vouchers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Text Quota</th>
                                <th>Multimedia Quota</th>
                                <th>Penggunaan</th>
                                <th>Status</th>
                                <th>Kadaluarsa</th>
                                <th>Dibuat Oleh</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vouchers as $voucher)
                                <tr>
                                    <td>
                                        <code class="font-weight-bold">{{ $voucher->code }}</code>
                                    </td>
                                    <td>{{ $voucher->name }}</td>
                                    <td>{{ number_format($voucher->text_quota, 0, ',', '.') }}</td>
                                    <td>{{ number_format($voucher->multimedia_quota, 0, ',', '.') }}</td>
                                    <td>
                                        {{ $voucher->used_count }} 
                                        @if($voucher->max_uses)
                                            / {{ $voucher->max_uses }}
                                        @else
                                            / ∞
                                        @endif
                                    </td>
                                    <td>
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
                                    </td>
                                    <td>
                                        @if($voucher->expires_at)
                                            @if($voucher->expires_at->isPast())
                                                <span class="text-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> 
                                                    {{ $voucher->expires_at->format('d M Y H:i') }}
                                                    <br><small>(Sudah kadaluarsa)</small>
                                                </span>
                                            @elseif($voucher->expires_at->isToday())
                                                <span class="text-warning">
                                                    {{ $voucher->expires_at->format('d M Y H:i') }}
                                                    <br><small>(Kadaluarsa hari ini)</small>
                                                </span>
                                            @else
                                                {{ $voucher->expires_at->format('d M Y H:i') }}
                                            @endif
                                        @else
                                            <span class="text-muted">Tidak ada</span>
                                        @endif
                                    </td>
                                    <td>{{ $voucher->creator->name ?? '-' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.vouchers.show', $voucher) }}" 
                                               class="btn btn-sm btn-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.vouchers.edit', $voucher) }}" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.vouchers.destroy', $voucher) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus voucher ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $vouchers->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada voucher ditemukan.</p>
                    <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buat Voucher Baru
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
