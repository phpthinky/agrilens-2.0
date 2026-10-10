@extends('layouts.app')
@section('title', 'Technician Management')
@section('content')

<div class="row">
    <div class="col-md-8">
        <h2>Technician Management</h2>
        <p class="lead">Manage technician accounts, their barangay territory, and scheduled farm testing.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('admin.users.create') }}" class="btn btn-success">
            <i class="fa fa-plus me-1"></i> Add Technician
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row mt-2">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header"><h5>All Technicians</h5></div>
            <div class="card-body">
                @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>User Type</th><th>Registered</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    @if($user->photo_url)
                                        <img src="{{ $user->photo_url }}" class="rounded-circle me-1" style="width:28px;height:28px;object-fit:cover;">
                                    @else
                                        <span class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center text-white me-1" style="width:28px;height:28px;font-size:13px;">{{ strtoupper(substr($user->username, 0, 1)) }}</span>
                                    @endif
                                    {{ $user->full_name }}
                                    @if($user->id === auth()->id())
                                        <span class="badge bg-primary">You</span>
                                    @endif
                                </td>
                                <td>{{ $user->username }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->user_type === 'admin' ? 'danger' : ($user->user_type === 'technician' ? 'warning' : 'success') }}">
                                        {{ ucfirst($user->user_type) }}
                                    </span>
                                </td>
                                <td>{{ $user->created_at->format('M j, Y') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-success">Profile</a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary">Edit</a>
                                        @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">Delete</button>
                                        </form>
                                        @else
                                        <span class="btn btn-outline-secondary disabled">Current user</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p>No users found.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
