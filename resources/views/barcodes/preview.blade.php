@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Barcode Preview</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Generated Barcodes ({{ $start }} to {{ $end }})</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        Successfully generated {{ count($barcodes) }} barcodes. Database has been updated.
                    </div>

                    <div class="alert alert-info">
                        Preview of first 5 barcodes. The PDF will contain all {{ count($barcodes) }} barcodes.
                    </div>

                    <div class="row mb-4">
                        @foreach (array_slice($barcodes, 0, 5) as $barcode)
                            <div class="col-md-3 mb-3">
                                <div class="border p-2 text-center">
                                    <small>{{ $barcode['code'] }}</small>
                                    <div class="mt-2">{!! $barcode['barcode'] !!}</div>
                                    <div class="mt-1">{{ $barcode['number'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('barcodes.export.pdf') }}" class="btn btn-primary" target="_blank">
                            <i class="fas fa-file-pdf"></i> Export to PDF
                        </a>

                        <a href="{{ route('barcodes.generate') }}" class="btn btn-secondary">
                            Generate More Barcodes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
