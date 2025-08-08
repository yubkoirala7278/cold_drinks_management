@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Batch List</h4>
                    <a href="{{ route('batches.create') }}" class="btn btn-primary btn-sm"> <i class="fas fa-plus"></i> Add Batch</a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover" id="batches-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Product SKU</th>
                                <th>Batch Number</th>
                                <th>Production Date</th>
                                <th>Expiry Date</th>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
            $('#batches-table').DataTable({
                processing: true,
                serverSide: true,
                searchDelay: 1000,
                ajax: "{{ route('batches.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'product_name',
                        name: 'product.name'
                    },
                    {
                        data: 'product_sku',
                        name: 'product.sku'
                    },
                    {
                        data: 'batch_number',
                        name: 'batch_number'
                    },
                    {
                        data: 'production_date_formatted',
                        name: 'production_date'
                    },
                    {
                        data: 'expiry_date_formatted',
                        name: 'expiry_date'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        visible: false
                    }
                ],
                order: [
                    [7, 'desc']
                ]
            });

            // SweetAlert for delete
            $(document).on('click', '.delete-btn', function() {
                let batchId = $(this).data('id');

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
                            url: "{{ url('batches') }}/" + batchId,
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
                                    $('#batches-table').DataTable().ajax
                                        .reload();
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
