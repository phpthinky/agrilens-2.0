@extends('layouts.app')
@section('title', 'Edit Barangay')
@section('content')

<h2><i class="fas fa-city me-2"></i>Edit Barangay</h2>

<div class="card mt-3">
    <div class="card-body">
        <form method="POST" action="{{ route('barangays.update', $barangay) }}">
            @csrf
            @method('PUT')
            @include('barangays._form', ['barangay' => $barangay])
        </form>
    </div>
</div>
@endsection
