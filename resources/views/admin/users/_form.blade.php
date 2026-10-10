@php $assignedIds = $assignedBarangayIds ?? []; @endphp

<div class="mb-3">
    <label class="form-label">Username</label>
    <input type="text" class="form-control" name="username" value="{{ old('username', $user->username ?? '') }}" required>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">First Name *</label>
        <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}" required>
        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Middle Name</label>
        <input type="text" class="form-control" name="middle_name" value="{{ old('middle_name', $user->middle_name ?? '') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-8 mb-3">
        <label class="form-label">Last Name *</label>
        <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name', $user->last_name ?? '') }}" required>
        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Ext. (Jr., III)</label>
        <input type="text" class="form-control" name="suffix" value="{{ old('suffix', $user->suffix ?? '') }}">
    </div>
</div>
<div class="mb-3">
    <label class="form-label">Address <small class="text-muted">(optional)</small></label>
    <input type="text" class="form-control" name="address" value="{{ old('address', $user->address ?? '') }}">
</div>
<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control" name="email" value="{{ old('email', $user->email ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">User Type</label>
    <select class="form-control" name="user_type" required>
        @foreach(['technician','admin'] as $type)
        <option value="{{ $type }}" {{ old('user_type', $user->user_type ?? 'technician') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Password {{ isset($user) ? '(leave blank to keep current)' : '*' }}</label>
    <input type="password" class="form-control" name="password" {{ !isset($user) ? 'required' : '' }} minlength="6">
</div>
<div class="mb-3">
    <label class="form-label">Assigned Barangays <small class="text-muted">(territory — ignored for admins)</small></label>
    <select name="barangay_ids[]" class="form-select" multiple size="5">
        @foreach($barangays as $b)
            <option value="{{ $b->id }}" {{ in_array($b->id, old('barangay_ids', $assignedIds)) ? 'selected' : '' }}>{{ $b->name }}</option>
        @endforeach
    </select>
    <div class="form-text">Hold Ctrl/Cmd to select multiple. Determines which farmers/farms this user can see.</div>
</div>
<div class="d-grid gap-2">
    <button type="submit" class="btn {{ isset($user) ? 'btn-primary' : 'btn-success' }}">
        {{ isset($user) ? 'Update Technician' : 'Add Technician' }}
    </button>
    <a href="{{ route('admin.users') }}" class="btn btn-secondary">Cancel</a>
</div>
