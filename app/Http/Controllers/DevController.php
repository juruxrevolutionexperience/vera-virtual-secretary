<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VeraConfig;

class DevController extends Controller
{
    /**
     * Panel principal del desarrollador.
     */
    public function index()
    {
        $user   = Auth::user();
        $config = VeraConfig::where('user_id', $user->id)->first();

        return view('dev.index', compact('user', 'config'));
    }

    /**
     * Cambiar el plan del usuario sin pasar por Stripe.
     * Solo disponible en modo dev.
     */
    public function switchPlan(Request $request)
    {
        $request->validate([
            'plan' => ['required', 'in:basic,pro,team'],
        ]);

        $planLimits = [
            'basic' => ['monthly_meeting_limit' => 30,      'monthly_tokens_limit' => 50000],
            'pro'   => ['monthly_meeting_limit' => 999999,  'monthly_tokens_limit' => 1000000],
            'team'  => ['monthly_meeting_limit' => 999999,  'monthly_tokens_limit' => 5000000],
        ];

        $plan = $request->plan;

        Auth::user()->update([
            'plan_type'             => $plan,
            'is_paid'               => true,
            'monthly_meeting_limit' => $planLimits[$plan]['monthly_meeting_limit'],
            'monthly_tokens_limit'  => $planLimits[$plan]['monthly_tokens_limit'],
        ]);

        return back()->with('dev_success', "Plan cambiado a {$plan}");
    }

    /**
     * Simular un mensaje entrante (WhatsApp/Telegram/Email) para pruebas.
     */
    public function simulateMessage(Request $request)
    {
        $request->validate([
            'channel' => ['required', 'in:whatsapp,telegram,email'],
            'message' => ['required', 'string', 'max:1000'],
            'sender'  => ['nullable', 'string', 'max:100'],
        ]);

        $user    = Auth::user();
        $channel = $request->channel;
        $message = $request->message;
        $sender  = $request->sender ?: 'dev-simulator';

        // Disparar el Job de procesamiento como si llegara un mensaje real
        \App\Jobs\ProcessIncomingMessage::dispatch($user->id, $channel, $message, $sender);

        return back()->with('dev_success', "Mensaje simulado enviado por {$channel} desde \"{$sender}\".");
    }

    /**
     * Resetear el onboarding del usuario.
     * Borra los campos del Cerebro de Vera para forzar el wizard desde cero.
     */
    public function resetOnboarding()
    {
        $config = VeraConfig::where('user_id', Auth::id())->first();

        if ($config) {
            $config->update([
                'business_name'            => null,
                'owner_name'               => null,
                'tone'                     => 'profesional',
                'working_hours'            => null,
                'meeting_duration'         => 45,
                'buffer_minutes'           => 15,
                'unknown_response'         => null,
                'escalation_triggers'      => null,
                'vera_signature'           => null,
                'onboarding_completed'     => false,
                'onboarding_completed_at'  => null,
            ]);
        }

        return redirect()->route('dashboard')->with('dev_success', 'Onboarding reseteado.');
    }
}
