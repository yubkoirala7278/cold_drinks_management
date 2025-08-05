@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Product Details</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('warehouse.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
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
                    <h4 class="card-title">Product Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h5>Name:</h5>
                                <p>{{ $product->name }}</p>
                            </div>
                            <div class="mb-3">
                                <h5>SKU:</h5>
                                <p>{{ $product->sku }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h5>Volume (ml):</h5>
                                <p>{{ $product->volume_ml }}</p>
                            </div>
                            <div class="mb-3">
                                <h5>Created At:</h5>
                                <p>{{ $product->created_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary">Edit</a>
                        <a href="{{ route('products.index') }}" class="btn btn-light">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
