@extends('layouts.app')
@section('title', 'Register Farmer')
@section('content')

<h2><i class="fas fa-user-plus me-2"></i>Register Farmer</h2>

<div class="card mt-3">
    <div class="card-body">
        <form method="POST" action="{{ route('farmers.store') }}">
            @csrf
            @include('farmers._form', ['farmer' => null])
        </form>
    </div>
</div>
@endsection
