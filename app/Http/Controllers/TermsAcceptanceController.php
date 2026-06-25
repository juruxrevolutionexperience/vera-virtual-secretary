<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureTermsAccepted;
use App\Models\UserConsent;
use Illuminate\Http\Request;

class TermsAcceptanceController extends Controller
{
    public function show()
    {
        return view('terms.accept', [
            'version' => EnsureTermsAccepted::CURRENT_VERSION,
        ]);
    }

    public function accept(Request $request)
    {
        $request->validate([
            'accept_terms' => ['required', 'accepted'],
        ], [
            'accept_terms.required' => 'Debes aceptar los Términos y Condiciones para continuar.',
            'accept_terms.accepted' => 'Debes aceptar los Términos y Condiciones para continuar.',
        ]);

        UserConsent::updateOrCreate(
            [
                'user_id'          => $request->user()->id,
                'consent_type'     => 'terms_of_service',
                'consent_version'  => EnsureTermsAccepted::CURRENT_VERSION,
            ],
            [
                'is_granted'  => true,
                'granted_at'  => now(),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'revoked_at'  => null,
            ]
        );

        return redirect()->intended(route('dashboard'));
    }
}
