<!-- resources/views/layouts/mobile.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <!-- ES5 Shim for older Android browsers -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/es5-shim/4.5.7/es5-shim.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Warehouse Management</title>
    {{-- bootstrap cdn --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- font awesome cdn --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    {{-- sweet alert cdn --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            padding-top: 10px;
            font-size: 14px;
        }

        .btn-block {
            white-space: normal;
        }

        .form-control {
            font-size: 14px;
        }

        .card {
            margin-bottom: 15px;
        }
       
    </style>
     @stack('styles')
</head>

<body>
    <div class="container-fluid">
        @yield('content')
    </div>

    {{-- bootstrap --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    {{-- sweet alert --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- custom script --}}
    @stack('scripts')
</body>

</html>
