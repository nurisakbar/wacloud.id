@extends('layouts.base')

@section('title', 'Edit Voucher')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-edit"></i> Edit Voucher
                </h1>
                <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
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
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.vouchers.update', $voucher) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label for="code" class="font-weight-bold">Kode Voucher <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code', $voucher->code) }}" 
                                   required
                                   style="text-transform: uppercase;">
                            @error('code')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="name" class="font-weight-bold">Nama Voucher <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $voucher->name) }}" 
                                   required>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="font-weight-bold">Deskripsi</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3">{{ old('description', $voucher->description) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <hr>

                        <h6 class="font-weight-bold mb-3">Quota yang Diberikan</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="text_quota" class="font-weight-bold">Text Quota <span class="text-danger">*</span></label>
                                <input type="number" 
                                       class="form-control @error('text_quota') is-invalid @enderror" 
                                       id="text_quota" 
                                       name="text_quota" 
                                       value="{{ old('text_quota', $voucher->text_quota) }}" 
                                       required
                                       min="0">
                                @error('text_quota')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="multimedia_quota" class="font-weight-bold">Multimedia Quota <span class="text-danger">*</span></label>
                                <input type="number" 
                                       class="form-control @error('multimedia_quota') is-invalid @enderror" 
                                       id="multimedia_quota" 
                                       name="multimedia_quota" 
                                       value="{{ old('multimedia_quota', $voucher->multimedia_quota) }}" 
                                       required
                                       min="0">
                                @error('multimedia_quota')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <hr>

                        <h6 class="font-weight-bold mb-3">Pengaturan</h6>

                        <div class="form-group mb-3">
                            <label for="max_uses" class="font-weight-bold">Maksimal Penggunaan</label>
                            <input type="number" 
                                   class="form-control @error('max_uses') is-invalid @enderror" 
                                   id="max_uses" 
                                   name="max_uses" 
                                   value="{{ old('max_uses', $voucher->max_uses) }}" 
                                   min="1">
                            <small class="form-text text-muted">Biarkan kosong untuk unlimited penggunaan</small>
                            @error('max_uses')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="expires_at" class="font-weight-bold">Tanggal Kadaluarsa</label>
                            <input type="datetime-local" 
                                   class="form-control @error('expires_at') is-invalid @enderror" 
                                   id="expires_at" 
                                   name="expires_at" 
                                   value="{{ old('expires_at', $voucher->expires_at ? $voucher->expires_at->format('Y-m-d\TH:i') : '') }}">
                            <small class="form-text text-muted">Biarkan kosong untuk tidak ada kadaluarsa</small>
                            @error('expires_at')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1"
                                       {{ old('is_active', $voucher->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Aktifkan voucher
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.vouchers.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
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
