@extends('layouts.auth')

@section('title', 'Otorisasi Klikmedis')

@section('content')
<div class="container">
    <!-- Outer Row -->
    <div class="row justify-content-center mt-5">
        <div class="col-xl-6 col-lg-7 col-md-9">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <!-- Nested Row within Card Body -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="p-5">
                                <div class="text-center">
                                    <div class="mb-4">
                                        <i class="fas fa-link fa-3x text-primary"></i>
                                    </div>
                                    <h1 class="h4 text-gray-900 mb-4">Hubungkan Klikmedis ke WACloud</h1>
                                </div>

                                <div class="alert alert-info">
                                    <p class="mb-0">Halo <strong>{{ $name }}</strong> ({{ $email }}),</p>
                                    <p>Apakah Anda yakin akan menghubungkan akun Klikmedis Anda dengan layanan WACloud?</p>
                                </div>

                                <form action="{{ route('klikmedis.confirm') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="setting_id" value="{{ $setting_id }}">
                                    <input type="hidden" name="callback_url" value="{{ $callback_url }}">
                                    <input type="hidden" name="name" value="{{ $name }}">
                                    <input type="hidden" name="email" value="{{ $email }}">

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary btn-user btn-block py-2">
                                            <i class="fas fa-check-circle"></i> Ya, Hubungkan Sekarang
                                        </button>
                                        <a href="{{ $callback_url }}?status=error&message=Dibatalkan oleh pengguna" class="btn btn-secondary btn-user btn-block py-2">
                                            <i class="fas fa-times-circle"></i> Batal
                                        </a>
                                    </div>
                                </form>

                                <hr>
                                <div class="text-center small text-muted">
                                    <i class="fas fa-shield-alt"></i> Koneksi aman dilakukan melalui Single Sign-On (SSO).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
