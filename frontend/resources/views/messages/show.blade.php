@extends('layouts.base')

@section('title', 'Message Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('Message Details') }}</span>
                    <a href="{{ route('messages.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                    </a>
                </div>

                <div class="card-body">
                    <!-- Message Header Info -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    @if ($message->isIncoming())
                                        <span class="badge bg-primary text-white fs-6 px-3 py-2">
                                            <i class="fas fa-inbox"></i> {{ __('Incoming') }}
                                        </span>
                                    @else
                                        <span class="badge bg-success text-white fs-6 px-3 py-2">
                                            <i class="fas fa-paper-plane"></i> {{ __('Outgoing') }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <span class="badge bg-info text-white fs-6 px-3 py-2">
                                        <i class="fas fa-{{ $message->message_type === 'text' ? 'comment' : ($message->message_type === 'image' ? 'image' : ($message->message_type === 'video' ? 'video' : 'file')) }}"></i> 
                                        {{ ucfirst($message->message_type) }}
                                    </span>
                                </div>
                                @if ($message->chat_type)
                                    <div class="ms-2">
                                        <span class="badge bg-secondary fs-6 px-3 py-2">
                                            <i class="fas fa-{{ $message->chat_type === 'group' ? 'users' : 'user' }}"></i> 
                                            {{ ucfirst($message->chat_type) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="status-timeline mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge {{ $message->status === 'pending' ? 'bg-warning' : ($message->status === 'sent' ? 'bg-info' : ($message->status === 'delivered' ? 'bg-primary' : ($message->status === 'read' ? 'bg-success' : 'bg-danger'))) }} me-2">
                                        @if ($message->status === 'pending')
                                            <i class="fas fa-clock"></i> {{ __('Pending') }}
                                        @elseif ($message->status === 'sent')
                                            <i class="fas fa-check"></i> {{ __('Sent') }}
                                        @elseif ($message->status === 'delivered')
                                            <i class="fas fa-check-double"></i> {{ __('Delivered') }}
                                        @elseif ($message->status === 'read')
                                            <i class="fas fa-check-double text-primary"></i> {{ __('Read') }}
                                        @elseif ($message->status === 'failed')
                                            <i class="fas fa-times"></i> {{ __('Failed') }}
                                        @else
                                            {{ ucfirst($message->status) }}
                                        @endif
                                    </span>
                                    @if ($message->error_message)
                                        <span class="text-danger small">
                                            <i class="fas fa-exclamation-triangle"></i> {{ $message->error_message }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <small class="text-muted">{{ __('Created') }}: {{ $message->created_at->format('d M Y, H:i:s') }}</small>
                        </div>
                    </div>

                    <!-- Main Information Grid -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> {{ __('Message Information') }}</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <th width="40%" class="text-muted">{{ __('Message ID') }}</th>
                                            <td>
                                                <code class="small">{{ $message->id }}</code>
                                                <button class="btn btn-sm btn-link p-0 ms-2" onclick="copyToClipboard('{{ $message->id }}')" title="Copy">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @if ($message->whatsapp_message_id)
                                        <tr>
                                            <th class="text-muted">{{ __('WhatsApp Message ID') }}</th>
                                            <td>
                                                <code class="small">{{ strlen($message->whatsapp_message_id) > 50 ? substr($message->whatsapp_message_id, 0, 50) . '...' : $message->whatsapp_message_id }}</code>
                                                <button class="btn btn-sm btn-link p-0 ms-2" onclick="copyToClipboard('{{ $message->whatsapp_message_id }}')" title="Copy">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <th class="text-muted">{{ __('From') }}</th>
                                            <td>
                                                <strong>{{ $message->from_number ? '+' . $message->from_number : __('Unknown') }}</strong>
                                                @if ($message->isIncoming() && $message->session)
                                                    <br><small class="text-muted">{{ $message->session->session_name }}</small>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-muted">{{ __('To') }}</th>
                                            <td>
                                                <strong>{{ $message->to_number ? '+' . $message->to_number : __('Unknown') }}</strong>
                                                @if ($message->isOutgoing() && $message->session)
                                                    <br><small class="text-muted">{{ $message->session->session_name }}</small>
                                                @endif
                                            </td>
                                        </tr>
                                        @if ($message->session)
                                        <tr>
                                            <th class="text-muted">{{ __('Device/Session') }}</th>
                                            <td>
                                                <strong>{{ $message->session->session_name }}</strong>
                                                @if ($message->session->phone_number)
                                                    <br><small class="text-muted">{{ $message->session->phone_number }}</small>
                                                @endif
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-clock"></i> {{ __('Timeline') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <div class="timeline-item mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="timeline-icon {{ $message->sent_at ? 'bg-success' : 'bg-secondary' }} me-3">
                                                    <i class="fas fa-paper-plane text-white"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <strong>{{ __('Sent At') }}</strong>
                                                    <div class="text-muted small">
                                                        {{ $message->sent_at ? $message->sent_at->format('d M Y, H:i:s') : __('Not sent yet') }}
                                                        @if ($message->sent_at)
                                                            <span class="ms-2">({{ $message->sent_at->diffForHumans() }})</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="timeline-item mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="timeline-icon {{ $message->delivered_at ? 'bg-info' : 'bg-secondary' }} me-3">
                                                    <i class="fas fa-check-double text-white"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <strong>{{ __('Delivered At') }}</strong>
                                                    <div class="text-muted small">
                                                        {{ $message->delivered_at ? $message->delivered_at->format('d M Y, H:i:s') : __('Not delivered yet') }}
                                                        @if ($message->delivered_at)
                                                            <span class="ms-2">({{ $message->delivered_at->diffForHumans() }})</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="timeline-item mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="timeline-icon {{ $message->read_at ? 'bg-primary' : 'bg-secondary' }} me-3">
                                                    <i class="fas fa-eye text-white"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <strong>{{ __('Read At') }}</strong>
                                                    <div class="text-muted small">
                                                        {{ $message->read_at ? $message->read_at->format('d M Y, H:i:s') : __('Not read yet') }}
                                                        @if ($message->read_at)
                                                            <span class="ms-2">({{ $message->read_at->diffForHumans() }})</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Media Information (if applicable) -->
                    @if ($message->media_url || $message->media_mime_type || $message->media_size)
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-file"></i> {{ __('Media Information') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @if ($message->media_url)
                                        <div class="col-md-6">
                                            <strong>{{ __('Media URL') }}:</strong>
                                            <div class="mt-2">
                                                <a href="{{ filter_var($message->media_url, FILTER_VALIDATE_URL) ? $message->media_url : asset('storage/' . str_replace('/storage/', '', $message->media_url)) }}" 
                                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt"></i> {{ __('Open Media') }}
                                                </a>
                                            </div>
                                        </div>
                                        @endif
                                        @if ($message->media_mime_type)
                                        <div class="col-md-3">
                                            <strong>{{ __('MIME Type') }}:</strong>
                                            <div class="text-muted">{{ $message->media_mime_type }}</div>
                                        </div>
                                        @endif
                                        @if ($message->media_size)
                                        <div class="col-md-3">
                                            <strong>{{ __('File Size') }}:</strong>
                                            <div class="text-muted">{{ number_format($message->media_size / 1024, 2) }} KB</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Message Content -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-{{ $message->message_type === 'text' ? 'comment' : ($message->message_type === 'image' ? 'image' : ($message->message_type === 'video' ? 'video' : 'file')) }}"></i> 
                                        {{ __('Message Content') }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if ($message->message_type === 'text')
                                        <div class="message-content p-4" style="white-space: pre-wrap; word-wrap: break-word; font-size: 1.1em; line-height: 1.8; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; border-left: 4px solid #007bff;">
                                            {{ $message->content ?: __('No content') }}
                                        </div>
                                    @elseif ($message->message_type === 'image')
                                        <div class="text-center">
                                            @if ($message->media_url)
                                                <div class="mb-3">
                                                    <img src="{{ filter_var($message->media_url, FILTER_VALIDATE_URL) ? $message->media_url : asset('storage/' . str_replace('/storage/', '', $message->media_url)) }}" 
                                                         alt="Image" 
                                                         class="img-fluid rounded shadow" 
                                                         style="max-width: 100%; max-height: 600px; height: auto; cursor: pointer;"
                                                         onclick="window.open(this.src, '_blank')">
                                                </div>
                                                <a href="{{ filter_var($message->media_url, FILTER_VALIDATE_URL) ? $message->media_url : asset('storage/' . str_replace('/storage/', '', $message->media_url)) }}" 
                                                   target="_blank" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-external-link-alt"></i> {{ __('Open Full Size') }}
                                                </a>
                                            @else
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> {{ __('Image URL not available') }}
                                                </div>
                                            @endif
                                            @if ($message->caption)
                                                <div class="mt-4 text-start">
                                                    <strong class="d-block mb-2">{{ __('Caption') }}:</strong>
                                                    <div class="p-3 bg-light rounded" style="white-space: pre-wrap; word-wrap: break-word;">
                                                        {{ $message->caption }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif ($message->message_type === 'video')
                                        <div class="text-center">
                                            @if ($message->media_url)
                                                <div class="mb-3">
                                                    <video controls class="w-100 rounded shadow" style="max-height: 600px;">
                                                        <source src="{{ filter_var($message->media_url, FILTER_VALIDATE_URL) ? $message->media_url : asset('storage/' . str_replace('/storage/', '', $message->media_url)) }}" type="{{ $message->media_mime_type ?? 'video/mp4' }}">
                                                        {{ __('Your browser does not support the video tag.') }}
                                                    </video>
                                                </div>
                                                <a href="{{ filter_var($message->media_url, FILTER_VALIDATE_URL) ? $message->media_url : asset('storage/' . str_replace('/storage/', '', $message->media_url)) }}" 
                                                   target="_blank" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-download"></i> {{ __('Download Video') }}
                                                </a>
                                            @else
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> {{ __('Video URL not available') }}
                                                </div>
                                            @endif
                                            @if ($message->caption)
                                                <div class="mt-4 text-start">
                                                    <strong class="d-block mb-2">{{ __('Caption') }}:</strong>
                                                    <div class="p-3 bg-light rounded" style="white-space: pre-wrap; word-wrap: break-word;">
                                                        {{ $message->caption }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-center">
                                            @if ($message->media_url)
                                                <div class="mb-3">
                                                    <i class="fas fa-file fa-5x text-primary mb-3"></i>
                                                    <div>
                                                        <strong>{{ __('Document File') }}</strong>
                                                        @if ($message->media_mime_type)
                                                            <div class="text-muted small">{{ $message->media_mime_type }}</div>
                                                        @endif
                                                        @if ($message->media_size)
                                                            <div class="text-muted small">{{ number_format($message->media_size / 1024, 2) }} KB</div>
                                                        @endif
                                                    </div>
                                                </div>
                                                <a href="{{ filter_var($message->media_url, FILTER_VALIDATE_URL) ? $message->media_url : asset('storage/' . str_replace('/storage/', '', $message->media_url)) }}" 
                                                   target="_blank" 
                                                   class="btn btn-primary btn-lg">
                                                    <i class="fas fa-download"></i> {{ __('Download Document') }}
                                                </a>
                                            @else
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle"></i> {{ __('Document URL not available') }}
                                                </div>
                                            @endif
                                            @if ($message->caption)
                                                <div class="mt-4 text-start">
                                                    <strong class="d-block mb-2">{{ __('Caption') }}:</strong>
                                                    <div class="p-3 bg-light rounded" style="white-space: pre-wrap; word-wrap: break-word;">
                                                        {{ $message->caption }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 19px;
        top: 50px;
        width: 2px;
        height: calc(100% - 20px);
        background: #dee2e6;
    }
    
    .timeline-item {
        position: relative;
    }
    
    .message-content {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }
</style>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Show toast notification
            const toast = document.createElement('div');
            toast.className = 'alert alert-success position-fixed top-0 end-0 m-3';
            toast.style.zIndex = '9999';
            toast.innerHTML = '<i class="fas fa-check"></i> Copied to clipboard!';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        });
    }
</script>
@endsection
