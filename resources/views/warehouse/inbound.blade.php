<!-- resources/views/warehouse/inbound.blade.php -->
@extends('layouts.mobile')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h3 class="text-center mb-4">Inbound Items</h3>

                <div class="card mb-3">
                    <div class="card-body">
                        <form id="productForm">
                            <div class="form-group">
                                <label for="product">Product</label>
                                <select class="form-control" id="product" required>
                                    <option value="">Select Product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}
                                            ({{ $product->volume_ml }}ml)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="batch">Batch</label>
                                <select class="form-control" id="batch" required disabled>
                                    <option value="">Select Batch</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Start Scanning</button>
                        </form>
                    </div>
                </div>

                <div id="scanningSection" class="d-none">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Scanning Mode</h5>
                            <p class="card-text">Product: <span id="currentProduct"></span></p>
                            <p class="card-text">Batch: <span id="currentBatch"></span></p>

                            <div class="form-group">
                                <label for="barcode">Barcode</label>
                                <input type="text" class="form-control" id="barcode" autofocus>
                                <small class="form-text text-muted">Scan barcode to continue</small>
                            </div>

                            <div id="locationInfo" class="alert alert-info d-none">
                                Place item at: <strong id="locationText"></strong>
                            </div>

                            <div id="errorInfo" class="alert alert-danger d-none"></div>

                            <button id="doneButton" class="btn btn-success btn-block mt-3 d-none">
                                Done
                            </button>

                            <button id="backButton" class="btn btn-secondary btn-block mt-2">
                                Back to Product Selection
                            </button>
                        </div>
                    </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup CSRF token for all AJAX requests
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let typingTimer;
            const doneTypingInterval = 10; // 10 milli second

            // Helper function to toggle visibility
            function toggleElement(element, show) {
                element.classList.toggle('d-none', !show);
            }

            // Helper function for AJAX requests
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
            const productSelect = document.getElementById('product');
            const batchSelect = document.getElementById('batch');

            productSelect.addEventListener('change', function() {
                const productId = this.value;
                if (!productId) {
                    batchSelect.innerHTML = '<option value="">Select Batch</option>';
                    batchSelect.disabled = true;
                    return;
                }

                ajaxRequest('GET', `/warehouse/batches/${productId}`)
                    .then(batches => {
                        let options = '<option value="">Select Batch</option>';
                        batches.forEach(batch => {
                            options +=
                                `<option value="${batch.id}">${batch.batch_number} (Prod: ${batch.production_date}, Exp: ${batch.expiry_date})</option>`;
                        });
                        batchSelect.innerHTML = options;
                        batchSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error loading batches:', error);
                        batchSelect.innerHTML = '<option value="">Error loading batches</option>';
                        batchSelect.disabled = true;
                    });
            });

            // Handle product form submission
            const productForm = document.getElementById('productForm');
            productForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const productId = productSelect.value;
                const batchId = batchSelect.value;

                if (!productId || !batchId) {
                    return;
                }

                // Show scanning section
                toggleElement(document.getElementById('scanningSection'), true);
                toggleElement(productForm, false);

                // Set current product/batch info
                document.getElementById('currentProduct').textContent = productSelect.options[productSelect
                    .selectedIndex].text;
                document.getElementById('currentBatch').textContent = batchSelect.options[batchSelect
                    .selectedIndex].text;

                // Focus on barcode input
                document.getElementById('barcode').focus();
            });

            // Handle barcode scanning with delay
            const barcodeInput = document.getElementById('barcode');

            // On keyup, start the countdown
            barcodeInput.addEventListener('keyup', function() {
                clearTimeout(typingTimer);
                if (barcodeInput.value) {
                    typingTimer = setTimeout(findLocation, doneTypingInterval);
                }
            });

            // On keydown, clear the countdown
            barcodeInput.addEventListener('keydown', function() {
                clearTimeout(typingTimer);
            });

            // Function to find location
            function findLocation() {
                const barcode = barcodeInput.value;
                if (!barcode) return;

                const productId = productSelect.value;
                const batchId = batchSelect.value;

                // Clear previous messages
                toggleElement(errorInfo, false);
                toggleElement(locationInfo, false);
                toggleElement(doneButton, false);

                // Show loading state
                errorInfo.textContent = 'Checking barcode...';
                toggleElement(errorInfo, true);

                // Find location for this barcode
                ajaxRequest('POST', '/warehouse/find-location', {
                        product_id: productId,
                        batch_id: batchId,
                        barcode: barcode
                    })
                    .then(response => {
                        if (response.error) {
                            if (response.barcode_exists) {
                                errorInfo.textContent =
                                    'Error: This barcode already exists in the system. Please scan a different barcode.';
                            } else if (response.no_location) {
                                errorInfo.textContent =
                                    'Error: No available locations found for this product. Please contact warehouse management.';
                            } else {
                                errorInfo.textContent = response.error;
                            }
                            toggleElement(errorInfo, true);
                            toggleElement(locationInfo, false);
                            toggleElement(doneButton, false);

                            // Clear the barcode field and focus for new input
                            barcodeInput.value = '';
                            barcodeInput.focus();
                        } else if (response.barcode_valid) {
                            document.getElementById('locationText').textContent = response.location;
                            toggleElement(errorInfo, false);
                            toggleElement(locationInfo, true);
                            doneButton.dataset.locationId = response.location_id;
                            toggleElement(doneButton, true);
                        }
                    })
                    .catch(error => {
                        errorInfo.textContent = error.message || 'Error processing barcode. Please try again.';
                        toggleElement(errorInfo, true);
                        toggleElement(locationInfo, false);
                        toggleElement(doneButton, false);

                        // Clear the barcode field and focus for new input
                        barcodeInput.value = '';
                        barcodeInput.focus();
                    });
            }

            // Handle done button click
            const doneButton = document.getElementById('doneButton');
            doneButton.addEventListener('click', function() {
                const locationId = this.dataset.locationId;
                const barcode = barcodeInput.value;
                const productId = productSelect.value;
                const batchId = batchSelect.value;

                if (!locationId || !barcode) return;

                // Store the item
                ajaxRequest('POST', '/warehouse/store-item', {
                        product_id: productId,
                        batch_id: batchId,
                        barcode: barcode,
                        location_id: locationId
                    })
                    .then(() => {
                        // Clear barcode and focus for next scan
                        barcodeInput.value = '';
                        barcodeInput.focus();
                        toggleElement(document.getElementById('locationInfo'), false);
                        toggleElement(doneButton, false);
                    })
                    .catch(error => {
                        alert(error.message || 'Error storing item');
                    });
            });

            // Handle back button
            document.getElementById('backButton').addEventListener('click', function() {
                toggleElement(document.getElementById('scanningSection'), false);
                toggleElement(productForm, true);
                barcodeInput.value = '';
                toggleElement(document.getElementById('locationInfo'), false);
                toggleElement(document.getElementById('errorInfo'), false);
                toggleElement(doneButton, false);
            });
        });
    </script>
@endpush