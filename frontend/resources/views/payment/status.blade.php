@extends('layouts.base')

@section('title', 'Payment Status')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-credit-card"></i> Payment Status
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div id="payment-status-container">
                        <!-- Loading State -->
                        <div id="loading-state" class="py-5">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <h5 class="text-primary">Checking Payment Status...</h5>
                            <p class="text-muted">Please wait while we verify your payment</p>
                        </div>

                        <!-- Success State -->
                        <div id="success-state" class="py-5" style="display: none;">
                            <div class="mb-3">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="text-success mb-3">Payment Successful!</h4>
                            <p class="text-muted mb-4">Your quota has been added to your account.</p>
                            <a href="{{ route('quota.index') }}" class="btn btn-success btn-lg">
                                <i class="fas fa-arrow-left"></i> Back to Quota
                            </a>
                        </div>

                        <!-- Pending State -->
                        <div id="pending-state" class="py-5" style="display: none;">
                            <div class="mb-3">
                                <i class="fas fa-clock text-warning" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="text-warning mb-3">Payment Pending</h4>
                            <p class="text-muted mb-4">Your payment is being processed. Please wait...</p>
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     style="width: 100%"></div>
                            </div>
                            <p class="text-muted small">This page will automatically refresh</p>
                        </div>

                        <!-- Failed State -->
                        <div id="failed-state" class="py-5" style="display: none;">
                            <div class="mb-3">
                                <i class="fas fa-times-circle text-danger" style="font-size: 4rem;"></i>
                            </div>
                            <h4 class="text-danger mb-3">Payment Failed</h4>
                            <p class="text-muted mb-4" id="failed-message">Your payment could not be processed.</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('quota.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Quota
                                </a>
                                @if($purchase->xendit_invoice_url)
                                <a href="{{ $purchase->xendit_invoice_url }}" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Try Again
                                </a>
                                @endif
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <div class="mt-4 pt-4 border-top">
                            <div class="row text-left">
                                <div class="col-6">
                                    <small class="text-muted">Purchase Number:</small>
                                    <div class="font-weight-bold">{{ $purchase->purchase_number }}</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Amount:</small>
                                    <div class="font-weight-bold">Rp {{ number_format($purchase->amount, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let checkInterval;
let checkCount = 0;
const maxChecks = 30; // Maximum 30 checks (5 minutes if checking every 10 seconds)

function checkPaymentStatus() {
    checkCount++;
    
    fetch('{{ route("payment.check-status", $purchase->id) }}', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const status = data.status;
            const paid = data.paid || false;
            
            // Hide all states
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('success-state').style.display = 'none';
            document.getElementById('pending-state').style.display = 'none';
            document.getElementById('failed-state').style.display = 'none';
            
            if (paid || status === 'completed') {
                // Payment successful
                document.getElementById('success-state').style.display = 'block';
                clearInterval(checkInterval);
                
                // Redirect after 3 seconds
                setTimeout(() => {
                    window.location.href = '{{ route("quota.index") }}';
                }, 3000);
            } else if (status === 'failed') {
                // Payment failed
                document.getElementById('failed-state').style.display = 'block';
                clearInterval(checkInterval);
            } else {
                // Still pending
                if (checkCount === 1) {
                    // First check, show pending state
                    document.getElementById('loading-state').style.display = 'none';
                    document.getElementById('pending-state').style.display = 'block';
                }
                
                // Stop checking after max attempts
                if (checkCount >= maxChecks) {
                    clearInterval(checkInterval);
                    document.getElementById('pending-state').style.display = 'none';
                    document.getElementById('failed-state').style.display = 'block';
                    document.getElementById('failed-message').textContent = 
                        'Payment verification timeout. Please check your payment status manually.';
                }
            }
        } else {
            // Error checking status
            if (checkCount >= maxChecks) {
                clearInterval(checkInterval);
                document.getElementById('loading-state').style.display = 'none';
                document.getElementById('failed-state').style.display = 'block';
            }
        }
    })
    .catch(error => {
        console.error('Error checking payment status:', error);
        if (checkCount >= maxChecks) {
            clearInterval(checkInterval);
            document.getElementById('loading-state').style.display = 'none';
            document.getElementById('failed-state').style.display = 'block';
        }
    });
}

// Start checking payment status
document.addEventListener('DOMContentLoaded', function() {
    // Initial check
    checkPaymentStatus();
    
    // Check every 10 seconds
    checkInterval = setInterval(checkPaymentStatus, 10000);
});
</script>
@endsection







