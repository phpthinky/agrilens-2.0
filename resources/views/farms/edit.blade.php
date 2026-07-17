@extends('layouts.app')
@section('title', 'Edit Farm')
@section('content')

<h2><i class="fas fa-tractor me-2"></i>Edit Farm — {{ $farm->farm_name }}</h2>

<div class="card mt-3">
    <div class="card-body">
        <form method="POST" action="{{ route('farms.update', $farm) }}">
            @csrf
            @method('PUT')
            @include('farms._form', ['farm' => $farm, 'selectedFarmer' => null])
        </form>
    </div>
</div>
@endsection
