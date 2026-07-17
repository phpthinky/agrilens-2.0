@extends('layouts.app')
@section('title', 'New Farm')
@section('content')

<h2><i class="fas fa-tractor me-2"></i>New Farm</h2>

<div class="card mt-3">
    <div class="card-body">
        <form method="POST" action="{{ route('farms.store') }}">
            @csrf
            @include('farms._form', ['farm' => null])
        </form>
    </div>
</div>
@endsection
