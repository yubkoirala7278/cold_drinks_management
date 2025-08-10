@extends('layouts.app')

@section('content')
        <!-- Key Metrics Cards -->
        <div class="metrics-grid">
            @foreach ([['products', 'Products', 'fas fa-box', '#3498db', route('products.index')], ['batches', 'Batches', 'fas fa-boxes', '#2ecc71', route('batches.index')], ['items', 'Items', 'fas fa-barcode', '#9b59b6', '#'], ['available_locations', 'Available Spaces', 'fas fa-warehouse', '#e67e22', '#']] as $metric)
                <div class="metric-card">
                    <div class="metric-icon" style="background-color: {{ $metric[3] }};">
                        <i class="{{ $metric[2] }}"></i>
                    </div>
                    <div class="metric-info">
                        <h2>{{ $stats[$metric[0]] }}</h2>
                        <p>{{ $metric[1] }}</p>
                        <a href="{{ $metric[4] }}">View All</a>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Alerts Section -->
        <div class="alerts-section">
            @if ($stats['low_stock'] > 0)
                <div class="alert-card warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <h3>{{ $stats['low_stock'] }} Low Stock Products</h3>
                        <a href="{{ route('products.index') }}?low_stock=1">Review Now</a>
                    </div>
                </div>
            @endif

            @if ($stats['expiring_soon'] > 0)
                <div class="alert-card danger">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h3>{{ $stats['expiring_soon'] }} Batches Expiring Soon</h3>
                        <a href="{{ route('batches.index') }}?expiring=1">Check Dates</a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <!-- Product Distribution -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Product Distribution by Volume</h3>
                <div id="productDistributionChart"></div>
            </div>

            <!-- Location Utilization -->
            <div class="chart-card">
                <h3><i class="fas fa-map-marked-alt"></i> Location Utilization</h3>
                <div id="locationUtilizationChart"></div>
            </div>

            <!-- Batch Status -->
            <div class="chart-card">
                <h3><i class="fas fa-clipboard-check"></i> Batch Status</h3>
                <div id="batchStatusChart"></div>
            </div>
        </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            transition: transform 0.2s;
        }

        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 20px;
        }

        .metric-info h2 {
            margin: 0;
            font-size: 1.8rem;
        }

        .metric-info p {
            margin: 5px 0;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .metric-info a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* Alerts Section */
        .alerts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .alert-card {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .alert-card i {
            font-size: 24px;
            margin-right: 15px;
        }

        .alert-card.warning {
            border-left: 4px solid #f39c12;
        }

        .alert-card.warning i {
            color: #f39c12;
        }

        .alert-card.danger {
            border-left: 4px solid #e74c3c;
        }

        .alert-card.danger i {
            color: #e74c3c;
        }

        .alert-card h3 {
            margin: 0 0 5px 0;
            font-size: 1rem;
        }

        .alert-card a {
            color: inherit;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            opacity: 0.8;
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .chart-card h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
        }

        .chart-card h3 i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .metrics-grid {
                grid-template-columns: 1fr 1fr;
            }

            .charts-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Product Distribution Chart (Pie)
            var productDistributionChart = new ApexCharts(
                document.querySelector("#productDistributionChart"), {
                    chart: {
                        type: 'pie',
                        height: 350
                    },
                    series: @json($productDistribution['volumes']),
                    labels: @json($productDistribution['labels']),
                    colors: ['#3498db', '#2ecc71', '#9b59b6', '#e67e22', '#f1c40f', '#1abc9c', '#34495e',
                        '#e74c3c'
                    ],
                    legend: {
                        position: 'bottom'
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 300
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                }
            );
            productDistributionChart.render();

            // Location Utilization Chart (Stacked Bar)
            var locationUtilizationChart = new ApexCharts(
                document.querySelector("#locationUtilizationChart"), {
                    chart: {
                        type: 'bar',
                        height: 350,
                        stacked: true,
                        stackType: '100%'
                    },
                    series: [{
                            name: 'Available',
                            data: @json($locationUtilization['available'])
                        },
                        {
                            name: 'Occupied',
                            data: @json($locationUtilization['occupied'])
                        }
                    ],
                    colors: ['#2ecc71', '#e67e22'],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 4
                        }
                    },
                    xaxis: {
                        categories: @json($locationUtilization['labels']),
                        title: {
                            text: 'Levels'
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'Number of Locations'
                        }
                    },
                    legend: {
                        position: 'bottom'
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 300
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                }
            );
            locationUtilizationChart.render();

            // Batch Status Chart (Donut)
            var batchStatusChart = new ApexCharts(
                document.querySelector("#batchStatusChart"), {
                    chart: {
                        type: 'donut',
                        height: 350
                    },
                    series: [
                        @json($batchStatus['active']),
                        @json($batchStatus['expiring']),
                        @json($batchStatus['expired'])
                    ],
                    labels: ['Active', 'Expiring Soon', 'Expired'],
                    colors: ['#2ecc71', '#f39c12', '#e74c3c'],
                    legend: {
                        position: 'bottom'
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 300
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                }
            );
            batchStatusChart.render();
        });
    </script>
@endpush
