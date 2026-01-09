@extends('layouts.base')

@section('title', 'Create Device')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Form Column -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-plus-circle mr-2"></i>Buat Device WhatsApp Baru
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('sessions.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-12 mb-6">
                                <label for="session_name" class="form-label">Nama Device <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('session_name') is-invalid @enderror" 
                                       id="session_name" name="session_name" value="{{ old('session_name') }}" 
                                       required autofocus placeholder="Contoh: WhatsApp Bisnis Saya">
                                
                                @error('session_name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                
                                <small class="form-text text-muted">
                                    Berikan nama yang mudah diingat untuk device ini.
                                </small>
                            </div>

                            <div class="col-md-12 mb-6" style="margin-bottom: 20px;margin-top: 20px;">
                                <label for="phone_number" class="form-label">Nomor Telepon <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('phone_number') is-invalid @enderror" 
                                       id="phone_number" 
                                       name="phone_number" 
                                       value="{{ old('phone_number') }}" 
                                       required 
                                       placeholder="081395777706"
                                       pattern="^08[0-9]{8,11}$"
                                       maxlength="13"
                                       autocomplete="tel"
                                       inputmode="numeric">
                                
                                @error('phone_number')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                
                                <small class="form-text text-muted">
                                    Masukkan nomor telepon dengan format: <strong>081395777706</strong> (10-13 digit, dimulai dengan 08). Format lain tidak diterima.
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Catatan:</strong>
                                Setelah membuat device, Anda perlu memindai QR code dengan WhatsApp untuk menghubungkan device.
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('sessions.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-2"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>Buat Device
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Information Column -->
        <div class="col-lg-4">
            <div class="card shadow mb-4 border-left-info">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-info-circle text-info mr-2"></i>Informasi
                    </h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success mr-2"></i>
                            <strong>Nama Device:</strong> Gunakan nama yang deskriptif untuk memudahkan identifikasi.
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-phone text-primary mr-2"></i>
                            <strong>Nomor Telepon:</strong> Masukkan nomor WhatsApp aktif yang akan digunakan untuk device ini.
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-qrcode text-warning mr-2"></i>
                            <strong>QR Code:</strong> Setelah membuat device, scan QR code yang muncul untuk menghubungkan WhatsApp.
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-shield-alt text-success mr-2"></i>
                            <strong>Keamanan:</strong> Setiap device terhubung ke nomor telepon yang berbeda.
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow mb-4 border-left-warning">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-lightbulb text-warning mr-2"></i>Tips
                    </h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <i class="fas fa-circle text-muted mr-2" style="font-size: 6px;"></i>
                            Pastikan nomor telepon aktif dan dapat menerima pesan WhatsApp
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-circle text-muted mr-2" style="font-size: 6px;"></i>
                            QR code akan expired setelah beberapa menit, refresh jika perlu
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-circle text-muted mr-2" style="font-size: 6px;"></i>
                            Satu nomor telepon hanya bisa digunakan untuk satu device aktif
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

