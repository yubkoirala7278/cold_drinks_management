<!-- resources/views/warehouse/outbound.blade.php -->
@extends('layouts.mobile')

@section('content')
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Header with progress indicator -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">Outbound Items</h3>
                    <div class="badge bg-primary rounded-pill px-3 py-2">
                        <span id="progressStep">1</span>/2
                    </div>
                </div>

                <!-- Product Selection Card (Step 1) -->
                <div id="productSelectionCard" class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Select Product to Pick</h5>

                        <form id="outboundForm">
                            <!-- Product Selection -->
                            <div class="mb-4">
                                <label for="outboundProduct" class="form-label fw-bold">Product</label>
                                <select class="form-select form-select-lg" id="outboundProduct" required>
                                    <option value="" selected disabled>Select Product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-volume="{{ $product->volume_ml }}">
                                            {{ $product->name }} ({{ $product->volume_ml }}ml)
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Select the product you're picking</div>
                            </div>

                            <!-- Next Button -->
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3">
                                <i class="bi bi-box-arrow-up me-2"></i> Get Next Item
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Picking Card (Step 2) -->
                <div id="pickSection" class="card shadow-sm border-0 d-none">
                    <div class="card-body p-4">
                        <!-- Current Selection Info -->
                        <div class="alert alert-primary d-flex align-items-center mb-4">
                            <div class="flex-shrink-0">
                                <i class="bi bi-info-circle-fill fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="alert-heading mb-1">Current Pick</h6>
                                <p class="mb-0" id="pickProduct"></p>
                            </div>
                        </div>
                        <!-- Location Info -->
                        <div id="pickLocationInfo" class="alert alert-success d-flex align-items-center mb-4">
                            <div class="flex-shrink-0">
                                <i class="bi bi-geo-alt-fill fs-4"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="alert-heading mb-1">Pick from:</h6>
                                <p class="mb-0 fs-5 fw-bold" id="pickLocation"></p>
                            </div>
                        </div>

                        <div id="noItemsAlert" class="alert alert-warning d-none">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No more items available for this product
                        </div>

                        <!-- Barcode Input -->
                        <div class="mb-4">
                            <label for="pickBarcode" class="form-label fw-bold">Scan Barcode</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-upc"></i>
                                </span>
                                <input type="text" class="form-control" id="pickBarcode"
                                    placeholder="Scan or enter barcode" autofocus>
                            </div>
                            <div class="form-text">Scan the item barcode to confirm pick</div>
                        </div>



                        <!-- Error Info -->
                        <div id="pickError" class="alert alert-danger d-flex align-items-center d-none mb-4">
                            <div class="flex-shrink-0">
                                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                            </div>
                            <div class="ms-3" id="pickErrorMessage"></div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-3">
                            <button id="confirmPick" class="btn btn-success btn-lg py-3 d-none">
                                <i class="bi bi-check-circle-fill me-2"></i> Confirm Pick
                            </button>

                            <button id="backToProduct" class="btn btn-outline-secondary btn-lg py-3">
                                <i class="bi bi-arrow-left me-2"></i> Back to Selection
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Footer -->
                <div class="text-center mt-4 text-muted small">
                    <div class="mb-1">Today's outbound items: <strong>{{ $todayOutboundCount ?? 0 }}</strong></div>
                    <div>Current session: <strong id="sessionCount">0</strong></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Consistent with inbound styling */
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
    <!-- Same dependencies as inbound -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/whatwg-fetch@3.6.2/dist/fetch.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup CSRF token for all AJAX requests
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let sessionCount = 0;
            let currentProductId = null;

            // DOM Elements
            const outboundForm = document.getElementById('outboundForm');
            const outboundProduct = document.getElementById('outboundProduct');
            const pickSection = document.getElementById('pickSection');
            const pickBarcode = document.getElementById('pickBarcode');
            const confirmPick = document.getElementById('confirmPick');
            const backToProduct = document.getElementById('backToProduct');
            const pickError = document.getElementById('pickError');
            const pickErrorMessage = document.getElementById('pickErrorMessage');
            const progressStep = document.getElementById('progressStep');
            const sessionCountElement = document.getElementById('sessionCount');

            // Helper functions
            function toggleElement(element, show) {
                element.classList.toggle('d-none', !show);
            }
            // New function to handle no items case
            function showNoItemsAvailable() {
                // Hide location info and scanner
                toggleElement(document.getElementById('pickLocationInfo'), false);
                toggleElement(pickBarcode.parentElement, false);
                toggleElement(confirmPick, false);
                // Show no items message
                toggleElement(document.getElementById('noItemsAlert'), true);
                // Focus back button for better UX
                backToProduct.focus();
            }

            function showPickError(message) {
                pickErrorMessage.textContent = message;
                toggleElement(pickError, true);
                pickBarcode.classList.add('is-invalid');
                setTimeout(() => pickBarcode.classList.remove('is-invalid'), 2000);
            }

            function clearPickErrors() {
                toggleElement(pickError, false);
                pickBarcode.classList.remove('is-invalid');
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
                    if (!response.ok) throw new Error(response.statusText);
                    return response.json();
                });
            }

            // Form submission - get next item
            outboundForm.addEventListener('submit', function(e) {
                e.preventDefault();
                currentProductId = outboundProduct.value;

                if (!currentProductId) {
                    showPickError('Please select a product');
                    return;
                }

                getNextItem(currentProductId);
            });

            // Get next item to pick
            function getNextItem(productId) {
                ajaxRequest('POST', '/warehouse/next-item', {
                        product_id: productId
                    })
                    .then(response => {
                        // Hide no items alert by default
                        toggleElement(document.getElementById('noItemsAlert'), false);

                        // Error handling
                        if (response.error) {
                            if (response.no_items) {
                                showNoItemsAvailable();
                            } else {
                                // For other errors, keep UI elements visible but show error
                                toggleElement(document.getElementById('pickLocationInfo'), true);
                                toggleElement(pickBarcode.parentElement, true);
                                showPickError(response.error);
                            }
                            return;
                        }

                        // Validate response data
                        if (!response.location_id || !response.barcode) {
                            showNoItemsAvailable();
                            return;
                        }

                        // Normal successful case
                        toggleElement(outboundForm.parentElement, false);
                        toggleElement(pickSection, true);
                        progressStep.textContent = '2';

                        // Update UI elements
                        document.getElementById('pickProduct').textContent =
                            outboundProduct.options[outboundProduct.selectedIndex].text;
                        document.getElementById('pickLocation').textContent = response.location;

                        // Ensure correct elements are visible
                        toggleElement(document.getElementById('pickLocationInfo'), true);
                        toggleElement(pickBarcode.parentElement, true);
                        toggleElement(confirmPick, false); // Hide until barcode matches

                        // Store expected data
                        confirmPick.dataset.locationId = response.location_id;
                        confirmPick.dataset.expectedBarcode = response.barcode;

                        // Reset and focus
                        pickBarcode.value = '';
                        pickBarcode.disabled = false;
                        pickBarcode.focus();
                    })
                    .catch(error => {
                        showPickError('Error getting next item: ' + error.message);
                        // Ensure UI is in consistent state even after errors
                        toggleElement(document.getElementById('pickLocationInfo'), false);
                        toggleElement(pickBarcode.parentElement, false);
                    });
            }

            // Barcode scanning
            pickBarcode.addEventListener('input', function() {
                const scannedBarcode = this.value.trim();
                if (!scannedBarcode) return;

                const expectedBarcode = confirmPick.dataset.expectedBarcode;

                if (scannedBarcode === expectedBarcode) {
                    clearPickErrors();
                    toggleElement(confirmPick, true);
                    confirmPick.focus(); // Auto-focus confirm button
                } else {
                    showPickError('Barcode does not match expected item');
                    toggleElement(confirmPick, false);
                }
            });

            // Confirm pick
            confirmPick.addEventListener('click', function() {
                const locationId = this.dataset.locationId;
                const barcode = pickBarcode.value.trim();

                ajaxRequest('POST', '/warehouse/remove-item', {
                        barcode: barcode,
                        location_id: locationId
                    })
                    .then(response => {
                        if (response.success) {
                            // Update counters
                            sessionCount++;
                            sessionCountElement.textContent = sessionCount;

                            // Reset for next pick
                            pickBarcode.value = '';
                            toggleElement(confirmPick, false);
                            clearPickErrors();

                            // Get next item automatically
                            getNextItem(currentProductId);
                        }
                    })
                    .catch(error => {
                        showPickError('Error confirming pick: ' + (error.message || 'Unknown error'));
                    });
            });

            // Back to product selection
            backToProduct.addEventListener('click', function() {
                toggleElement(pickSection, false);
                toggleElement(outboundForm.parentElement, true);
                progressStep.textContent = '1';

                // Reset all elements
                toggleElement(document.getElementById('pickLocationInfo'), true);
                toggleElement(pickBarcode.parentElement, true);
                toggleElement(document.getElementById('noItemsAlert'), false);

                pickBarcode.value = '';
                pickBarcode.disabled = false;
                clearPickErrors();
                toggleElement(confirmPick, false);
                outboundProduct.focus();
            });


            // Initialize with product select focused
            outboundProduct.focus();
        });
    </script>
@endpush
