<?php

namespace App\Http\Controllers;

use App\Models\VeraNotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VeraNotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $preferences = VeraNotificationPreference::getOrCreateForUser($user);

        return view('settings.notifications', compact('preferences'));
    }

    public function data()
    {
        $user = Auth::user();
        $preferences = VeraNotificationPreference::getOrCreateForUser($user);

        return response()->json([
            'success' => true,
            'preferences' => [
                'email_enabled' => (bool) $preferences->email_enabled,
                'push_enabled' => (bool) $preferences->push_enabled,
                'daily_summary_enabled' => (bool) $preferences->daily_summary_enabled,
                'daily_summary_time' => $preferences->daily_summary_time ?? '18:00',
                'weekly_summary_enabled' => (bool) $preferences->weekly_summary_enabled,
                'weekly_summary_day' => $preferences->weekly_summary_day ?? 'monday',
                'weekly_summary_time' => $preferences->weekly_summary_time ?? '09:00',
                'monthly_summary_enabled' => (bool) $preferences->monthly_summary_enabled,
                'monthly_summary_day' => $preferences->monthly_summary_day ?? 1,
                'monthly_summary_time' => $preferences->monthly_summary_time ?? '09:00',
                'limit_warnings_enabled' => (bool) $preferences->limit_warnings_enabled,
                'warning_threshold_percentage' => $preferences->warning_threshold_percentage ?? 80,
                'high_activity_alerts' => (bool) $preferences->high_activity_alerts,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'email_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'daily_summary_enabled' => 'boolean',
            'daily_summary_time' => 'nullable|date_format:H:i',
            'weekly_summary_enabled' => 'boolean',
            'weekly_summary_day' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'weekly_summary_time' => 'nullable|date_format:H:i',
            'monthly_summary_enabled' => 'boolean',
            'monthly_summary_day' => 'nullable|integer|min:1|max:31',
            'monthly_summary_time' => 'nullable|date_format:H:i',
            'limit_warnings_enabled' => 'boolean',
            'warning_threshold_percentage' => 'nullable|integer|min:50|max:100',
            'high_activity_alerts' => 'boolean',
        ]);

        $user = Auth::user();
        $preferences = VeraNotificationPreference::getOrCreateForUser($user);
        $preferences->update($validated);

        // Si es petición AJAX, devolver JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Preferencias actualizadas correctamente'
            ]);
        }

        return back()->with('success', 'Preferencias de notificaciones actualizadas correctamente');
    }
}
