@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">My Profile</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('warehouse.dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">Profile</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-xl mx-auto mb-4">
                        <img src="{{ asset('asset/images/admin.webp') }}" alt="Profile" class="rounded-circle img-fluid border border-primary"
                            height="80" width="80">
                    </div>
                    <h5 class="font-size-16 mb-1">{{ Auth::user()->name }}</h5>
                    <p class="text-muted mb-2">{{ ucfirst(str_replace('_', ' ', Auth::user()->role)) }}</p>

                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Account Information</h5>

                    <div class="table-responsive">
                        <table class="table mb-0">
                            <tbody>
                                <tr>
                                    <th scope="row">Name:</th>
                                    <td>{{ Auth::user()->name }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Email:</th>
                                    <td>{{ Auth::user()->email }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Role:</th>
                                    <td>
                                        <span class="badge bg-primary">
                                            {{ ucfirst(str_replace('_', ' ', Auth::user()->role)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Joined:</th>
                                    <td>{{ Auth::user()->created_at->format('d M Y') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Recent Activity</h4>

                    <div class="activity-feed">
                        <div class="feed-item">
                            <div class="feed-item-list">
                                <p class="text-muted mb-1">Today</p>
                                <p class="mb-0">Logged in to the system</p>
                                <small class="text-muted">Just now</small>
                            </div>
                        </div>
                        <!-- Add more activity items as needed -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .avatar-xl {
            height: 6rem;
            width: 6rem;
            line-height: 6rem;
            font-size: 2.25rem;
        }

        .avatar-title {
            align-items: center;
            display: flex;
            height: 100%;
            justify-content: center;
            width: 100%;
        }

        .activity-feed {
            padding-left: 16px;
            border-left: 2px solid #f3f3f3;
        }

        .feed-item {
            position: relative;
            padding-bottom: 19px;
            padding-left: 16px;
            border-left: 2px solid #f3f3f3;
        }

        .feed-item:last-child {
            border-color: transparent;
        }

        .feed-item:before {
            content: "";
            position: absolute;
            left: -9px;
            top: 0;
            background-color: #727cf5;
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }
    </style>
@endpush
