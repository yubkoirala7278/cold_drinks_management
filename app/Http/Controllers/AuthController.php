<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Redirect based on role
            switch ($user->role) {
                case 'admin':
                    return redirect('/warehouse');
                case 'inbound_staff':
                    return redirect('/warehouse/inbound');
                case 'outbound_staff':
                    return redirect('/warehouse/outbound');
                default:
                    Auth::logout();
                    return back();
            }
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Role-based redirection
            switch ($user->role) {
                case 'admin':
                    return redirect()->intended('/warehouse');
                case 'inbound_staff':
                    return redirect()->intended('/warehouse/inbound');
                case 'outbound_staff':
                    return redirect()->intended('/warehouse/outbound');
                default:
                    Auth::logout();
                    return back()->with('error', 'Unauthorized role');
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
