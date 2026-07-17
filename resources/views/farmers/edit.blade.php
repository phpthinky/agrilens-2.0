@extends('layouts.app')
@section('title', 'Edit Farmer')
@section('content')

<h2><i class="fas fa-user-edit me-2"></i>Edit Farmer — {{ $farmer->full_name }}</h2>

<div class="card mt-3">
    <div class="card-body">
        <form method="POST" action="{{ route('farmers.update', $farmer) }}">
            @csrf
            @method('PUT')
            @include('farmers._form', ['farmer' => $farmer])
        </form>
    </div>
</div>
@endsection
