<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;

class AuthController extends Controller
{
    // Login user
    public function login(LoginRequest $request)
    {
        Input::merge(array_map('trim', Input::all()));

        $user = User::where('username', $request->get('login'))->orWhere('email', $request->get('login'))->first();

        if ($user) {
            if (Hash::check($request->get('password'), $user->password)) {
                Auth::login($user);

                return redirect()->route('show.home');
            }
        }

        return redirect()->back()->withInput()->withErrors(['login' => 'Invalid username or password.']);
    }

    // Logout authenticated user
    public function logout()
    {
        Auth::logout();

        return redirect()->route('show.login');
    }

}
