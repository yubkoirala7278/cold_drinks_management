<!-- resources/views/warehouse/outbound.blade.php -->
@extends('layouts.mobile')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h3 class="text-center mb-4">Outbound Items</h3>

                <div class="card mb-3">
                    <div class="card-body">
                        <form id="outboundForm">
                            <div class="form-group">
                                <label for="outboundProduct">Product</label>
                                <select class="form-control" id="outboundProduct" required>
                                    <option value="">Select Product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}
                                            ({{ $product->volume_ml }}ml)</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Get Next Item</button>
                        </form>
                    </div>
                </div>

                <div id="pickSection" class="card d-none">
                    <div class="card-body">
                        <h5 class="card-title">Pick Item</h5>
                        <p class="card-text">Product: <span id="pickProduct"></span></p>

                        <div class="alert alert-info">
                            Pick from: <strong id="pickLocation"></strong>
                        </div>

                        <div class="form-group">
                            <label for="pickBarcode">Scan Barcode</label>
                            <input type="text" class="form-control" id="pickBarcode" autofocus>
                            <small class="form-text text-muted">Scan the barcode to confirm pick</small>
                        </div>

                        <div id="pickError" class="alert alert-danger d-none"></div>

                        <button id="confirmPick" class="btn btn-success btn-block mt-3 d-none">
                            Confirm Pick
                        </button>

                        <button id="backToProduct" class="btn btn-secondary btn-block mt-2">
                            Back to Product Selection
                        </button>
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
            // Handle outbound form submission
            $('#outboundForm').submit(function(e) {
                e.preventDefault();

                const productId = $('#outboundProduct').val();
                if (!productId) return;

                // Get next item to pick
                $.post('/warehouse/next-item', {
                    product_id: productId
                }, function(response) {
                    if (response.error) {
                        alert(response.error);
                        return;
                    }

                    // Show pick section
                    $('#outboundForm').addClass('d-none');
                    $('#pickSection').removeClass('d-none');

                    // Set pick info
                    $('#pickProduct').text($('#outboundProduct option:selected').text());
                    $('#pickLocation').text(response.location);

                    // Store location ID and expected barcode
                    $('#confirmPick').data({
                        'location-id': response.location_id,
                        'expected-barcode': response.barcode
                    });

                    // Focus on barcode input
                    $('#pickBarcode').focus();
                });
            });

            // Handle barcode scanning for pick
            $('#pickBarcode').change(function() {
                const scannedBarcode = $(this).val();
                if (!scannedBarcode) return;

                const expectedBarcode = $('#confirmPick').data('expected-barcode');

                if (scannedBarcode === expectedBarcode) {
                    $('#pickError').addClass('d-none');
                    $('#confirmPick').removeClass('d-none');
                } else {
                    $('#pickError').text('Barcode does not match expected item').removeClass('d-none');
                    $('#confirmPick').addClass('d-none');
                }
            });

            // Handle confirm pick
            $('#confirmPick').click(function() {
                const locationId = $(this).data('location-id');
                const barcode = $('#pickBarcode').val();

                if (!locationId || !barcode) return;

                // Remove the item
                $.post('/warehouse/remove-item', {
                    barcode: barcode,
                    location_id: locationId
                }, function() {
                    // Reset for next pick
                    $('#pickBarcode').val('').focus();
                    $('#pickError').addClass('d-none');
                    $('#confirmPick').addClass('d-none');

                    // Get next item automatically
                    const productId = $('#outboundProduct').val();
                    $.post('/warehouse/next-item', {
                        product_id: productId
                    }, function(response) {
                        if (response.error) {
                            alert(response.error);
                            return;
                        }

                        // Update pick info
                        $('#pickLocation').text(response.location);

                        // Store new location ID and expected barcode
                        $('#confirmPick').data({
                            'location-id': response.location_id,
                            'expected-barcode': response.barcode
                        });
                    });
                }).fail(function(xhr) {
                    alert(xhr.responseJSON?.error || 'Error removing item');
                });
            });

            // Handle back to product
            $('#backToProduct').click(function() {
                $('#pickSection').addClass('d-none');
                $('#outboundForm').removeClass('d-none');
                $('#pickBarcode').val('');
                $('#pickError').addClass('d-none');
                $('#confirmPick').addClass('d-none');
            });
        });
    </script>
@endpush
