@extends('layouts.base')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1">Dashboard</h1>
            <p class="text-muted mb-0">Pantau device WhatsApp dan aktivitas terbaru Anda.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-uppercase text-muted mb-1 small">Total Devices</p>
                    <h3 class="fw-bold mb-0">{{ $metrics['totalSessions'] ?? 0 }}</h3>
                    <small class="text-muted">Semua device yang telah dibuat</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-uppercase text-muted mb-1 small">Active</p>
                    <h3 class="fw-bold mb-0">{{ $metrics['activeSessions'] ?? 0 }}</h3>
                    <small class="text-muted">Device sedang terhubung</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-uppercase text-muted mb-1 small">Sent Today</p>
                    <h3 class="fw-bold mb-0">{{ $metrics['messagesSentToday'] ?? 0 }}</h3>
                    <small class="text-muted">Pesan keluar</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-uppercase text-muted mb-1 small">Received Today</p>
                    <h3 class="fw-bold mb-0">{{ $metrics['messagesReceivedToday'] ?? 0 }}</h3>
                    <small class="text-muted">Pesan masuk</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quota Information -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h5 mb-0"><i class="fas fa-wallet text-primary"></i> Quota Information</h2>
                        <small class="text-muted">Informasi quota yang Anda miliki</small>
                    </div>
                    <a href="{{ route('quota.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-shopping-cart"></i> Purchase Quota
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Free Quota (dengan watermark) -->
                        <div class="col-md-4 mb-3">
                            <div class="card border-left-success shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Free Quota
                                            </div>
                                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($quota->free_text_quota ?? 0, 0, ',', '.') }} pesan
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-tag"></i> Dengan watermark (Reset tgl 1)
                                            </small>
                                        </div>
                                        <div class="ml-3">
                                            <i class="fas fa-gift fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Text Premium Quota (tanpa watermark) -->
                        <div class="col-md-4 mb-3">
                            <div class="card border-left-primary shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Text Premium Quota
                                            </div>
                                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($quota->text_quota ?? 0, 0, ',', '.') }} pesan
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-star"></i> Tanpa watermark
                                            </small>
                                        </div>
                                        <div class="ml-3">
                                            <i class="fas fa-comment-dots fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Multimedia Quota -->
                        <div class="col-md-4 mb-3">
                            <div class="card border-left-info shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Multimedia Quota
                                            </div>
                                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                                {{ number_format($quota->multimedia_quota ?? 0, 0, ',', '.') }} pesan
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                <i class="fas fa-images"></i> Image, Video, Document
                                            </small>
                                        </div>
                                        <div class="ml-3">
                                            <i class="fas fa-film fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik Pesan Bulan Ini -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h5 mb-0">Grafik Pesan Bulan Ini</h2>
                <small class="text-muted">Jumlah pesan yang dikirim dari awal bulan hingga hari ini</small>
            </div>
            <span class="badge bg-success text-white">
                Total: {{ number_format($messageStats['total'] ?? 0, 0, ',', '.') }} pesan
            </span>
        </div>
        <div class="card-body">
            <canvas id="messageChart" height="80"></canvas>
        </div>
    </div>

    <!-- Grafik Penggunaan Quota Harian -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h5 mb-0"><i class="fas fa-chart-line text-primary"></i> Grafik Penggunaan Quota Harian</h2>
                <small class="text-muted">Penggunaan quota dari awal bulan hingga hari ini</small>
            </div>
            <div>
                <span class="badge bg-success text-white mr-2">
                    <i class="fas fa-gift"></i> Free: Unlimited
                </span>
                <span class="badge bg-primary text-white mr-2">
                    <i class="fas fa-star"></i> Text Premium: {{ number_format($quotaUsageStats['total_text_quota'] ?? 0, 0, ',', '.') }} pesan
                </span>
                <span class="badge bg-info text-white">
                    <i class="fas fa-film"></i> Multimedia: {{ number_format($quotaUsageStats['total_multimedia_quota'] ?? 0, 0, ',', '.') }} pesan
                </span>
            </div>
        </div>
        <div class="card-body">
            <canvas id="quotaUsageChart" height="80"></canvas>
        </div>
    </div>

</div>

@push('scripts')
<!-- Chart.js (using CDN for latest version) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('messageChart');
    if (!ctx) return;

    const messageStats = @json($messageStats ?? ['labels' => [], 'data' => []]);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: messageStats.labels || [],
            datasets: [{
                label: 'Pesan Dikirim',
                data: messageStats.data || [],
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: 'rgb(16, 185, 129)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 12,
                            weight: '500'
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return 'Pesan: ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return value.toLocaleString('id-ID');
                        },
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        },
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });

    // Quota Usage Chart
    const quotaUsageCtx = document.getElementById('quotaUsageChart');
    if (quotaUsageCtx) {
        const quotaUsageStats = @json($quotaUsageStats);
        
        new Chart(quotaUsageCtx, {
            type: 'line',
            data: {
                labels: quotaUsageStats.labels || [],
                datasets: [
                    {
                        label: 'Text Premium (Tanpa Watermark)',
                        data: quotaUsageStats.text_quota_data || [],
                        borderColor: 'rgb(78, 115, 223)',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: 'rgb(78, 115, 223)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Multimedia (Image, Video, Document)',
                        data: quotaUsageStats.multimedia_quota_data || [],
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: 'rgb(16, 185, 129)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toLocaleString('id-ID') + ' pesan';
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return value.toLocaleString('id-ID');
                            },
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        title: {
                            display: true,
                            text: 'Jumlah Pesan',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            },
                            maxRotation: 45,
                            minRotation: 45
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
