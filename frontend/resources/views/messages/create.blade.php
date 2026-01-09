@extends('layouts.base')

@section('title', 'Send Message')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="messageTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="single-tab" data-toggle="tab" href="#single-message" role="tab" aria-controls="single-message" aria-selected="true">
                                <i class="fas fa-envelope"></i> {{ __('Single Message') }}
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="bulk-tab" data-toggle="tab" href="#bulk-message" role="tab" aria-controls="bulk-message" aria-selected="false">
                                <i class="fas fa-file-excel"></i> {{ __('Bulk Message (Excel)') }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="messageTabsContent">
                        <!-- Single Message Tab -->
                        <div class="tab-pane fade show active" id="single-message" role="tabpanel" aria-labelledby="single-tab">
                            <form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data" id="message-form" novalidate>
                                @csrf

                        <div class="mb-3">
                            <label for="session_id" class="form-label">{{ __('Select Device') }}</label>
                            <select class="form-select @error('session_id') is-invalid @enderror" 
                                    id="session_id" name="session_id" required>
                                <option value="">{{ __('Choose a device...') }}</option>
                                @foreach($sessions as $session)
                                    <option value="{{ $session->id }}" 
                                            {{ ($selectedSession && $selectedSession->id == $session->id) || request('session_id') == $session->id ? 'selected' : '' }}>
                                        {{ $session->session_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('session_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="to_number" class="form-label">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('to_number') is-invalid @enderror" 
                                   id="to_number" 
                                   name="to_number" 
                                   value="{{ old('to_number', request('to_number')) }}" 
                                   required 
                                   placeholder="081395777706"
                                   pattern="^08[0-9]{8,11}$"
                                   maxlength="13"
                                   autocomplete="tel"
                                   inputmode="numeric">
                            @error('to_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                {{ __('Masukkan nomor telepon dengan format: 081395777706 (10-13 digit, dimulai dengan 08). Format lain tidak diterima.') }}
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="message_type" class="form-label">{{ __('Message Type') }}</label>
                            <select class="form-select @error('message_type') is-invalid @enderror" 
                                    id="message_type" name="message_type" required onchange="toggleMessageFields()">
                                <option value="text" {{ old('message_type') == 'text' ? 'selected' : '' }}>{{ __('Text Message') }}</option>
                                <option value="image" {{ old('message_type') == 'image' ? 'selected' : '' }}>{{ __('Image') }}</option>
                                <option value="document" {{ old('message_type') == 'document' ? 'selected' : '' }}>{{ __('Document') }}</option>
                            </select>
                            @error('message_type')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3" id="text-content-field">
                            <label for="content" class="form-label">{{ __('Message Content') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('content') is-invalid @enderror" 
                                      id="content" name="content" rows="5" 
                                      placeholder="{{ __('Type your message here...') }}">{{ old('content') }}</textarea>
                            @error('content')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3" id="media-field" style="display: none;">
                            <label for="media" class="form-label">{{ __('Image File') }}</label>
                            <input type="file" class="form-control @error('media') is-invalid @enderror" 
                                   id="media" name="media" accept="image/*">
                            @error('media')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                {{ __('Max file size: 10MB') }}
                            </small>
                        </div>

                        <div class="mb-3" id="document-file-field" style="display: none;">
                            <label for="document_file" class="form-label">{{ __('Document File') }}</label>
                            <input type="file" class="form-control @error('document_file') is-invalid @enderror" 
                                   id="document_file" name="document_file" 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar">
                            @error('document_file')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <small class="form-text text-muted">
                                {{ __('Max file size: 10MB. Supported: PDF, DOC, XLS, PPT, TXT, ZIP, etc.') }}
                            </small>
                        </div>

                        <div class="mb-3" id="document-url-field" style="display: none;">
                            <label for="document_url" class="form-label">{{ __('Or Document URL') }} <small class="text-muted">({{ __('Optional if uploading file') }})</small></label>
                            <input type="url" class="form-control @error('document_url') is-invalid @enderror"
                                   id="document_url" name="document_url"
                                   placeholder="https://example.com/file.pdf"
                                   value="{{ old('document_url') }}">
                            @error('document_url')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                            <small class="form-text text-muted">
                                {{ __('Document will be fetched from this URL and sent via WhatsApp.') }}
                            </small>
                        </div>

                        <div class="mb-3" id="caption-field" style="display: none;">
                            <label for="caption" class="form-label">{{ __('Caption') }} <small class="text-muted">({{ __('Optional') }})</small></label>
                            <textarea class="form-control @error('caption') is-invalid @enderror" 
                                      id="caption" name="caption" rows="2" 
                                      placeholder="{{ __('Add a caption...') }}">{{ old('caption') }}</textarea>
                            @error('caption')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('messages.index') }}" class="btn btn-secondary">
                                        {{ __('Cancel') }}
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> {{ __('Send Message') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Bulk Message Tab -->
                        <div class="tab-pane fade" id="bulk-message" role="tabpanel" aria-labelledby="bulk-tab">
                            <form method="POST" action="{{ route('messages.storeBulk') }}" enctype="multipart/form-data" id="bulk-message-form" novalidate>
                                @csrf

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>{{ __('Format Excel:') }}</strong> 
                                    {{ __('File Excel harus memiliki kolom: nomor (phone number), pesan (message content). Baris pertama adalah header.') }}
                                    <br>
                                    <strong>{{ __('Contoh:') }}</strong>
                                    <table class="table table-sm table-bordered mt-2" style="max-width: 500px;">
                                        <thead>
                                            <tr>
                                                <th>nomor</th>
                                                <th>pesan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>081395777706</td>
                                                <td>Halo, ini pesan pertama</td>
                                            </tr>
                                            <tr>
                                                <td>081234567890</td>
                                                <td>Halo, ini pesan kedua</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <small class="text-danger">
                                        <strong>{{ __('Catatan:') }}</strong> {{ __('Format nomor harus dimulai dengan 08 (contoh: 081395777706). Format lain tidak diterima.') }}
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label for="bulk_session_id" class="form-label">{{ __('Select Device') }}</label>
                                    <select class="form-select @error('bulk_session_id') is-invalid @enderror" 
                                            id="bulk_session_id" name="session_id" required>
                                        <option value="">{{ __('Choose a device...') }}</option>
                                        @foreach($sessions as $session)
                                            <option value="{{ $session->id }}" 
                                                    {{ ($selectedSession && $selectedSession->id == $session->id) || request('session_id') == $session->id ? 'selected' : '' }}>
                                                {{ $session->session_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bulk_session_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="excel_file" class="form-label">{{ __('Excel File') }} <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control @error('excel_file') is-invalid @enderror" 
                                           id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                                    @error('excel_file')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        {{ __('Format: .xlsx atau .xls. Max file size: 10MB') }}
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="delay_enabled" name="delay_enabled" value="1">
                                        <label class="form-check-label" for="delay_enabled">
                                            {{ __('Enable delay between messages') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3" id="delay-field" style="display: none;">
                                    <label for="delay_seconds" class="form-label">{{ __('Delay (seconds)') }}</label>
                                    <input type="number" class="form-control" 
                                           id="delay_seconds" name="delay_seconds" 
                                           value="2" min="1" max="60">
                                    <small class="form-text text-muted">
                                        {{ __('Delay antara setiap pesan (1-60 detik)') }}
                                    </small>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('messages.index') }}" class="btn btn-secondary">
                                        {{ __('Cancel') }}
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> {{ __('Send Bulk Messages') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMessageFields() {
    const messageType = document.getElementById('message_type').value;
    const textField = document.getElementById('text-content-field');
    const mediaField = document.getElementById('media-field');
    const documentFileField = document.getElementById('document-file-field');
    const documentUrlField = document.getElementById('document-url-field');
    const captionField = document.getElementById('caption-field');
    const contentInput = document.getElementById('content');
    const mediaInput = document.getElementById('media');
    const documentFileInput = document.getElementById('document_file');
    const documentUrlInput = document.getElementById('document_url');

    if (messageType === 'text') {
        textField.style.display = 'block';
        mediaField.style.display = 'none';
        documentFileField.style.display = 'none';
        documentUrlField.style.display = 'none';
        captionField.style.display = 'none';
        if (contentInput) {
            contentInput.required = true;
        }
        if (mediaInput) {
            mediaInput.required = false;
        }
        if (documentFileInput) {
            documentFileInput.required = false;
        }
        if (documentUrlInput) {
            documentUrlInput.required = false;
        }
    } else if (messageType === 'image') {
        textField.style.display = 'none';
        mediaField.style.display = 'block';
        documentFileField.style.display = 'none';
        documentUrlField.style.display = 'none';
        captionField.style.display = 'block';
        if (contentInput) {
            contentInput.required = false;
        }
        if (mediaInput) {
            mediaInput.required = true;
        }
        if (documentFileInput) {
            documentFileInput.required = false;
        }
        if (documentUrlInput) {
            documentUrlInput.required = false;
        }
    } else if (messageType === 'document') {
        textField.style.display = 'none';
        mediaField.style.display = 'none';
        documentFileField.style.display = 'block';
        documentUrlField.style.display = 'block';
        captionField.style.display = 'block';
        if (contentInput) {
            contentInput.required = false;
        }
        if (mediaInput) {
            mediaInput.required = false;
        }
        if (documentFileInput) {
            documentFileInput.required = false; // Not required if URL is provided
        }
        if (documentUrlInput) {
            documentUrlInput.required = false; // Not required if file is uploaded
        }
    }
}

// Initialize on page load
toggleMessageFields();

// Handle delay checkbox
document.getElementById('delay_enabled').addEventListener('change', function() {
    const delayField = document.getElementById('delay-field');
    if (this.checked) {
        delayField.style.display = 'block';
    } else {
        delayField.style.display = 'none';
    }
});
</script>
@endsection

