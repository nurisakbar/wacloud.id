@extends('layouts.base')

@section('title', 'Payment Success')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow mb-4 border-success">
                <div class="card-header py-3 bg-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-check-circle"></i> Payment Successful
                    </h6>
                </div>
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h3 class="text-success mb-3">Payment Successful!</h3>
                    <p class="text-muted mb-4">
                        Your payment has been processed successfully. Your quota has been added to your account.
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
                            @if($purchase->text_quota_added > 0)
                            <div class="row mb-2">
                                <div class="col-6"><strong>Text Quota Added:</strong></div>
                                <div class="col-6">{{ number_format($purchase->text_quota_added, 0, ',', '.') }} pesan</div>
                            </div>
                            @endif
                            @if($purchase->multimedia_quota_added > 0)
                            <div class="row mb-2">
                                <div class="col-6"><strong>Multimedia Quota Added:</strong></div>
                                <div class="col-6">{{ number_format($purchase->multimedia_quota_added, 0, ',', '.') }} pesan</div>
                            </div>
                            @endif
                            <div class="row">
                                <div class="col-6"><strong>Status:</strong></div>
                                <div class="col-6">
                                    <span class="badge badge-success">Completed</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('quota.index') }}" class="btn btn-success btn-lg">
                            <i class="fas fa-wallet"></i> View My Quota
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-home"></i> Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection





