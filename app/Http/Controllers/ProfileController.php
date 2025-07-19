<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Show the user's profile.
     */
    public function show()
    {
        $user = auth()->user();
        $user->load('groups', 'collectionItems');
        
        return view('profile.show', compact('user'));
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'preferred_language' => 'required|string|in:nl,en,de,fr,es',
            'notifications_enabled' => 'boolean',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user->update($request->only([
            'first_name',
            'last_name',
            'email',
            'preferred_language',
            'notifications_enabled',
            'email_notifications',
            'push_notifications',
        ]));

        return back()->with('success', 'Profiel succesvol bijgewerkt!');
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $user = auth()->user();

        // Check current password
        if (!Hash::check($request->current_password, $user->password_hash)) {
            return back()->withErrors([
                'current_password' => 'Huidig wachtwoord is incorrect.',
            ]);
        }

        // Update password
        $user->update([
            'password_hash' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Wachtwoord succesvol gewijzigd!');
    }
} 