@extends('layouts.base')

@section('title', 'Konfirmasi Pembayaran')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-money-check-alt"></i> Konfirmasi Pembayaran Manual
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle"></i> Informasi Pembelian
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Nomor Pembelian</th>
                            <td><strong>{{ $purchase->purchase_number }}</strong></td>
                        </tr>
                        <tr>
                            <th>Jumlah</th>
                            <td><strong class="text-primary">Rp {{ number_format($purchase->amount, 0, ',', '.') }}</strong></td>
                        </tr>
                        <tr>
                            <th>Quota Teks</th>
                            <td>{{ number_format($purchase->text_quota_added, 0, ',', '.') }} pesan</td>
                        </tr>
                        <tr>
                            <th>Quota Multimedia</th>
                            <td>{{ number_format($purchase->multimedia_quota_added, 0, ',', '.') }} pesan</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span class="badge badge-warning">Menunggu</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-upload"></i> Konfirmasi Pembayaran
                    </h6>
                </div>
                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('quota.confirm-payment.store', $purchase) }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label for="payment_reference" class="form-label">
                                <i class="fas fa-receipt"></i> Referensi Pembayaran <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('payment_reference') is-invalid @enderror" 
                                   id="payment_reference" 
                                   name="payment_reference" 
                                   value="{{ old('payment_reference', $purchase->payment_reference) }}" 
                                   placeholder="No. rekening, transaction ID, atau referensi pembayaran" 
                                   required>
                            @error('payment_reference')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                Masukkan nomor rekening, transaction ID, atau referensi pembayaran yang Anda gunakan.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="payment_proof" class="form-label">
                                <i class="fas fa-file-image"></i> Payment Proof (Bukti Pembayaran) <span class="text-danger">*</span>
                            </label>
                            <div class="custom-file">
                                <input type="file" 
                                       class="custom-file-input @error('payment_proof') is-invalid @enderror" 
                                       id="payment_proof" 
                                       name="payment_proof" 
                                       accept="image/*"
                                       required>
                                <label class="custom-file-label" for="payment_proof">Choose file...</label>
                            </div>
                            @error('payment_proof')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                Upload bukti pembayaran (screenshot transfer, foto struk, dll). Format: JPG, PNG, GIF. Maksimal 5MB.
                            </small>
                            <div id="preview-container" class="mt-3" style="display: none;">
                                <img id="preview-image" src="" alt="Preview" class="img-thumbnail" style="max-height: 300px;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note"></i> Catatan Tambahan (Opsional)
                            </label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="Catatan tambahan untuk admin...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Perhatian:</strong> Setelah mengirim konfirmasi pembayaran, admin akan memverifikasi pembayaran Anda. 
                            Quota akan ditambahkan setelah admin menyetujui pembayaran.
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Kirim Konfirmasi Pembayaran
                            </button>
                            <a href="{{ route('quota.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview image before upload
document.getElementById('payment_proof').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-image').src = e.target.result;
            document.getElementById('preview-container').style.display = 'block';
        }
        reader.readAsDataURL(file);
        
        // Update label
        const label = document.querySelector('.custom-file-label');
        label.textContent = file.name;
    }
});
</script>
@endsection

