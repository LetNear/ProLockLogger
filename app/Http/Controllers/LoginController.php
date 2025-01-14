<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // Prevent browser from caching the login page
        if (Auth::check()) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        return response()->view('auth.login')->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate', // HTTP 1.1
            'Pragma' => 'no-cache', // HTTP 1.0
            'Expires' => '0', // Proxies
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            // Authentication passed
            $request->session()->regenerate(); // Regenerate session ID to prevent fixation

            return redirect()->route('filament.admin.pages.dashboard');
        }

        return redirect()->back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
