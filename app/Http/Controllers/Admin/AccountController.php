<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * The superadmin's own account — view/update profile and change password.
 * Always operates on the currently authenticated superadmin.
 */
class AccountController extends Controller
{
    public function edit()
    {
        return view('admin.account', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username'   => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'phone'      => 'nullable|string|max:30',
            'image'      => 'nullable|image|max:2048',
        ]);

        $user->first_name = $data['first_name'];
        $user->last_name  = $data['last_name'] ?? '';
        $user->email      = $data['email'];
        $user->username   = $data['username'] ?: $data['email'];
        $user->phone      = $data['phone'] ?? null;

        if ($request->hasFile('image')) {
            if ($user->image && str_starts_with($user->image, 'uploads/') && is_file(public_path($user->image))) {
                @unlink(public_path($user->image));
            }
            $file = $request->file('image');
            $name = 'admin_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $dir  = public_path('uploads/admins');
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file->move($dir, $name);
            $user->image = 'uploads/admins/' . $name;
        }

        $user->save();

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('status', 'Password changed successfully.');
    }
}
