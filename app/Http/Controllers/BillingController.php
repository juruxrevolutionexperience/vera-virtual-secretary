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

        // Los price IDs se leen de config() (NO env() directo): con la config cacheada
        // en producción env() devuelve null y reventaba el checkout.
        $prices  = config('services.stripe.prices', []);
        $plan    = $request->input('plan', 'essentials');
        $priceId = $prices[$plan] ?? null;

        // Guard: si el plan no está configurado en Stripe, no reventamos con un 500.
        if (! $priceId) {
            return redirect()->route('billing')
                ->with('error', 'Ese plan no está disponible ahora mismo. Inténtalo en un momento o contáctanos.');
        }

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
