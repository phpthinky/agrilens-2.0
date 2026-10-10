@extends('layouts.app')
@section('title', 'Add Technician')
@section('content')

<div class="row mb-3">
    <div class="col">
        <a href="{{ route('admin.users') }}" class="text-muted small"><i class="fa fa-arrow-left me-1"></i>Back to Technician Management</a>
        <h2 class="mt-2">Add Technician</h2>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>Technician Details</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    @include('admin.users._form', ['barangays' => $barangays])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
