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
    <script>
        $(document).ready(function() {
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            // Load batches when product is selected
            $('#product').change(function() {
                const productId = $(this).val();
                if (!productId) {
                    $('#batch').html('<option value="">Select Batch</option>').prop('disabled', true);
                    return;
                }

                $.get(`/warehouse/batches/${productId}`, function(batches) {
                    let options = '<option value="">Select Batch</option>';
                    batches.forEach(batch => {
                        options +=
                            `<option value="${batch.id}">${batch.batch_number} (Prod: ${batch.production_date}, Exp: ${batch.expiry_date})</option>`;
                    });
                    $('#batch').html(options).prop('disabled', false);
                });
            });

            // Handle product form submission
            $('#productForm').submit(function(e) {
                e.preventDefault();

                const productId = $('#product').val();
                const batchId = $('#batch').val();

                if (!productId || !batchId) {
                    return;
                }

                // Show scanning section
                $('#scanningSection').removeClass('d-none');
                $('#productForm').addClass('d-none');

                // Set current product/batch info
                $('#currentProduct').text($('#product option:selected').text());
                $('#currentBatch').text($('#batch option:selected').text());

                // Focus on barcode input
                $('#barcode').focus();
            });

            // Handle barcode scanning
            $('#barcode').change(function() {
                const barcode = $(this).val();
                if (!barcode) return;

                const productId = $('#product').val();
                const batchId = $('#batch').val();

                // Find location for this barcode
                $.post('/warehouse/find-location', {
                    product_id: productId,
                    batch_id: batchId,
                    barcode: barcode
                }, function(response) {
                    if (response.error) {
                        $('#errorInfo').text(response.error).removeClass('d-none');
                        $('#locationInfo').addClass('d-none');
                        $('#doneButton').addClass('d-none');
                    } else {
                        $('#locationText').text(response.location);
                        $('#locationInfo').removeClass('d-none');
                        $('#errorInfo').addClass('d-none');

                        // Store the location ID in the done button
                        $('#doneButton').data('location-id', response.location_id).removeClass(
                            'd-none');
                    }
                }).fail(function(xhr) {
                    $('#errorInfo').text(xhr.responseJSON?.error || 'Error processing barcode')
                        .removeClass('d-none');
                    $('#locationInfo').addClass('d-none');
                    $('#doneButton').addClass('d-none');
                });
            });

            // Handle done button click
            $('#doneButton').click(function() {
                const locationId = $(this).data('location-id');
                const barcode = $('#barcode').val();
                const productId = $('#product').val();
                const batchId = $('#batch').val();

                if (!locationId || !barcode) return;

                // Store the item
                $.post('/warehouse/store-item', {
                    product_id: productId,
                    batch_id: batchId,
                    barcode: barcode,
                    location_id: locationId
                }, function() {
                    // Clear barcode and focus for next scan
                    $('#barcode').val('').focus();
                    $('#locationInfo').addClass('d-none');
                    $('#doneButton').addClass('d-none');
                }).fail(function(xhr) {
                    alert(xhr.responseJSON?.error || 'Error storing item');
                });
            });

            // Handle back button
            $('#backButton').click(function() {
                $('#scanningSection').addClass('d-none');
                $('#productForm').removeClass('d-none');
                $('#barcode').val('');
                $('#locationInfo').addClass('d-none');
                $('#errorInfo').addClass('d-none');
                $('#doneButton').addClass('d-none');
            });
        });
    </script>
@endpush
