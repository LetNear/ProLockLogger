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

    public function callbackGoogle()
    {
        // Get the user information from Google
        $googleUser = Socialite::driver('google')->stateless()->user();

        // Check if a user with the email exists in the database
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // If a user is found, check if google_id is set, if not update the user with google_id
            if (!$user->google_id) {
                $user->google_id = $googleUser->getId();
                $user->save();
            }

            // Log the user in
            Auth::login($user);

            // Redirect to the dashboard
            return redirect()->route('filament.admin.pages.dashboard');
        } else {
            // Handle the case where the email is not found in the database
            return redirect()->route('login')->withErrors(['email' => 'Your email is not registered in the system.']);
        }
    }
}
