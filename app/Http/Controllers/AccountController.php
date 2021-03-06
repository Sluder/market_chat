<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;
use App\Http\Requests\UserRequest;
use App\Mail\RegisterEmail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AccountController extends Controller
{
    /**
     * New user registration
     */
    public function register(UserRequest $request)
    {
        // Check if email & username are not in use
        if ($this->userExists($request->get('email'))->getData()) {
            return redirect()->back()->withInput()->withErrors(['email' => 'This email is already in use']);

        } else if ($this->userExists($request->get('username'))->getData()) {
            return redirect()->back()->withInput()->withErrors(['username' => 'This username is already in use']);
        }

        $user = User::create([
            'name' => $request->get('name'),
            'username' => $request->get('username'),
            'username_last_changed' => date("Y-m-d H:i:s"),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password'))
        ]);

        Mail::to($user->email)->send(new RegisterEmail);

        Auth::login($user);

        return redirect()->route('show.home');
    }

    /**
     * Update user information
     */
    public function updateProfile(UserRequest $request)
    {
        $user = Auth::user();

        // Check if user tried changing their username inside the block period (30 days)
        if ($request->get('username') !== $user->username && Carbon::parse($user->username_last_changed)->addDays(30) >= Carbon::now()) {
            return redirect()->back()->withInput()->withErrors(['username' => 'You cant change your username just yet']);
        }

        $user->update([
            'name' => $request->get('name'),
            'username' => $request->get('username'),
            'username_last_changed' => $user->username === $request->get('username') ? $user->username_last_changed : date("Y-m-d H:i:s"),
            'email' => $request->get('email'),
            'bio' => $request->get('bio'),
            'website' => $request->get('website')
        ]);

        return redirect()->back()->with(['profile_message' => 'Your profile was successfully updated']);
    }

    /**
     * Update user password
     */
    public function updatePassword(PasswordRequest $request)
    {
        $user = Auth::user();

        if (Hash::check($request->get('current_password'), $user->password)) {
            $user->update([
                'password' => Hash::make($request->get('new_password'))
            ]);
            return redirect()->back()->with(['password_message' => 'Your password was successfully updated']);
        }

        return redirect()->back()->withInput()->withErrors(['password' => 'Your current password is incorrect']);
    }

    /**
     * Checks if user already exists with email or username
     */
    public function userExists($data)
    {
        return response()->json(User::where('username', 'LIKE', '%' . $data . '%')->orWhere('email', $data)->exists());
    }

}
