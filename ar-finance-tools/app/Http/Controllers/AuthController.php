<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        // Simple auth for now - will be replaced with proper Laravel auth
        if (($email === 'admin' || $email === 'admin@dmx.co.id') && $password === 'admin') {
            session(['logged_in' => true, 'user' => ['name' => 'Admin', 'email' => $email]]);
            return redirect('/dashboard');
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => 'Email atau password salah']);
    }

    public function logout()
    {
        session()->flush();
        return redirect('/login');
    }
}
