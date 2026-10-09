<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        // Technician Management: farmer accounts aren't relevant here.
        $users    = User::where('user_type', '!=', 'farmer')->latest()->get();
        $editUser = null;
        $assignedBarangayIds = [];
        $barangays = Barangay::active()->orderBy('name')->get();

        if (request('edit_id')) {
            $editUser = User::findOrFail(request('edit_id'));
            $assignedBarangayIds = $editUser->assignedBarangays()->pluck('barangays.id')->all();
        }

        return view('admin.users', compact('users', 'editUser', 'barangays', 'assignedBarangayIds'));
    }

    public function show(User $user)
    {
        $user->load('assignedBarangays');
        $assignments = $user->testAssignments()
            ->with(['farm.farmer', 'farm.locationBarangay', 'soilSample'])
            ->latest('scheduled_date')
            ->get();

        $farms = Farm::active()->with(['farmer', 'locationBarangay'])->orderBy('farm_name')->get();

        return view('admin.users.show', compact('user', 'assignments', 'farms'));
    }

    public function updatePhoto(Request $request, User $user)
    {
        $request->validate([
            'photo' => 'required|image|max:4096',
        ]);

        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }

        $path = $request->file('photo')->store('technician-photos', 'public');
        $user->update(['photo_path' => $path]);

        return redirect()->route('admin.users.show', $user)->with('success', 'Profile photo updated.');
    }

    private function nameRules(): array
    {
        return [
            'first_name'  => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name'   => 'required|string|max:100',
            'suffix'      => 'nullable|string|max:20',
            'address'     => 'nullable|string|max:255',
        ];
    }

    public function store(Request $request)
    {
        $request->validate(array_merge([
            'username'  => 'required|string|max:50|unique:users',
            'email'     => 'required|email|max:100|unique:users',
            'password'  => 'required|string|min:6',
            'user_type' => 'required|in:farmer,technician,admin',
        ], $this->nameRules()));

        User::create([
            'username'    => $request->username,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'user_type'   => $request->user_type,
            'first_name'  => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name'   => $request->last_name,
            'suffix'      => $request->suffix,
            'address'     => $request->address,
        ]);

        return redirect()->route('admin.users')->with('success', 'Technician added successfully!');
    }

    public function update(Request $request, User $user)
    {
        $request->validate(array_merge([
            'username'  => 'required|string|max:50|unique:users,username,' . $user->id,
            'email'     => 'required|email|max:100|unique:users,email,' . $user->id,
            'user_type' => 'required|in:farmer,technician,admin',
            'password'  => 'nullable|string|min:6',
        ], $this->nameRules()));

        $data = [
            'username'    => $request->username,
            'email'       => $request->email,
            'user_type'   => $request->user_type,
            'first_name'  => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name'   => $request->last_name,
            'suffix'      => $request->suffix,
            'address'     => $request->address,
        ];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->assignedBarangays()->sync($request->input('barangay_ids', []));

        return redirect()->route('admin.users')->with('success', 'Technician updated successfully!');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'Technician deleted successfully!');
    }
}
