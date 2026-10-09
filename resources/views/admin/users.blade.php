@extends('layouts.app')
@section('title', 'Technician Management')
@section('content')

<div class="row">
    <div class="col-md-12">
        <h2>Technician Management</h2>
        <p class="lead">Manage technician accounts, their barangay territory, and scheduled farm testing.</p>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row mt-4">
    {{-- Add / Edit User Form --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>{{ $editUser ? 'Edit Technician' : 'Add Technician' }}</h5>
            </div>
            <div class="card-body">
                @if($editUser)
                <form method="POST" action="{{ route('admin.users.update', $editUser) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="user_id" value="{{ $editUser->id }}">
                @else
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                @endif
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" value="{{ $editUser ? $editUser->username : old('username') }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name', $editUser->first_name ?? '') }}" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" value="{{ old('middle_name', $editUser->middle_name ?? '') }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name', $editUser->last_name ?? '') }}" required>
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ext. (Jr., III)</label>
                            <input type="text" class="form-control" name="suffix" value="{{ old('suffix', $editUser->suffix ?? '') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address <small class="text-muted">(optional)</small></label>
                        <input type="text" class="form-control" name="address" value="{{ old('address', $editUser->address ?? '') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="{{ $editUser ? $editUser->email : old('email') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">User Type</label>
                        <select class="form-control" name="user_type" required>
                            @foreach(['technician','admin'] as $type)
                            <option value="{{ $type }}" {{ ($editUser ? $editUser->user_type === $type : $type === 'technician') ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password {{ $editUser ? '(leave blank to keep current)' : '*' }}</label>
                        <input type="password" class="form-control" name="password" {{ !$editUser ? 'required' : '' }} minlength="6">
                    </div>
                    @if($editUser)
                    <div class="mb-3">
                        <label class="form-label">Assigned Barangays <small class="text-muted">(territory — ignored for admins)</small></label>
                        <select name="barangay_ids[]" class="form-select" multiple size="5">
                            @foreach($barangays as $b)
                                <option value="{{ $b->id }}" {{ in_array($b->id, $assignedBarangayIds) ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple. Determines which farmers/farms this user can see.</div>
                    </div>
                    @endif
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn {{ $editUser ? 'btn-primary' : 'btn-success' }}">
                            {{ $editUser ? 'Update User' : 'Add User' }}
                        </button>
                        @if($editUser)
                        <a href="{{ route('admin.users') }}" class="btn btn-secondary">Cancel</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Users List --}}
    <div class="col-md-8">
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
                                        <a href="{{ route('admin.users', ['edit_id' => $user->id]) }}" class="btn btn-outline-primary">Edit</a>
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
