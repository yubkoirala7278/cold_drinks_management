@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Barcode Generator</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Generate New Barcodes</h4>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="{{ route('barcodes.generate.post') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Number of Barcodes to Generate</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1"
                                    max="10000" value="100" required>
                                <small class="text-muted">Max 10,000 at a time</small>
                            </div>
                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-primary">Generate Barcodes</button>
                            </div>
                        </div>
                    </form>

                    <div class="mt-4 pt-3 border-top">
                        <h5>Current Status</h5>
                        <p>Last generated number: {{ str_pad($lastNumber, 8, '0', STR_PAD_LEFT) }}</p>
                        <p>Next barcode will start from: {{ str_pad($nextNumber, 8, '0', STR_PAD_LEFT) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
