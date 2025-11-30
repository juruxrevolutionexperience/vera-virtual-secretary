<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    /**
     * Suscribir un email al newsletter
     */
    public function subscribe(Request $request)
    {
        // Validar el email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ], [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'Por favor ingresa un email válido',
            'email.max' => 'El email es demasiado largo',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('email'),
            ], 422);
        }

        $email = $request->email;

        // Verificar si el email ya existe
        $existing = NewsletterSubscription::where('email', $email)->first();

        if ($existing) {
            // Si ya existe y está activo
            if ($existing->status === 'active') {
                return response()->json([
                    'success' => true,
                    'message' => 'Ya estás suscrito a nuestro newsletter',
                ], 200);
            }

            // Si existía pero se había dado de baja, reactivar
            $existing->update([
                'status' => 'active',
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Bienvenido de vuelta! Te has suscrito exitosamente',
            ], 200);
        }

        // Crear nueva suscripción
        NewsletterSubscription::create([
            'email' => $email,
            'status' => 'active',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'subscribed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Gracias por suscribirte! Te mantendremos informado',
        ], 201);
    }

    /**
     * Desuscribir un email del newsletter
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email inválido',
            ], 422);
        }

        $subscription = NewsletterSubscription::where('email', $request->email)->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Este email no está en nuestra lista',
            ], 404);
        }

        $subscription->unsubscribe();

        return response()->json([
            'success' => true,
            'message' => 'Te has dado de baja exitosamente',
        ], 200);
    }
}
