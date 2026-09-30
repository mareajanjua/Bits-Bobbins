<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function show()
    {
        $admin = $this->admin();

        return view('admin.account', compact('admin'));
    }

    public function updateProfile(Request $request)
    {
        $admin = $this->admin();

        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:150'],
            'shop_name' => ['nullable', 'string', 'max:150'],
            'shop_address' => ['nullable', 'string', 'max:500'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'notify_returns' => ['nullable'],
            'notify_failed_payments' => ['nullable'],
            'notify_low_stock' => ['nullable'],
        ]);

        if ($request->hasFile('profile_photo')) {
            $directory = public_path('assets/dashboard/uploads/admin');
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            $filename = 'admin-' . $admin->admin_id . '.' . $request->file('profile_photo')->getClientOriginalExtension();
            $request->file('profile_photo')->move($directory, $filename);
            $data['profile_photo'] = 'assets/dashboard/uploads/admin/' . $filename;
        }

        $data['notify_returns'] = $request->boolean('notify_returns');
        $data['notify_failed_payments'] = $request->boolean('notify_failed_payments');
        $data['notify_low_stock'] = $request->boolean('notify_low_stock');

        $admin->update($data);

        return back()->with('status', 'Account profile updated.');
    }

    public function updatePassword(Request $request)
    {
        $admin = $this->admin();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
        ]);

        if (! Hash::check($data['current_password'], $admin->password_hash)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $admin->update(['password_hash' => Hash::make($data['password'])]);

        return back()->with('status', 'Password updated.');
    }

    private function admin(): Admin
    {
        return Admin::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@email.com',
                'password_hash' => Hash::make('admin123'),
                'shop_name' => 'Bits&Bobbins',
                'shop_address' => '',
            ]
        );
    }
}
