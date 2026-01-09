@extends('layouts.base')

@section('title', 'Payment Failed')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow mb-4 border-danger">
                <div class="card-header py-3 bg-danger text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-times-circle"></i> Payment Failed
                    </h6>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-times-circle text-danger" style="font-size: 5rem;"></i>
                    </div>
                    <h3 class="text-danger mb-3">Payment Failed</h3>
                    <p class="text-muted mb-4">
                        Your payment could not be processed. Please try again or contact support if the problem persists.
                    </p>
                    
                    <div class="card bg-light mb-4">
                        <div class="card-body text-left">
                            <div class="row mb-2">
                                <div class="col-6"><strong>Purchase Number:</strong></div>
                                <div class="col-6">{{ $purchase->purchase_number }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6"><strong>Amount:</strong></div>
                                <div class="col-6">Rp {{ number_format($purchase->amount, 0, ',', '.') }}</div>
                            </div>
                            <div class="row">
                                <div class="col-6"><strong>Status:</strong></div>
                                <div class="col-6">
                                    <span class="badge badge-danger">Failed</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info text-left">
                        <h6 class="alert-heading"><i class="fas fa-info-circle"></i> What to do next?</h6>
                        <ul class="mb-0">
                            <li>Check your payment method and try again</li>
                            <li>Ensure you have sufficient balance</li>
                            <li>Contact support if the problem persists</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        @if($purchase->xendit_invoice_url)
                        <a href="{{ $purchase->xendit_invoice_url }}" target="_blank" class="btn btn-primary btn-lg">
                            <i class="fas fa-redo"></i> Try Again
                        </a>
                        @endif
                        <a href="{{ route('quota.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left"></i> Back to Quota
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection




