<!-- resources/views/warehouse/inbound.blade.php -->
@extends('layouts.mobile')

@section('content')
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Header with progress indicator -->
                <div class="d-flex justify-content-between align-items-center mb-4">
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
    </style>
@endpush

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
            // Setup CSRF token for all AJAX requests
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let typingTimer;
            const doneTypingInterval = 500; // 500ms delay after typing
            let sessionCount = 0;

            // DOM Elements
            const productSelect = document.getElementById('product');
            const batchSelect = document.getElementById('batch');
            const productForm = document.getElementById('productForm');
            const barcodeInput = document.getElementById('barcode');
            const doneButton = document.getElementById('doneButton');
            const locationInfo = document.getElementById('locationInfo');
            const errorInfo = document.getElementById('errorInfo');
            const errorMessage = document.getElementById('errorMessage');
            const progressStep = document.getElementById('progressStep');
            const productSelectionCard = document.getElementById('productSelectionCard');
            const scanningCard = document.getElementById('scanningCard');
            const sessionCountElement = document.getElementById('sessionCount');

            // Helper functions
            function toggleElement(element, show) {
                if (show) {
                    element.classList.remove('d-none');
                } else {
                    element.classList.add('d-none');
                }
            }

            function showError(message) {
                errorMessage.textContent = message;
                toggleElement(errorInfo, true);
                toggleElement(locationInfo, false);
                toggleElement(doneButton, false);

                // Add visual feedback to input
                barcodeInput.classList.add('is-invalid');
                setTimeout(() => {
                    barcodeInput.classList.remove('is-invalid');
                }, 2000);
            }

            function clearErrors() {
                toggleElement(errorInfo, false);
                toggleElement(locationInfo, false);
                barcodeInput.classList.remove('is-invalid');
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
                batchSelect.innerHTML = '<option value="" selected disabled>Select Batch</option>';
                batchSelect.disabled = true;

                if (!productId) return;

                // Show loading state
                batchSelect.disabled = true;
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const volume = selectedOption.dataset.volume;

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
                        console.error('Error loading batches:', error);
                        batchSelect.innerHTML =
                            '<option value="" selected disabled>Error loading batches</option>';
                        batchSelect.disabled = true;
                    });
            });

            // Handle product form submission
            productForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const productId = productSelect.value;
                const batchId = batchSelect.value;

                if (!productId || !batchId) {
                    showError('Please select both product and batch');
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

                // Focus on barcode input
                barcodeInput.focus();
            });

            // Handle barcode scanning with delay
            barcodeInput.addEventListener('input', function() {
                clearTimeout(typingTimer);
                if (barcodeInput.value.trim()) {
                    // Add scanning feedback
                    barcodeInput.classList.add('scanning-active');

                    typingTimer = setTimeout(() => {
                        barcodeInput.classList.remove('scanning-active');
                        findLocation();
                    }, doneTypingInterval);
                }
            });

            // Function to find location
            function findLocation() {
                const barcode = barcodeInput.value.trim();
                if (!barcode) return;

                const productId = productSelect.value;
                const batchId = batchSelect.value;

                clearErrors();

                // Show loading state
                showError('Checking barcode...');

                // Find location for this barcode
                ajaxRequest('POST', '/warehouse/find-location', {
                        product_id: productId,
                        batch_id: batchId,
                        barcode: barcode
                    })
                    .then(response => {
                        if (response.error) {
                            if (response.barcode_exists) {
                                showError('This barcode already exists. Please scan a different item.');
                            } else if (response.no_location) {
                                showError('No available locations. Contact warehouse management.');
                            } else {
                                showError(response.error);
                            }
                            barcodeInput.value = '';
                            barcodeInput.focus();
                        } else if (response.barcode_valid) {
                            document.getElementById('locationText').textContent = response.location;
                            toggleElement(errorInfo, false);
                            toggleElement(locationInfo, true);
                            doneButton.dataset.locationId = response.location_id;
                            toggleElement(doneButton, true);

                            // Auto-focus the done button for quick confirmation
                            doneButton.focus();
                        }
                    })
                    .catch(error => {
                        showError(error.message || 'Error processing barcode. Please try again.');
                        barcodeInput.value = '';
                        barcodeInput.focus();
                    });
            }

            // Handle done button click
            doneButton.addEventListener('click', function() {
                const locationId = this.dataset.locationId;
                const barcode = barcodeInput.value.trim();

                ajaxRequest('POST', '/warehouse/store-item', {
                        product_id: productSelect.value,
                        batch_id: batchSelect.value,
                        barcode: barcode,
                        location_id: locationId
                    })
                    .then(response => {
                        if (response.success) {
                            // Update SESSION counter
                            sessionCount += response.stats.session_increment;
                            document.getElementById('sessionCount').textContent = sessionCount;

                            // Update TODAY'S counter (from server)
                            const todayCounter = document.querySelector(
                                '.text-muted strong:first-child');
                            todayCounter.textContent = response.stats.today_inbound;

                            // Reset for next scan
                            barcodeInput.value = '';
                            toggleElement(locationInfo, false);
                            barcodeInput.focus();
                        }
                    })
                    .catch(error => {
                        showError('Failed to save: ' + (error.message || 'Unknown error'));
                    });
            });

            // Handle back button
            document.getElementById('backButton').addEventListener('click', function() {
                toggleElement(scanningCard, false);
                toggleElement(productSelectionCard, true);
                progressStep.textContent = '1';

                // Reset scanning state
                barcodeInput.value = '';
                clearErrors();
                toggleElement(doneButton, false);

                // Focus back on product select
                productSelect.focus();
            });

            // Initialize with product select focused
            productSelect.focus();
        });
    </script>
@endpush
