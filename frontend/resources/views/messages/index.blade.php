@extends('layouts.base')

@section('title', 'Messages')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <span>{{ __('Messages') }}</span>
                        <!-- Auto Sync Toggle -->
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="autoSyncToggle" 
                                   {{ $autoSyncEnabled ? 'checked' : '' }}
                                   style="cursor: pointer;">
                            <label class="form-check-label" for="autoSyncToggle" style="cursor: pointer;">
                                <small>
                                    <i class="fas fa-sync-alt"></i> {{ __('Auto Sync Pesan Masuk') }}
                                </small>
                            </label>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="btnSyncIncoming" class="btn btn-info btn-sm">
                            <i class="fas fa-sync"></i> {{ __('Sync Pesan Masuk') }}
                        </button>
                        <button type="button" id="btnUpdateStatus" class="btn btn-warning btn-sm">
                            <i class="fas fa-refresh"></i> {{ __('Update Status') }}
                        </button>
                        <a href="{{ route('messages.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> {{ __('Send Message') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error_details'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>{{ __('Error Details:') }}</strong>
                            <pre class="mb-0 mt-2" style="white-space: pre-wrap; font-size: 0.9em;">{{ session('error_details') }}</pre>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <!-- Filters -->
                    <div class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <select name="session_id" id="filter_session_id" class="form-select">
                                    <option value="">{{ __('All Devices') }}</option>
                                    @foreach($sessions as $session)
                                        <option value="{{ $session->id }}" {{ request('session_id') == $session->id ? 'selected' : '' }}>
                                            {{ $session->session_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="direction" id="filter_direction" class="form-select">
                                    <option value="">{{ __('All Directions') }}</option>
                                    <option value="incoming" {{ request('direction') == 'incoming' ? 'selected' : '' }}>{{ __('Incoming') }}</option>
                                    <option value="outgoing" {{ request('direction') == 'outgoing' ? 'selected' : '' }}>{{ __('Outgoing') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" id="filter_search" class="form-control" placeholder="{{ __('Search messages...') }}" value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="button" id="btn_filter" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="messagesTable" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th width="15%">{{ __('Waktu') }}</th>
                                    <th width="20%">{{ __('Kontak') }}</th>
                                    <th width="40%">{{ __('Pesan') }}</th>
                                    <th width="15%">{{ __('Status') }}</th>
                                    <th width="10%" class="text-center">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    #messagesTable_wrapper .row {
        margin-bottom: 1rem;
    }
    
    /* Custom badge colors for better visibility */
    .badge {
        padding: 0.5em 0.75em;
        font-size: 0.875em;
        border-radius: 0.375rem;
        display: inline-block;
        margin-right: 0.25rem;
    }
    
    .badge.bg-purple {
        background-color: #6f42c1 !important;
        color: #fff !important;
    }
    
    .badge.bg-warning.text-dark {
        background-color: #ffc107 !important;
        color: #000 !important;
        font-weight: 600;
    }
    
    .badge.bg-info.text-white {
        background-color: #0dcaf0 !important;
        color: #fff !important;
    }
    
    .badge.bg-success.text-white {
        background-color: #198754 !important;
        color: #fff !important;
    }
    
    .badge.bg-danger.text-white {
        background-color: #dc3545 !important;
        color: #fff !important;
    }
    
    .badge.bg-primary.text-white {
        background-color: #0d6efd !important;
        color: #fff !important;
    }
    
    .badge.bg-secondary.text-white {
        background-color: #6c757d !important;
        color: #fff !important;
    }
    
    .message-preview {
        display: inline-block;
        max-width: 100%;
        word-wrap: break-word;
        white-space: normal;
    }
    
    #messagesTable td {
        vertical-align: middle;
    }
    
    #messagesTable .message-preview {
        line-height: 1.5;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('#messagesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("messages.data") }}',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: function(d) {
                // Add custom filters
                d.session_id = $('#filter_session_id').val();
                d.direction = $('#filter_direction').val();
                d.search = $('#filter_search').val();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables Ajax Error:', error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                console.error('Thrown:', thrown);
                alert('Terjadi kesalahan saat memuat data. Silakan refresh halaman atau cek console untuk detail.');
            }
        },
        columns: [
            { data: 'formatted_date', name: 'created_at', orderable: true },
            { data: 'contact_info', name: 'contact_info', orderable: false, searchable: false },
            { data: 'content_preview', name: 'content', orderable: false, searchable: true },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: [[0, 'desc']], // Order by date (column 0) descending
        pageLength: 10,
        language: {
            processing: "Memproses...",
            lengthMenu: "Tampilkan _MENU_ pesan per halaman",
            zeroRecords: "Tidak ada pesan ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ pesan",
            infoEmpty: "Menampilkan 0 sampai 0 dari 0 pesan",
            infoFiltered: "(difilter dari _MAX_ total pesan)",
            search: "Cari:",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        responsive: true
    });

    // Filter button click
    $('#btn_filter').on('click', function() {
        table.ajax.reload();
    });

    // Filter on Enter key
    $('#filter_search').on('keypress', function(e) {
        if (e.which === 13) {
            table.ajax.reload();
        }
    });

    // Auto-reload on filter change
    $('#filter_session_id, #filter_direction').on('change', function() {
        table.ajax.reload();
    });

    // Auto Sync Toggle
    $('#autoSyncToggle').on('change', function() {
        const enabled = $(this).is(':checked');
        
        $.ajax({
            url: '{{ route('messages.toggleAutoSync') }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            data: {
                enabled: enabled
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    const alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                    $('.card-body').prepend(alert);
                    setTimeout(() => alert.fadeOut(), 3000);
                    
                    // If enabled, trigger sync
                    if (enabled) {
                        syncIncomingMessages();
                    }
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal mengubah setting';
                alert(message);
                // Revert toggle
                $('#autoSyncToggle').prop('checked', !enabled);
            }
        });
    });

    // Sync Incoming Messages Button
    $('#btnSyncIncoming').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
        
        syncIncomingMessages(function() {
            btn.prop('disabled', false).html(originalHtml);
        });
    });

    // Update Pending Status Button
    $('#btnUpdateStatus').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        
        $.ajax({
            url: '{{ route('messages.updatePendingStatus') }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    const alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                    $('.card-body').prepend(alert);
                    setTimeout(() => alert.fadeOut(), 3000);
                    
                    // Reload table
                    table.ajax.reload();
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal update status';
                alert(message);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Sync Incoming Messages Function
    function syncIncomingMessages(callback) {
        $.ajax({
            url: '{{ route('messages.syncIncoming') }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    const alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                        response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>');
                    $('.card-body').prepend(alert);
                    setTimeout(() => alert.fadeOut(), 3000);
                    
                    // Reload table
                    table.ajax.reload();
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Gagal sync pesan masuk';
                alert(message);
            },
            complete: function() {
                if (callback) callback();
            }
        });
    }

    // Auto sync on page load if enabled
    @if($autoSyncEnabled)
    $(document).ready(function() {
        // Wait a bit before syncing to let page load first
        setTimeout(function() {
            syncIncomingMessages();
            // Also update pending status
            $('#btnUpdateStatus').trigger('click');
        }, 1000);
    });
    @endif

    // Auto update status every 30 seconds
    setInterval(function() {
        if ($('#autoSyncToggle').is(':checked')) {
            $.ajax({
                url: '{{ route('messages.updatePendingStatus') }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.success && response.data.updated > 0) {
                        // Reload table if any messages were updated
                        table.ajax.reload(null, false); // false = don't reset pagination
                    }
                }
            });
        }
    }, 30000); // Every 30 seconds
});
</script>
@endpush
