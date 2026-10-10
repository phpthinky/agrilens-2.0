<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Barangay *</label>
        <select name="barangay_id" class="form-select @error('barangay_id') is-invalid @enderror" required>
            <option value="">Select barangay</option>
            @foreach($barangays as $b)
                <option value="{{ $b->id }}" {{ (string) old('barangay_id', $farmer?->barangay_id) === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        @error('barangay_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">First Name *</label>
        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
               value="{{ old('first_name', $farmer?->first_name) }}" required>
        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Middle Name</label>
        <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $farmer?->middle_name) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Last Name *</label>
        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
               value="{{ old('last_name', $farmer?->last_name) }}" required>
        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">Suffix</label>
        <input type="text" name="suffix" class="form-control" maxlength="10" value="{{ old('suffix', $farmer?->suffix) }}" placeholder="Jr., Sr., III">
    </div>
    <div class="col-md-3">
        <label class="form-label">Gender *</label>
        <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
            @foreach(['Male', 'Female', 'Other'] as $g)
                <option value="{{ $g }}" {{ old('gender', $farmer?->gender) === $g ? 'selected' : '' }}>{{ $g }}</option>
            @endforeach
        </select>
        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">Birth Date</label>
        <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $farmer?->birth_date?->format('Y-m-d')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Contact Number</label>
        <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $farmer?->contact_number) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $farmer?->email) }}">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Address *</label>
        <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address', $farmer?->address) }}</textarea>
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Education Level</label>
        <select name="education_level" class="form-select">
            <option value="">Not specified</option>
            @foreach(['Elementary','Elementary Graduate','High School','High School Graduate','Vocational','College','College Graduate','Post Graduate'] as $lvl)
                <option value="{{ $lvl }}" {{ old('education_level', $farmer?->education_level) === $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   {{ old('is_active', $farmer?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save</button>
    <a href="{{ route('farmers.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
