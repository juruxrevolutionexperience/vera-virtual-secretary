<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VirtualSecretaryController extends Controller
{
    /**
     * Muestra el dashboard principal de Virtual Secretary
     * Aquí mostraremos las métricas y estadísticas del día
     */
    public function dashboard()
    {
        // Por ahora, datos de ejemplo (mock data)
        // Más adelante, estos datos vendrán de la base de datos
        $metrics = [
            'time_saved' => 3.5, // Horas ahorradas
            'calls_managed_today' => 12,
            'emails_responded' => 45,
            'emails_classified' => 78,
            'active_meetings' => 2,
            'scheduled_meetings' => 8,
        ];

        return view('virtual-secretary.dashboard', compact('metrics'));
    }

    /**
     * Muestra el calendario con reuniones y citas
     */
    public function calendar()
    {
        // Aquí cargaremos las reuniones del usuario
        $events = []; // Por ahora vacío

        return view('virtual-secretary.calendar', compact('events'));
    }

    /**
     * Muestra la bandeja de emails
     */
    public function emails()
    {
        // Aquí cargaremos los emails sincronizados
        $emails = []; // Por ahora vacío

        // Verificar si el usuario tiene email conectado
        $hasEmailConnected = false; // Verificaremos esto más adelante

        return view('virtual-secretary.emails', compact('emails', 'hasEmailConnected'));
    }

    /**
     * Muestra el registro de llamadas
     */
    public function calls()
    {
        // Aquí cargaremos el registro de llamadas
        $calls = []; // Por ahora vacío

        return view('virtual-secretary.calls', compact('calls'));
    }
}
