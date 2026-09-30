@extends('layouts.app')
@section('title', 'New Farm')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-tractor me-2"></i>New Farm</h2>
    <a href="{{ route('farms.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Back to List
    </a>
</div>

<div class="card mt-3">
    <div class="card-body">
        <form method="POST" action="{{ route('farms.store') }}">
            @csrf
            @include('farms._form', ['farm' => null])
            
            <div class="mt-4 border-top pt-3 text-end">
                <a href="{{ route('farms.index') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> Save Farm
                </button>
            </div>
        </form>
    </div>
</div>
@endsection