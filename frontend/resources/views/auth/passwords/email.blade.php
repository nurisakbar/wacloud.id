@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('content')
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="{{ asset('logo-wacloud.png') }}" alt="WACloud Logo" class="login-logo">
            <h2 class="login-title">Lupa Password</h2>
            <p class="login-subtitle">Masukan nomor HP Anda untuk mendapatkan password baru</p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="login-form" id="forgotPasswordForm">
            @csrf

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ session('status') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="form-group">
                <label for="phone" class="form-label">Nomor HP</label>
                <input id="phone" 
                       type="tel" 
                       class="form-control @error('phone') is-invalid @enderror" 
                       name="phone" 
                       value="{{ old('phone') }}" 
                       required 
                       autocomplete="tel" 
                       autofocus
                       placeholder="Contoh: 081234567890">

                @error('phone')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
                <small class="form-text text-muted mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Password baru akan dikirim ke WhatsApp nomor HP Anda
                </small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-login btn-block">
                    <i class="fas fa-paper-plane mr-2"></i>Kirim Password Baru
                </button>
            </div>

            <div class="form-group">
                <p class="login-link-text">
                    Ingat password Anda? <a href="{{ route('login') }}" class="login-link">Login</a>
                </p>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
body {
    background-image: url('{{ asset("Gemini_Generated_Image_sa6amqsa6amqsa6a.png") }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    font-family: 'Nunito', sans-serif;
    position: relative;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.3);
    z-index: 0;
}

.login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    position: relative;
    z-index: 1;
}

.login-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
    padding: 30px;
}

.login-header {
    text-align: center;
    margin-bottom: 30px;
}

.login-logo {
    max-width: 120px;
    height: auto;
    margin-bottom: 15px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}

.login-title {
    font-size: 24px;
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.login-subtitle {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.login-form {
    margin-top: 0;
}

.form-group {
    margin-bottom: 15px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
}

.form-text {
    display: block;
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.alert {
    padding: 12px 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 5px;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert .close {
    float: right;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    color: #000;
    text-shadow: 0 1px 0 #fff;
    opacity: .5;
    background: transparent;
    border: 0;
    cursor: pointer;
}

.btn {
    padding: 12px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: block;
    width: 100%;
    text-align: center;
    box-sizing: border-box;
}

.btn-login {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.btn-login:hover {
    background-color: #0056b3;
    border-color: #0056b3;
    color: white;
}

.login-link-text {
    font-size: 14px;
    color: #666;
    margin: 0;
    text-align: center;
}

.login-link {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}

.login-link:hover {
    text-decoration: underline;
}

@media (max-width: 480px) {
    .login-card {
        padding: 20px;
    }
}
</style>
@endpush
@endsection
