<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function checkout(Request $request)
    {
        $user = $request->user();

        $plans = [
            'essentials'   => env('STRIPE_PRICE_ESSENTIALS'),
            'professional' => env('STRIPE_PRICE_PROFESSIONAL'),
            'business'     => env('STRIPE_PRICE_BUSINESS'),
        ];

        $plan = $request->input('plan', 'essentials');
        $priceId = $plans[$plan] ?? $plans['essentials'];

        return $user->newSubscription('default', $priceId)->checkout([
            'success_url' => route('billing').'?success=1',
            'cancel_url'  => route('billing').'?canceled=1',
        ]);
    }

    public function cancel(Request $request)
    {
        $user = $request->user();
        $user->subscription('default')->cancel();

        return redirect()->route('billing')->with('status', 'Suscripción cancelada. Tendrás acceso hasta el final del período de facturación.');
    }

    public function resume(Request $request)
    {
        $user = $request->user();
        $user->subscription('default')->resume();

        return redirect()->route('billing')->with('status', 'Suscripción reanudada exitosamente.');
    }

    public function portal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('billing'));
    }
}

