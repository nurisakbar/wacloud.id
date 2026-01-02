@extends('layouts.base')

@section('title', 'Beli Quota')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-shopping-cart"></i> Beli Quota
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('quota.index') }}">Quota</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Beli</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shopping-cart"></i> Form Pembelian Quota
                    </h6>
                </div>
                <div class="card-body">
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

                    <form method="POST" action="{{ route('quota.purchase') }}" id="purchase-form">
                        @csrf

                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading"><i class="fas fa-info-circle"></i> Informasi Pembelian</h6>
                            <p class="mb-0">Isi jumlah quota yang ingin dibeli untuk masing-masing jenis. Anda bisa membeli beberapa jenis sekaligus.</p>
                        </div>

                        <div class="form-group">
                            <label for="text_quota_quantity" class="form-label">
                                <i class="fas fa-comment"></i> Quota Teks (Premium)
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control @error('text_quota_quantity') is-invalid @enderror" 
                                       id="text_quota_quantity" 
                                       name="text_quota_quantity" 
                                       value="{{ old('text_quota_quantity', 0) }}" 
                                       min="0" 
                                       onkeyup="calculateTotalAmount()" 
                                       onchange="calculateTotalAmount()" 
                                       oninput="calculateTotalAmount()">
                                <div class="input-group-append">
                                    <span class="input-group-text">pesan</span>
                                </div>
                            </div>
                            @error('text_quota_quantity')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                Harga per pesan: <strong>Rp {{ number_format($pricing->text_without_watermark_price, 0, ',', '.') }}</strong>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="multimedia_quota_quantity" class="form-label">
                                <i class="fas fa-image"></i> Multimedia Quota
                            </label>
                            <div class="input-group">
                                <input type="number" 
                                       class="form-control @error('multimedia_quota_quantity') is-invalid @enderror" 
                                       id="multimedia_quota_quantity" 
                                       name="multimedia_quota_quantity" 
                                       value="{{ old('multimedia_quota_quantity', 0) }}" 
                                       min="0" 
                                       onkeyup="calculateTotalAmount()" 
                                       onchange="calculateTotalAmount()" 
                                       oninput="calculateTotalAmount()">
                                <div class="input-group-append">
                                    <span class="input-group-text">pesan</span>
                                </div>
                            </div>
                            @error('multimedia_quota_quantity')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                Harga per pesan: <strong>Rp {{ number_format($pricing->multimedia_price, 0, ',', '.') }}</strong>
                            </small>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label for="amount" class="form-label">
                                <i class="fas fa-money-bill-wave"></i> Total Amount (IDR) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="number" 
                                       class="form-control @error('amount') is-invalid @enderror" 
                                       id="amount" 
                                       name="amount" 
                                       value="{{ old('amount', 0) }}" 
                                       min="1000" 
                                       step="1000" 
                                       readonly>
                            </div>
                            @error('amount')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted" id="amount-info">
                                Isi jumlah quota yang ingin dibeli
                            </small>
                        </div>

                        <div class="alert alert-light border" id="calculation-info" style="display: none;">
                            <h6 class="alert-heading"><i class="fas fa-calculator"></i> Calculation Details</h6>
                            <div id="calculation-details"></div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label for="payment_method" class="form-label">
                                <i class="fas fa-credit-card"></i> Payment Method <span class="text-danger">*</span>
                            </label>
                            <select class="form-control @error('payment_method') is-invalid @enderror" 
                                    id="payment_method" 
                                    name="payment_method" 
                                    required>
                                <option value="">-- Pilih Metode Pembayaran --</option>
                                <option value="manual" {{ old('payment_method') === 'manual' ? 'selected' : '' }}>Manual Transfer</option>
                                {{-- Xendit payment disabled --}}
                                {{-- <option value="xendit" {{ old('payment_method') === 'xendit' ? 'selected' : '' }}>Xendit (Online Payment)</option> --}}
                            </select>
                            @error('payment_method')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                Silakan pilih Manual Transfer untuk melakukan pembayaran via transfer bank.
                            </small>
                        </div>

                        <!-- Manual Payment Instructions -->
                        <div class="alert alert-info" id="manual-payment-instructions" style="display: none;">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle"></i> Instruksi Pembayaran Manual
                            </h6>
                            <hr>
                            <div class="mb-3">
                                <strong><i class="fas fa-university"></i> Transfer ke Rekening:</strong>
                                <div class="mt-2 ml-3">
                                    <p class="mb-1"><strong>Bank:</strong> Mandiri</p>
                                    <p class="mb-1"><strong>No. Rekening:</strong> <span class="font-weight-bold text-primary">1320025238651</span></p>
                                    <p class="mb-0"><strong>Atas Nama:</strong> <span class="font-weight-bold">NURIS AKBAR</span></p>
                                </div>
                            </div>
                            <div class="mb-0">
                                <strong><i class="fas fa-phone"></i> Konfirmasi Pembayaran:</strong>
                                <div class="mt-2 ml-3">
                                    <p class="mb-0">
                                        Setelah melakukan transfer, silakan konfirmasi pembayaran Anda ke: 
                                        <a href="https://wa.me/6289699935552" target="_blank" class="font-weight-bold text-success">
                                            <i class="fab fa-whatsapp"></i> 089699935552
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                                <i class="fas fa-shopping-cart"></i> Purchase Quota
                            </button>
                            <a href="{{ route('quota.index') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Pricing Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tag"></i> Pricing Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-comment text-primary"></i> Text Quota (Premium)</span>
                            <strong>Rp {{ number_format($pricing->text_without_watermark_price, 0, ',', '.') }}/pesan</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-image text-info"></i> Multimedia Quota</span>
                            <strong>Rp {{ number_format($pricing->multimedia_price, 0, ',', '.') }}/pesan</strong>
                        </div>
                    </div>
                    <hr>
                    <div class="alert alert-info mb-0">
                        <small>
                            <i class="fas fa-info-circle"></i> 
                            Minimum purchase: <strong>Rp 1.000</strong>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Current Quota -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-wallet"></i> Current Quota
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-xs text-muted mb-1">Balance (IDR)</div>
                        <div class="h5 font-weight-bold text-primary mb-0">
                            Rp {{ number_format($quota->balance, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="text-xs text-muted mb-1">Text Quota</div>
                        <div class="h5 font-weight-bold text-info mb-0">
                            {{ number_format($quota->text_quota, 0, ',', '.') }} pesan
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="text-xs text-muted mb-1">Multimedia Quota</div>
                        <div class="h5 font-weight-bold text-success mb-0">
                            {{ number_format($quota->multimedia_quota, 0, ',', '.') }} pesan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Pricing data from server
const pricing = {
    text_without_watermark: {{ $pricing->text_without_watermark_price }},
    multimedia: {{ $pricing->multimedia_price }}
};

function calculateTotalAmount() {
    const textQuotaQuantity = parseInt(document.getElementById('text_quota_quantity').value) || 0;
    const multimediaQuotaQuantity = parseInt(document.getElementById('multimedia_quota_quantity').value) || 0;
    
    const amountField = document.getElementById('amount');
    const amountInfo = document.getElementById('amount-info');
    const calculationInfo = document.getElementById('calculation-info');
    const calculationDetails = document.getElementById('calculation-details');
    
    let totalAmount = 0;
    let calculationParts = [];
    
    // Calculate text quota
    if (textQuotaQuantity > 0) {
        const textPrice = pricing.text_without_watermark;
        if (textPrice > 0) {
            const textTotal = textPrice * textQuotaQuantity;
            totalAmount += textTotal;
            calculationParts.push(`<strong>Text Quota:</strong> ${textQuotaQuantity} pesan × Rp ${formatNumber(textPrice)} = Rp ${formatNumber(textTotal)}`);
        }
    }
    
    // Calculate multimedia quota
    if (multimediaQuotaQuantity > 0) {
        const multimediaPrice = pricing.multimedia;
        if (multimediaPrice > 0) {
            const multimediaTotal = multimediaPrice * multimediaQuotaQuantity;
            totalAmount += multimediaTotal;
            calculationParts.push(`<strong>Multimedia Quota:</strong> ${multimediaQuotaQuantity} pesan × Rp ${formatNumber(multimediaPrice)} = Rp ${formatNumber(multimediaTotal)}`);
        }
    }
    
    // Round to 2 decimal places
    totalAmount = Math.round(totalAmount * 100) / 100;
    
    // Update amount field
    amountField.value = totalAmount;
    
    // Update info and calculation
    if (totalAmount > 0) {
        if (totalAmount < 1000) {
            amountInfo.innerHTML = '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Minimum purchase: Rp 1.000</span>';
        } else {
            amountInfo.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Total amount: <strong>Rp ' + formatNumber(totalAmount) + '</strong></span>';
        }
        
        if (calculationParts.length > 0) {
            calculationDetails.innerHTML = calculationParts.join('<br>') + '<br><hr class="my-2"><strong class="text-primary">Total: Rp ' + formatNumber(totalAmount) + '</strong>';
            calculationInfo.style.display = 'block';
        } else {
            calculationInfo.style.display = 'none';
        }
    } else {
        amountInfo.textContent = 'Isi jumlah quota yang ingin dibeli';
        amountField.value = 0;
        calculationInfo.style.display = 'none';
    }
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Handle payment method change
document.getElementById('payment_method').addEventListener('change', function() {
    const manualPaymentInstructions = document.getElementById('manual-payment-instructions');
    
    if (this.value === 'manual') {
        // Show manual payment instructions
        manualPaymentInstructions.style.display = 'block';
    } else {
        // Hide manual payment instructions
        manualPaymentInstructions.style.display = 'none';
    }
});

// Form validation
document.getElementById('purchase-form').addEventListener('submit', function(e) {
    const totalAmount = parseFloat(document.getElementById('amount').value) || 0;
    if (totalAmount < 1000) {
        e.preventDefault();
        alert('Minimum purchase: Rp 1.000');
        return false;
    }
    
    const textQuotaQuantity = parseInt(document.getElementById('text_quota_quantity').value) || 0;
    const multimediaQuotaQuantity = parseInt(document.getElementById('multimedia_quota_quantity').value) || 0;
    
    if (textQuotaQuantity === 0 && multimediaQuotaQuantity === 0) {
        e.preventDefault();
        alert('Silakan isi minimal satu jenis quota yang ingin dibeli');
        return false;
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotalAmount();
    // Trigger payment method change handler
    const paymentMethod = document.getElementById('payment_method');
    const manualPaymentInstructions = document.getElementById('manual-payment-instructions');
    
    if (paymentMethod.value) {
        paymentMethod.dispatchEvent(new Event('change'));
    } else {
        // Default: hide manual instructions
        manualPaymentInstructions.style.display = 'none';
    }
});
</script>

{{-- Xendit payment script disabled --}}
{{-- @push('scripts')
<script>
{!! file_get_contents(resource_path('js/xendit-payment.js')) !!}
</script>
@endpush --}}
@endsection

