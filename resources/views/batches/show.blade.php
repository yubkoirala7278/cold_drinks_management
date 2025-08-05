@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Batch Details</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('warehouse.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('batches.index') }}">Batches</a></li>
                    <li class="breadcrumb-item active">Details</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Batch Information</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <h5>Product:</h5>
                            <p>{{ $batch->product->name }} ({{ $batch->product->sku }})</p>
                        </div>
                        <div class="mb-3">
                            <h5>Batch Number:</h5>
                            <p>{{ $batch->batch_number }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <h5>Production Date:</h5>
                            <p>{{ $batch->production_date->format('d M Y') }}</p>
                        </div>
                        <div class="mb-3">
                            <h5>Expiry Date:</h5>
                            <p>{{ $batch->expiry_date->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('batches.edit', $batch->id) }}" class="btn btn-primary">Edit</a>
                    <a href="{{ route('batches.index') }}" class="btn btn-light">Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection