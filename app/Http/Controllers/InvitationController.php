<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function accept(string $token)
    {
        $invitation = Invitation::findValid($token);

        if (!$invitation) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Este enlace de invitación no es válido o ha expirado.']);
        }

        session(['invitation_token' => $token, 'invitation_email' => $invitation->email]);

        return redirect()->route('register');
    }
}
