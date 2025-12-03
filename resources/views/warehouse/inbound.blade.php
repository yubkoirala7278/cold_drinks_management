<!-- resources/views/warehouse/inbound.blade.php -->
@extends('layouts.mobile')

@push('styles')
    <style>
        /* Custom styles for better mobile experience */
        .card {
            border-radius: 12px;
        }

        .form-select-lg,
        .input-group-lg .form-control {
            font-size: 1rem;
            padding: 0.75rem 1rem;
        }

        .alert {
            border-radius: 10px;
        }

        /* Animation for scanning feedback */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }

            100% {
                transform: scale(1);
            }
        }

        .scanning-active {
            animation: pulse 1.5s infinite;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.25);
        }

        /* Improved mobile input */
        #barcode {
            font-size: 1.1rem;
            padding: 12px 15px;
            height: 50px;
        }

        /* Better mobile alerts */
        .swal2-popup {
            font-size: 1rem !important;
        }

        /* Larger buttons for mobile */
        .swal2-confirm,
        .swal2-cancel {
            padding: 10px 20px !important;
            margin: 0 5px !important;
            font-size: 1rem !important;
        }

        .highlight-location {
            color: #fff;
            /* White text for better contrast */
            background: linear-gradient(90deg, #ff6b6b, #ff8e8e, #ff6b6b);
            /* Gradient for a vibrant look */
            background-size: 200% auto;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: pulseGradient 2s infinite ease-in-out;
            display: inline-block;
        }

        @keyframes pulseGradient {
            0% {
                background-position: 0% 50%;
                transform: scale(1);
            }

            50% {
                background-position: 100% 50%;
                transform: scale(1.05);
            }

            100% {
                background-position: 0% 50%;
                transform: scale(1);
            }
        }
    </style>
@endpush

@section('content')
    <div class="container py-3">
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
                <!-- Header with progress indicator -->
                <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                    <h3 class="mb-0">Inbound Items</h3>
                    <div class="badge bg-primary rounded-pill px-3 py-2">
                        <span id="progressStep">1</span>/2
                    </div>
                </div>

                <!-- Product Selection Card (Step 1) -->
                <div id="productSelectionCard" class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Select Product & Batch</h5>

                        <form id="productForm">
                            <!-- Product Selection -->
                            <div class="mb-4">
                                <label for="product" class="form-label fw-bold">Product</label>
                                <select class="form-select form-select-lg" id="product" required>
                                    <option value="" selected disabled>Select Product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-volume="{{ $product->volume_ml }}">
                                            {{ $product->name }} ({{ $product->volume_ml }}ml)
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the product you're receiving</div>
                            </div>

                            <!-- Batch Selection -->
                            <div class="mb-4">
                                <label for="batch" class="form-label fw-bold">Batch</label>
                                <select class="form-select form-select-lg" id="batch" required disabled>
                                    <option value="" selected disabled>Select Batch</option>
                                </select>
                                <div class="form-text">Select the product batch</div>
                            </div>

                            <!-- Next Button -->
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3">
                                <i class="bi bi-upc-scan me-2"></i> Start Scanning
                            </button>
                        </form>

                    </div>
                </div>

                <!-- Scanning Card (Step 2) -->
                <div id="scanningCard" class="card shadow-sm border-0 d-none">
                    <div class="card-body p-4">
                        <!-- Current Selection Info -->
                        <div class="alert alert-primary d-flex align-items-center mb-4">
                            <div class="flex-shrink-0">
                                <i class="bi bi-info-circle-fill fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="alert-heading mb-1">Current Selection</h6>
                                <p class="mb-0" id="currentProduct"></p>
                                <p class="mb-0" id="currentBatch"></p>
                            </div>
                        </div>

                        <!-- Barcode Input -->
                        <div class="mb-4">
                            <label for="barcode" class="form-label fw-bold">Scan Barcode</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-upc"></i>
                                </span>
                                <input type="text" class="form-control" id="barcode"
                                    placeholder="Scan or enter barcode" autofocus>
                            </div>
                            <div class="form-text">Scan the product barcode to continue</div>
                        </div>

                        <!-- Location Info -->
                        <div id="locationInfo" class="alert alert-success d-flex align-items-center d-none mb-4">
                            <div class="flex-shrink-0">
                                <i class="bi bi-geo-alt-fill fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="alert-heading mb-1">Place item at:</h6>
                                <p class="mb-0 fs-5 fw-bold" id="locationText"></p>
                            </div>
                        </div>

                        <!-- Error Info -->
                        <div id="errorInfo" class="alert alert-danger d-flex align-items-center d-none mb-4">
                            <div class="flex-shrink-0">
                                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                            </div>
                            <div class="ms-3" id="errorMessage"></div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-3">
                            <button id="doneButton" class="btn btn-success btn-lg py-3 d-none">
                                <i class="bi bi-check-circle-fill me-2"></i> Confirm Placement
                            </button>

                            <button id="backButton" class="btn btn-outline-secondary btn-lg py-3">
                                <i class="bi bi-arrow-left me-2"></i> Back to Selection
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Footer -->
                <div class="text-center mt-4 text-muted small">
                    <div class="mb-1">Today's inbound items: <strong>{{ $todayInboundCount ?? 0 }}</strong></div>
                    <div>Current session: <strong id="sessionCount">0</strong></div>
                </div>
            </div>
        </div>
    </div>
@endsection



@push('scripts')
    <!-- Use a more compatible jQuery version for Android 7 WebView -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <!-- Polyfill for older browsers -->
    <script src="https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/whatwg-fetch@3.6.2/dist/fetch.umd.min.js"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // System inbound barcode (single code for all inbound items)
            const INBOUND_CODE = '{{ config("warehouse.inbound_barcode") }}';

            // Setup CSRF token for all AJAX requests
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let typingTimer;
            const doneTypingInterval = 800; // Slightly longer delay for mobile users
            let sessionCount = 0;

            function toggleElement(element, show) {
                if (element) {
                    if (show) {
                        element.classList.remove('d-none');
                    } else {
                        element.classList.add('d-none');
                    }
                }
            }

            // DOM Elements
            const productSelect = document.getElementById('product');
            const batchSelect = document.getElementById('batch');
            const productForm = document.getElementById('productForm');
            const barcodeInput = document.getElementById('barcode');
            const progressStep = document.getElementById('progressStep');
            const productSelectionCard = document.getElementById('productSelectionCard');
            const scanningCard = document.getElementById('scanningCard');
            const sessionCountElement = document.getElementById('sessionCount');

            // Mobile UX Improvements
            function focusWithMobileKeyboard(element) {
                element.focus();
                // For Android devices to properly show keyboard
                setTimeout(() => {
                    element.focus();
                }, 100);
            }

            // SweetAlert 2 configuration
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

            // Helper functions
            function resetScanningState() {
                barcodeInput.value = '';
                focusWithMobileKeyboard(barcodeInput);
            }

            function ajaxRequest(method, url, data) {
                return fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: data ? JSON.stringify(data) : undefined
                }).then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText);
                    }
                    return response.json();
                });
            }

            // Load batches when product is selected
            productSelect.addEventListener('change', function() {
                const productId = this.value;
                batchSelect.innerHTML = '<option value="" selected disabled>Loading batches...</option>';
                batchSelect.disabled = true;

                if (!productId) return;

                ajaxRequest('GET', `/warehouse/batches/${productId}`)
                    .then(batches => {
                        let options = '<option value="" selected disabled>Select Batch</option>';
                        batches.forEach(batch => {
                            const prodDate = new Date(batch.production_date)
                                .toLocaleDateString();
                            const expDate = new Date(batch.expiry_date).toLocaleDateString();
                            options +=
                                `<option value="${batch.id}">${batch.batch_number} (Prod: ${prodDate}, Exp: ${expDate})</option>`;
                        });
                        batchSelect.innerHTML = options;
                        batchSelect.disabled = false;
                    })
                    .catch(error => {
                        batchSelect.innerHTML =
                            '<option value="" selected disabled>Error loading batches</option>';
                        Toast.fire({
                            icon: 'error',
                            title: 'Failed to load batches'
                        });
                    });
            });

            // Handle product form submission
            productForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const productId = productSelect.value;
                const batchId = batchSelect.value;

                if (!productId || !batchId) {
                    Toast.fire({
                        icon: 'error',
                        title: 'Please select both product and batch'
                    });
                    return;
                }

                // Switch to scanning view
                toggleElement(productSelectionCard, false);
                toggleElement(scanningCard, true);
                progressStep.textContent = '2';

                // Set current product/batch info
                document.getElementById('currentProduct').textContent = productSelect.options[productSelect
                    .selectedIndex].text;
                document.getElementById('currentBatch').textContent = batchSelect.options[batchSelect
                    .selectedIndex].text;

                // Focus on barcode input with mobile keyboard
                focusWithMobileKeyboard(barcodeInput);
            });

            // Handle barcode scanning with delay
            barcodeInput.addEventListener('input', function() {
                clearTimeout(typingTimer);
                if (barcodeInput.value.trim()) {
                    // Add visual feedback
                    barcodeInput.classList.add('scanning-active');

                    typingTimer = setTimeout(() => {
                        barcodeInput.classList.remove('scanning-active');
                        processBarcode();
                    }, doneTypingInterval);
                }
            });

            // Process barcode function
            function processBarcode() {
                const barcode = barcodeInput.value.trim();
                if (!barcode) return;

                // Require the system inbound barcode only
                if (barcode !== INBOUND_CODE) {
                    showErrorAlert('Invalid Barcode', `Please scan the system inbound barcode: ${INBOUND_CODE}`);
                    return;
                }

                const productId = productSelect.value;
                const batchId = batchSelect.value;

                // Show loading state
                Swal.fire({
                    title: 'Processing barcode...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Find location for this barcode
                ajaxRequest('POST', '/warehouse/find-location', {
                        product_id: productId,
                        batch_id: batchId,
                        barcode: barcode
                    })
                    .then(response => {
                        Swal.close();
                        if (response.error) {
                            handleErrorResponse(response);
                        } else if (response.barcode_valid) {
                            handleSuccessResponse(response, barcode);
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        showErrorAlert('Error processing barcode', 'Please try again or contact support');
                    });
            }

            function handleErrorResponse(response) {
                let errorMessage = 'An error occurred';

                if (response.barcode_exists) {
                    errorMessage = 'This barcode already exists in the system. Please scan a different item.';
                } else if (response.no_location) {
                    errorMessage =
                        'All storage locations are currently full for this product. Please contact warehouse management.';
                } else if (response.error) {
                    errorMessage = response.error;
                }

                showErrorAlert('Cannot Process Item', errorMessage);
            }

            function handleSuccessResponse(response, barcode) {
                const location = response.location;

                Swal.fire({
                    title: 'Storage Location Found',
                    html: `Please place this item at: <span class="highlight-location">${location}</span>`,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, I placed it',
                    cancelButtonText: 'No, cancel',
                    reverseButtons: true,
                    focusConfirm: false,
                    focusCancel: true,
                    customClass: {
                        confirmButton: 'btn btn-success mx-2',
                        cancelButton: 'btn btn-danger mx-2',
                        popup: 'custom-swal-popup' // Optional: if you need to target the popup
                    },
                    buttonsStyling: false
                }).then((result) => {
                    resetScanningState();

                    if (result.isConfirmed) {
                        confirmItemPlacement(response, barcode);
                    }
                });
            }

            function confirmItemPlacement(response, barcode) {
                Swal.fire({
                    title: 'Saving item...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                ajaxRequest('POST', '/warehouse/store-item', {
                        product_id: productSelect.value,
                        batch_id: batchSelect.value,
                        barcode: barcode,
                        location_id: response.location_id
                    })
                    .then(response => {
                        Swal.close();
                        if (response.success) {
                            // Update counters
                            sessionCount += response.stats.session_increment;
                            sessionCountElement.textContent = sessionCount;

                            Toast.fire({
                                icon: 'success',
                                title: 'Item saved successfully!'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        showErrorAlert('Save Failed', 'Failed to save item. Please try again.');
                    });
            }

            function showErrorAlert(title, message) {
                return Swal.fire({
                    title: title,
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-danger py-2 px-4'
                    },
                    buttonsStyling: false,
                    allowOutsideClick: false
                }).then(() => {
                    resetScanningState();
                });
            }

            // Handle back button
            document.getElementById('backButton').addEventListener('click', function() {
                toggleElement(scanningCard, false);
                toggleElement(productSelectionCard, true);
                progressStep.textContent = '1';
                resetScanningState();
                productSelect.focus();
            });

            // Initialize with product select focused
            focusWithMobileKeyboard(productSelect);
        });
    </script>
@endpush
