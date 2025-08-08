@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Product List</h4>
                    <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm"> <i class="fas fa-plus"></i> Add Product</a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover" id="products-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>SKU</th>
                                <th>Volume (ml)</th>
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
            $('#products-table').DataTable({
                processing: true,
                serverSide: true,
                searchDelay: 1000,
                ajax: "{{ route('products.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'sku',
                        name: 'sku'
                    },
                    {
                        data: 'volume_ml',
                        name: 'volume_ml'
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
                    [5, 'desc']
                ],
            });

            // SweetAlert for delete
            $(document).on('click', '.delete-btn', function() {
                let productId = $(this).data('id');

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
                            url: "{{ url('products') }}/" + productId,
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
                                    $('#products-table').DataTable().ajax
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
