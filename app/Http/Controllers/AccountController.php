<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Mail\RegisterEmail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;

class AccountController extends Controller
{
    /**
     * New user registration
     *
     * @param UserRequest $request
     * @return back to user home
     */
    public function register(UserRequest $request)
    {
        Input::merge(array_map('trim', Input::all()));

        // Check is email & username are not in use
        if ($this->userExists($request->get('email'))->getData()) {
            return redirect()->back()->withInput()->withErrors(['email' => 'Error: This email is already in use']);

        } else if ($this->userExists($request->get('username'))->getData()) {
            return redirect()->back()->withInput()->withErrors(['username' => 'Error: This username is already in use']);
        }

        $user = User::create(array(
            'name' => $request->get('name'),
            'username' => $request->get('username'),
            'username_last_changed' => date("Y-m-d H:i:s"),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password'))
        ));

        Mail::to($user->email)->send(new RegisterEmail);

        Auth::login($user);

        return redirect()->route('show.home');
    }

    /**
     * Checks if user already exists with email or username
     *
     * @param $data : Data to check
     * @return \Illuminate\Http\JsonResponse
     */
    public function userExists($data)
    {
        return response()->json(User::where('username', 'LIKE', '%' . $data . '%')->orWhere('email', $data)->exists());
    }

    /**
     * Update user information
     *
     * @param User $user : Existing user
     * @param UserRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(User $user, UserRequest $request)
    {
        if (Carbon::parse($user->username_last_changed)->addDays(30) > Carbon::now()) {
            // user cant change username
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
     *
     * @param User $user : Existing user
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function updatePassword(User $user)
    {
        if (request('current_password') && request('new_password')) {
            if (Hash::check(request('current_password'), $user->password)) {
                $user->update([
                    'password' => Hash::make(request('new_password'))
                ]);
                return redirect()->back()->with(['password_message' => 'Your password was successfully updated']);

            } else {
                return redirect()->back()->withInput()->withErrors(['password' => 'Your current password is incorrect']);
            }
        }

        return redirect()->back()->withErrors(['password' => 'Your current and new password are required']);
    }

}
