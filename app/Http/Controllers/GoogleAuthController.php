<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callbackGoogle(Request $request)
    {
        try {
            // Get the user information from Google
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Check if a user with the email exists in the database
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // If a user is found, update the user with google_id if not set
                if (!$user->google_id) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }

                // Log the user in and regenerate the session
                Auth::login($user, true);
                $request->session()->regenerate();  // Ensure this line is included

                // Redirect to the dashboard
                return redirect()->route('filament.admin.pages.dashboard');
            } else {
                // Redirect to a registration page or show an error
                return redirect()->route('register')->withErrors([
                    'email' => 'Your email is not registered in the system. Please register first.',
                ]);
            }
        } catch (\Exception $e) {
            // Handle the exception and show a user-friendly error message
            return redirect()->route('login')->withErrors([
                'google' => 'There was an error with Google authentication. Please try again.',
            ]);
        }
    }
}
