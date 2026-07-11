<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->redirectUrl(config('app.url') . '/auth/google/callback')
            ->scopes(['email', 'profile'])
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(config('app.url') . '/auth/google/callback')
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'No se pudo autenticar con Google. Inténtalo de nuevo.']);
        }

        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // Usuario existente — actualiza datos de Google y permite login
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
            ]);
        } else {
            // Usuario nuevo — requiere invitación válida
            $token = session('invitation_token');
            $invitation = $token ? Invitation::findValid($token) : null;

            if (!$invitation) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Necesitas una invitación para registrarte. Solicita acceso en jurux.app/beta.html']);
            }

            $user = User::create([
                'name'              => $googleUser->getName(),
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'password'          => bcrypt(Str::random(32)),
            ]);

            $invitation->markUsed();
            session()->forget(['invitation_token', 'invitation_email']);
        }

        Auth::login($user, true);

        return redirect()->intended(route('vera.dashboard'));
    }
}
