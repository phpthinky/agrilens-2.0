@extends('layouts.guest')
@section('title', 'Login')
@section('content')

<div class="auth-form-header">
    <h1>Welcome back</h1>
    <p>Sign in to access the soil analysis dashboard.</p>
</div>

<div class="auth-card">
    @if($errors->any())
        <div class="alert alert-danger py-2 small"><i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="fas fa-user text-muted"></i></span>
                <input type="text" class="form-control" name="username" value="{{ old('username') }}" placeholder="Enter username" required autofocus>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
                <button type="button" class="input-group-text bg-white" onclick="togglePassword()" title="Show/hide password">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-success w-100"><i class="fas fa-sign-in-alt me-2"></i>Sign In</button>
    </form>
</div>
@endsection
@section('scripts')
<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
@endsection
