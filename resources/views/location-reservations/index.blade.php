@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0 font-size-18">Location Reservations</h4>
            <div class="page-title-right">
                <a href="{{ route('location-reservations.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Create Reservation
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Reservation List</h4>
            </div>
            <div class="card-body">

                <table class="table table-bordered table-striped" id="reservations-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Location</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.76563rem;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    var table = $('#reservations-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('location-reservations.index') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'location_code', name: 'location_code'},
            {data: 'product_name', name: 'product.name'},
            {data: 'product_sku', name: 'product.sku'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    // SweetAlert for delete
    $(document).on('click', '.delete-btn', function() {
        var reservationId = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ url('location-reservations') }}/" + reservationId,
                    type: 'DELETE',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            response.success,
                            'success'
                        ).then(() => {
                            table.ajax.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            xhr.responseJSON.error,
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
@endpush