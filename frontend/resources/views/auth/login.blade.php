@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="{{ asset('logo-wacloud.png') }}" alt="WACloud Logo" class="login-logo">
            <p class="login-subtitle">Masukan email dan password untuk login</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="login-form" id="loginForm">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input id="email" type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                       placeholder="Masukan email Anda">

                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-input-wrapper">
                    <input id="password" type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           name="password" required autocomplete="current-password"
                           placeholder="Masukan password Anda">
                    <span class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                    </span>
                </div>
                <div class="forgot-password-link">
                    <a href="{{ route('password.request') }}">Lupa Password?</a>
                </div>

                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Masukan Hasil Penjumlahan Berikut</label>
                <div class="captcha-container">
                    <div class="captcha-question">
                        <span id="captchaNum1">{{ rand(1, 10) }}</span>
                        <span>+</span>
                        <span id="captchaNum2">{{ rand(1, 10) }}</span>
                        <span>=</span>
                    </div>
                    <input type="text" 
                           class="form-control captcha-input @error('captcha') is-invalid @enderror"
                           name="captcha" 
                           id="captcha"
                           required
                           placeholder="captcha"
                           maxlength="3">
                    <input type="hidden" name="captcha_answer" id="captcha_answer">
                </div>
                @error('captcha')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <a href="#" class="help-link">Bantuan Teknis</a>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-login btn-block">
                    Login
                </button>
            </div>

            <div class="form-group">
                <a href="{{ route('register') }}" class="btn btn-register btn-block">
                    Daftar
                </a>
            </div>

            <div class="form-group">
                <div class="divider">
                    <span>---</span>
                </div>
            </div>

            <div class="form-group">
                <button type="button" class="btn btn-google btn-block" onclick="loginWithGoogle()">
                    <svg width="20" height="20" viewBox="0 0 24 24" class="google-logo">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <span>Login dengan Google</span>
                </button>
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

.password-input-wrapper {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #999;
    font-size: 16px;
}

.password-toggle:hover {
    color: #333;
}

.forgot-password-link {
    text-align: right;
    margin-top: 5px;
    font-size: 13px;
}

.forgot-password-link a {
    color: #007bff;
    text-decoration: none;
}

.forgot-password-link a:hover {
    text-decoration: underline;
}

.captcha-container {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.captcha-question {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 16px;
    font-weight: bold;
    color: #555;
    padding: 10px 15px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 5px;
    min-width: 120px;
    justify-content: center;
}

.captcha-input {
    flex: 1;
    min-width: 100px;
    max-width: 120px;
    text-align: center;
    font-weight: 600;
    font-size: 16px;
}

.help-link {
    color: #666;
    text-decoration: none;
    font-size: 14px;
    display: inline-block;
}

.help-link:hover {
    color: #007bff;
    text-decoration: underline;
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

.btn-register {
    background-color: #fff;
    color: #007bff;
    border: 1px solid #007bff;
}

.btn-register:hover {
    background-color: #e9f5ff;
    color: #007bff;
}

.btn-google {
    background: white;
    color: #333;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 12px;
}

.btn-google:hover {
    background: #f0f0f0;
    border-color: #ccc;
    color: #333;
}

.google-logo {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.btn-google span {
    font-weight: bold;
}

.divider {
    text-align: center;
    position: relative;
    margin: 20px 0;
}

.divider::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 100%;
    height: 1px;
    background: #eee;
}

.divider span {
    background: white;
    padding: 0 10px;
    position: relative;
    color: #999;
    font-size: 12px;
}

@media (max-width: 480px) {
    .login-card {
        padding: 20px;
    }

    .captcha-container {
        flex-direction: column;
        align-items: stretch;
    }

    .captcha-question {
        width: 100%;
    }

    .captcha-input {
        max-width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Generate captcha on page load
document.addEventListener('DOMContentLoaded', function() {
    generateCaptcha();
});

function generateCaptcha() {
    const num1 = Math.floor(Math.random() * 10) + 1;
    const num2 = Math.floor(Math.random() * 10) + 1;
    const answer = num1 + num2;
    
    document.getElementById('captchaNum1').textContent = num1;
    document.getElementById('captchaNum2').textContent = num2;
    document.getElementById('captcha_answer').value = answer;
    document.getElementById('captcha').value = '';
}

function loginWithGoogle() {
    window.location.href = '{{ route('auth.google') }}';
}

// Regenerate captcha on form submit if validation fails
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const captchaInput = document.getElementById('captcha').value;
    const captchaAnswer = document.getElementById('captcha_answer').value;
    
    if (captchaInput !== captchaAnswer) {
        e.preventDefault();
        alert('Captcha tidak benar. Silakan coba lagi.');
        generateCaptcha();
        return false;
    }
});
</script>
@endpush
@endsection
