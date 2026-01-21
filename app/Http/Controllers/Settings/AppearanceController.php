<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppearanceController extends Controller
{
    /**                                                                                                                                    
     * Mostrar la página de configuración de apariencia                                                                                    
     */
    public function index()
    {
        $currentDensity = auth()->user()->email_density ?? 'comfortable';

        return view('settings.appearance', [
            'currentDensity' => $currentDensity
        ]);
    }

    /**                                                                                                                                    
     * Actualizar la densidad visual del usuario                                                                                           
     */
    public function updateDensity(Request $request)
    {
        $request->validate([
            'density' => 'required|in:compact,comfortable,spacious'
        ]);

        $user = auth()->user();
        $user->email_density = $request->density;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Densidad actualizada correctamente',
            'density' => $request->density
        ]);
    }
}