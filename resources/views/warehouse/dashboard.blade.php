<!-- resources/views/warehouse/dashboard.blade.php -->
@extends('layouts.mobile')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 text-center mb-4">
            <h2>Warehouse Management</h2>
        </div>
        
        <div class="col-12 mb-3">
            <a href="{{ route('warehouse.inbound') }}" class="btn btn-primary btn-block py-3">
                <i class="fas fa-arrow-down"></i> Inbound
            </a>
        </div>
        
        <div class="col-12">
            <a href="{{ route('warehouse.outbound') }}" class="btn btn-success btn-block py-3">
                <i class="fas fa-arrow-up"></i> Outbound
            </a>
        </div>
    </div>
</div>
@endsection