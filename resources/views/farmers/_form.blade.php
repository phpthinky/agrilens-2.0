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
        <label class="form-label">ID Type</label>
        <input type="text" name="id_type" class="form-control" value="{{ old('id_type', $farmer?->id_type) }}" placeholder="Driver's License, National ID...">
    </div>
    <div class="col-md-6">
        <label class="form-label">ID Number</label>
        <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $farmer?->id_number) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Farmer Type *</label>
        <select name="farmer_type" class="form-select" required>
            @foreach(['Owner', 'Tenant', 'Caretaker', 'Other'] as $t)
                <option value="{{ $t }}" {{ old('farmer_type', $farmer?->farmer_type ?? 'Owner') === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Years Farming</label>
        <input type="number" name="years_farming" class="form-control" min="0" max="99" value="{{ old('years_farming', $farmer?->years_farming) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Total Farm Area (ha)</label>
        <input type="number" step="0.01" name="total_farm_area" class="form-control" value="{{ old('total_farm_area', $farmer?->total_farm_area) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Crops Grown</label>
        <input type="text" name="crops_grown" class="form-control" value="{{ old('crops_grown', $farmer?->crops_grown) }}" placeholder="Comma-separated, e.g. Rice, Corn">
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
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $farmer?->notes) }}</textarea>
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
