@extends('layouts.app')
@section('title', 'Edit Farm - ' . $farm->farm_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-edit me-2"></i>Edit Farm: {{ $farm->farm_name }}</h2>
    <a href="{{ route('farms.show', $farm) }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Cancel & Return
    </a>
</div>

<div class="card mt-3">
    <div class="card-body">
        <form method="POST" action="{{ route('farms.update', $farm) }}">
            @csrf
            @method('PUT')

            @include('farms._form', ['farm' => $farm])

            <div class="mt-4 border-top pt-3 text-end">
                <a href="{{ route('farms.show', $farm) }}" class="btn btn-outline-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Update Farm
                </button>
            </div>
        </form>
    </div>
</div>
@endsection