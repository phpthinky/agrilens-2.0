@php $assignedIds = $assignedTechnicianIds ?? []; @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Barangay Name *</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $barangay?->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Code *</label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $barangay?->code) }}" maxlength="10" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $barangay?->description) }}</textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label">Area (hectares)</label>
        <input type="number" step="0.01" name="area_hectares" class="form-control"
               value="{{ old('area_hectares', $barangay?->area_hectares) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Population</label>
        <input type="number" name="population" class="form-control"
               value="{{ old('population', $barangay?->population) }}">
    </div>
    <div class="col-12">
        <label class="form-label">Assigned Technicians</label>
        <select name="technician_ids[]" class="form-select" multiple size="5">
            @foreach($technicians as $tech)
                <option value="{{ $tech->id }}" {{ in_array($tech->id, $assignedIds) ? 'selected' : '' }}>
                    {{ $tech->username }}
                </option>
            @endforeach
        </select>
        <div class="form-text">Hold Ctrl/Cmd to select multiple technicians.</div>
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1"
                   {{ old('is_active', $barangay?->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save</button>
    <a href="{{ route('barangays.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
