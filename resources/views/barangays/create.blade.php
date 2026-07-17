@extends('layouts.app')
@section('title', 'New Barangay')
@section('content')

<h2><i class="fas fa-city me-2"></i>New Barangay</h2>

<div class="card mt-3">
    <div class="card-body">
        <form method="POST" action="{{ route('barangays.store') }}">
            @csrf
            @include('barangays._form', ['barangay' => null])
        </form>
    </div>
</div>
@endsection
