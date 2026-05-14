<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (!session('invitation_token')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Necesitas una invitación para registrarte. Solicita acceso en jurux.app/beta.html']);
        }

        return view('auth.register', [
            'invitation_email' => session('invitation_email'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $token = session('invitation_token');

        if (!$token || !$invitation = Invitation::findValid($token)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Invitación no válida o expirada.']);
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $userData = [
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ];

        if ($invitation->plan_slug) {
            $userData['plan_type'] = $invitation->plan_slug;
        }

        $user = User::create($userData);

        $invitation->markUsed();
        session()->forget(['invitation_token', 'invitation_email']);

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
