@extends('layouts.mobile')


@push('styles')
    <style>
        /* Mobile-optimized styles */
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .form-select-lg,
        .input-group-lg .form-control {
            font-size: 1rem;
            padding: 0.75rem 1rem;
            height: auto;
        }

        .btn-lg {
            padding: 0.75rem 1rem;
            font-size: 1.1rem;
        }

        /* Scanning feedback */
        #pickBarcode.scan-success {
            animation: pulseSuccess 1s ease;
        }

        @keyframes pulseSuccess {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
    </style>
@endpush

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Icon Logout -->
                <div class="text-end" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    style="cursor: pointer;">
                    <i class="fa-solid fa-arrow-right-from-bracket fs-5"></i>
                </div>

                <!-- Hidden Logout Form -->
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>

                <!-- Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-4 mt-3">

                    <h3 class="mb-0">Outbound Picking</h3>
                    <div class="badge bg-primary rounded-pill px-3 py-2">
                        <span id="progressStep">1</span>/2
                    </div>

                </div>

                <!-- Stats Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-around text-center">
                            <div>
                                <div class="text-muted small">Today's Picks</div>
                                <div class="h5 mb-0">{{ $todayOutboundCount }}</div>
                            </div>
                            @if ($topProducts->isNotEmpty())
                                <div>
                                    <div class="text-muted small">Top Product</div>
                                    <div class="h5 mb-0">{{ $topProducts->first()->product_name }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Product Selection Card -->
                <div id="productSelectionCard" class="card shadow-sm border-0 mb-3">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">Select Product to Pick</h5>

                        <form id="outboundForm">
                            <div class="mb-3">
                                <label for="outboundProduct" class="form-label fw-bold">Product</label>
                                <select class="form-select form-select-lg" id="outboundProduct" required>
                                    <option value="" selected disabled>Select Product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->name }} ({{ $product->volume_ml }}ml)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3">
                                <i class="bi bi-box-arrow-up me-2"></i> Find Next Item
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Picking Interface -->
                <div id="pickSection" class="card shadow-sm border-0 d-none">
                    <div class="card-body p-4">
                        <!-- Current Pick Info -->
                        <div class="alert alert-primary mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                                <div>
                                    <h6 class="alert-heading mb-1" id="pickProductName"></h6>
                                    <div class="small">Ready to pick</div>
                                </div>
                            </div>
                        </div>

                        <!-- Location Card -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-geo-alt-fill text-primary fs-3 me-3"></i>
                                    <div>
                                        <div class="text-muted small">Pick From</div>
                                        <div class="h4 mb-0 fw-bold" id="pickLocation"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Barcode Scanner -->
                        <div class="mb-4">
                            <label for="pickBarcode" class="form-label fw-bold">Scan Barcode</label>
                            <div class="input-group input-group-lg mb-2">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-upc-scan"></i>
                                </span>
                                <input type="text" class="form-control" id="pickBarcode" placeholder="Scan barcode"
                                    autofocus>
                            </div>
                            <div class="form-text">Confirm item by scanning its barcode</div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button id="backButton" class="btn btn-outline-secondary btn-lg py-3">
                                <i class="bi bi-arrow-left me-2"></i> Back to Selection
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Session Counter -->
                <div class="text-center mt-3">
                    <div class="text-muted small">Session Picks: <strong id="sessionCount">0</strong></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <script>
        $(document).ready(function() {
            // System outbound barcode (client-wide single pick barcode)
            const OUTBOUND_CODE = '{{ config('warehouse.outbound_barcode') }}';
            const Swal = window.Swal;
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            let sessionCount = 0;
            let currentProduct = null;
            let expectedBarcode = null;
            let currentLocationId = null;

            // Initialize with product select focused
            $('#outboundProduct').focus();

            // Form submission
            $('#outboundForm').on('submit', function(e) {
                e.preventDefault();
                currentProduct = $('#outboundProduct').val();
                const productName = $('#outboundProduct option:selected').text();

                if (!currentProduct) {
                    showError('Please select a product');
                    return;
                }

                findNextItem(currentProduct, productName);
            });

            // Barcode scanning with enhanced feedback
            $('#pickBarcode').on('input', debounce(function() {
                const scannedBarcode = $(this).val().trim();

                if (!scannedBarcode || scannedBarcode.length < 3) return;

                // Only accept the configured system outbound barcode for confirming picks
                if (scannedBarcode === OUTBOUND_CODE) {
                    $(this).addClass('scan-success');
                    setTimeout(() => {
                        $(this).removeClass('scan-success');
                        showConfirmationDialog();
                    }, 500);
                } else {
                    showBarcodeMismatchAlert(scannedBarcode);
                }
            }, 500));

            // Back button
            $('#backButton').click(function() {
                resetToSelection();
            });

            // Core functions
            function findNextItem(productId, productName) {
                showLoading('Finding next item...');

                $.ajax({
                    url: '/warehouse/next-item',
                    method: 'POST',
                    data: {
                        product_id: productId
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.close();

                        if (response.error) {
                            if (response.no_items) {
                                showNoItemsAlert(productName);
                            } else {
                                showError(response.error);
                            }
                            return;
                        }

                        // Update UI
                        $('#pickProductName').text(productName);
                        $('#pickLocation').text(response.location);
                        expectedBarcode = response.barcode;
                        currentLocationId = response.location_id;

                        // Show picking interface
                        $('#productSelectionCard').addClass('d-none');
                        $('#pickSection').removeClass('d-none');
                        $('#progressStep').text('2');

                        // Reset scanner
                        $('#pickBarcode').val('').focus();
                    },
                    error: function(xhr) {
                        Swal.close();
                        showError('Failed to find next item. Please try again.');
                    }
                });
            }

            function showConfirmationDialog() {
                Swal.fire({
                    title: 'Confirm Pick',
                    html: `Please confirm you're picking <strong>${$('#pickProductName').text()}</strong> from <strong>${$('#pickLocation').text()}</strong>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    confirmButtonText: 'Yes, confirm pick',
                    cancelButtonText: 'Cancel',
                    focusCancel: true,
                    allowOutsideClick: false,
                    preConfirm: () => {
                        return processPick();
                    }
                }).then((result) => {
                    // if (result.isConfirmed) {
                    $('#pickBarcode').val('').focus();
                    // }
                });
            }

            function processPick() {
                showLoading('Processing pick...');

                return new Promise((resolve) => {
                    $.ajax({
                        url: '/warehouse/remove-item',
                        method: 'POST',
                        data: {
                            // Send the system outbound barcode so server can resolve by location
                            barcode: OUTBOUND_CODE,
                            location_id: currentLocationId
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            Swal.close();
                            if (response.success) {
                                // Update counters
                                sessionCount++;
                                $('#sessionCount').text(sessionCount);

                                Toast.fire({
                                    icon: 'success',
                                    title: 'Pick confirmed!'
                                });

                                // Get next item automatically
                                findNextItem(currentProduct, $('#pickProductName').text());
                            }
                            resolve(true);
                        },
                        error: function(xhr) {
                            Swal.close();
                            showError('Failed to confirm pick. Please try again.');
                            resolve(false);
                        }
                    });
                });
            }

            function showBarcodeMismatchAlert(scannedBarcode) {
                Swal.fire({
                    title: 'Barcode Mismatch',
                    html: `Scanned barcode <strong>${scannedBarcode}</strong> doesn't match expected item`,
                    icon: 'error',
                    confirmButtonText: 'Try Again',
                    focusConfirm: false,
                    allowOutsideClick: false
                }).then(() => {
                    $('#pickBarcode').val('').focus();
                });
            }

            function showNoItemsAlert(productName) {
                Swal.fire({
                    title: 'No Items Available',
                    html: `No more items found for <strong>${productName}</strong>`,
                    icon: 'info',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false
                }).then(() => {
                    resetToSelection();
                });
            }

            function resetToSelection() {
                $('#pickSection').addClass('d-none');
                $('#productSelectionCard').removeClass('d-none');
                $('#progressStep').text('1');
                $('#outboundProduct').focus();
            }

            function showLoading(title) {
                Swal.fire({
                    title: title,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            function showError(message) {
                Toast.fire({
                    icon: 'error',
                    title: message
                });
            }

            function debounce(func, wait) {
                let timeout;
                return function() {
                    const context = this,
                        args = arguments;
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(context, args), wait);
                };
            }
        });
    </script>
@endpush
