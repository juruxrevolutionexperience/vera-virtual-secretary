<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Si ya tiene suscripción activa → al dashboard de Vera
        if ($user->subscribed('default')) {
            return redirect()->route('vera.dashboard');
        }

        return view('billing.plans');
    }

    public function manage(Request $request)
    {
        return view('billing.manage');
    }

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
            'success_url' => route('vera.dashboard') . '?welcome=1',
            'cancel_url'  => route('billing'),
        ]);
    }

    public function cancel(Request $request)
    {
        $request->user()->subscription('default')->cancel();

        return redirect()->route('billing.manage')->with('status', 'Suscripción cancelada. Tendrás acceso hasta el final del período.');
    }

    public function resume(Request $request)
    {
        $request->user()->subscription('default')->resume();

        return redirect()->route('billing.manage')->with('status', 'Suscripción reanudada.');
    }

    public function portal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('billing.manage'));
    }
}
