<?php

namespace App\Http\Controllers;

use App\Models\EmailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Inertia\Inertia;

class EmailAccountController extends Controller
{
    /**
     * Mostrar lista de cuentas conectadas
     */
    public function index()
    {
        $accounts = EmailAccount::where('user_id', Auth::id())
            ->with([
                'syncLogs' => function ($query) {
                    $query->latest()->take(3);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'provider' => $account->provider,
                    'provider_name' => $account->provider_name,
                    'email_address' => $account->email_address,
                    'display_name' => $account->display_name,
                    'sync_enabled' => $account->sync_enabled,
                    'sync_status' => $account->sync_status,
                    'last_sync_at' => $account->last_sync_at?->toISOString(),
                    'sync_logs' => $account->syncLogs->map(function ($log) {
                        return [
                            'sync_type' => ucfirst($log->sync_type),
                            'status' => $log->status,
                            'started_at' => $log->started_at->toISOString(),
                            'emails_created' => $log->emails_created ?? 0,
                            'emails_updated' => $log->emails_updated ?? 0,
                            'emails_failed' => $log->emails_failed ?? 0,
                        ];
                    }),
                ];
            });

        return Inertia::render('EmailAccounts/Index', [
            'accounts' => $accounts,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Redirigir a Google OAuth
     */
    public function redirectToGoogle()
    {
        // Guardar el user_id en la sesión antes de redirigir
        session(['oauth_user_id' => Auth::id()]);

        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/gmail.send',
                'https://www.googleapis.com/auth/gmail.modify',
                'https://www.googleapis.com/auth/gmail.labels',
            ])
            ->with(['access_type' => 'offline', 'prompt' => 'consent', 'state' => encrypt(Auth::id())])
            ->redirect();
    }

    /**
     * Callback de Google OAuth
     */
    public function handleGoogleCallback()
    {
        try {
            // Verificar autenticación
            if (!Auth::check()) {
                // Si no está autenticado, guardar la URL de callback y redirigir al login
                session(['url.intended' => url()->current() . '?' . http_build_query(request()->query())]);
                return redirect()->route('login')->with('info', 'Por favor, inicia sesión para conectar tu cuenta de Gmail.');
            }

            \Log::info('Starting Google OAuth callback');
            \Log::info('Request params', ['code' => request()->has('code'), 'state' => request()->has('state')]);

            $googleUser = Socialite::driver('google')->stateless()->user();
            \Log::info('Google user retrieved', ['email' => $googleUser->getEmail()]);

            // Verificar si ya existe una cuenta con este email
            $existingAccount = EmailAccount::where('email_address', $googleUser->getEmail())->first();

            if ($existingAccount) {
                \Log::info('Account already exists');
                return redirect()->route('email-accounts.index')
                    ->with('error', 'Esta cuenta de Gmail ya está conectada.');
            }

            \Log::info('Creating new email account', [
                'user_id' => Auth::id(),
                'email' => $googleUser->getEmail(),
                'has_token' => !empty($googleUser->token),
                'has_refresh' => !empty($googleUser->refreshToken),
            ]);

            // Crear nueva cuenta
            $account = EmailAccount::create([
                'user_id' => Auth::id(),
                'provider' => 'gmail',
                'email_address' => $googleUser->getEmail(),
                'display_name' => $googleUser->getName(),
                'access_token' => $googleUser->token,
                'refresh_token' => $googleUser->refreshToken,
                'token_expires_at' => now()->addSeconds($googleUser->expiresIn ?? 3600),
                'provider_user_id' => $googleUser->getId(),
                'sync_enabled' => true,
                'sync_status' => 'active',
            ]);

            \Log::info('Account created successfully', ['account_id' => $account->id]);

            // Lanzar sincronización inicial
            \App\Jobs\InitialSyncEmailsJob::dispatch($account);

            \Log::info('Initial sync job dispatched', ['account_id' => $account->id]);

            // Limpiar sesión de OAuth
            session()->forget('oauth_user_id');

            // Redirigir a la página de emails donde verá el loading
            return redirect()->route('emails.index')
                ->with('success', 'Cuenta conectada. Sincronizando emails...')
                ->with('syncing_account_id', $account->id);

        } catch (\Exception $e) {
            \Log::error('Google OAuth Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('email-accounts.index')
                ->with('error', 'Error al conectar con Gmail: ' . $e->getMessage());
        }
    }

    /**
     * Redirigir a Microsoft OAuth
     */
    public function redirectToMicrosoft()
    {
        return Socialite::driver('microsoft')
            ->scopes([
                'Mail.Read',
                'Mail.Send',
                'Mail.ReadWrite',
                'offline_access',
            ])
            ->redirect();
    }

    /**
     * Callback de Microsoft OAuth
     */
    public function handleMicrosoftCallback()
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();

            // Verificar si ya existe
            $existingAccount = EmailAccount::where('email_address', $microsoftUser->getEmail())->first();

            if ($existingAccount) {
                return redirect()->route('email-accounts.index')
                    ->with('error', 'Esta cuenta de Outlook ya está conectada.');
            }

            // Crear nueva cuenta
            EmailAccount::create([
                'user_id' => Auth::id(),
                'provider' => 'outlook',
                'email_address' => $microsoftUser->getEmail(),
                'display_name' => $microsoftUser->getName(),
                'access_token' => $microsoftUser->token,
                'refresh_token' => $microsoftUser->refreshToken,
                'token_expires_at' => now()->addSeconds($microsoftUser->expiresIn ?? 3600),
                'provider_user_id' => $microsoftUser->getId(),
                'sync_enabled' => true,
                'sync_status' => 'active',
            ]);

            return redirect()->route('email-accounts.index')
                ->with('success', 'Cuenta de Outlook conectada exitosamente. La sincronización comenzará en breve.');

        } catch (\Exception $e) {
            return redirect()->route('email-accounts.index')
                ->with('error', 'Error al conectar con Outlook: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar cuenta conectada
     */
    public function destroy(EmailAccount $emailAccount)
    {
        // Verificar que pertenece al usuario
        if ($emailAccount->user_id !== Auth::id()) {
            abort(403);
        }

        // Eliminar todos los emails asociados a esta cuenta
        \App\Models\Email::where('email_account_id', $emailAccount->id)->delete();

        // Eliminar la cuenta
        $emailAccount->delete();

        return redirect()->route('email-accounts.index')
            ->with('success', 'Cuenta desconectada exitosamente y todos sus emails han sido eliminados.');
    }

    /**
     * Activar/Desactivar sincronización
     */
    public function toggleSync(EmailAccount $emailAccount)
    {
        if ($emailAccount->user_id !== Auth::id()) {
            abort(403);
        }

        $emailAccount->update([
            'sync_enabled' => !$emailAccount->sync_enabled,
            'sync_status' => !$emailAccount->sync_enabled ? 'paused' : 'active',
        ]);

        $status = $emailAccount->sync_enabled ? 'activada' : 'pausada';

        return response()->json([
            'success' => true,
            'message' => "Sincronización {$status}",
            'sync_enabled' => $emailAccount->sync_enabled,
        ]);
    }

    /**
     * Forzar sincronización manual
     */
    public function syncNow(EmailAccount $emailAccount)
    {
        if ($emailAccount->user_id !== Auth::id()) {
            abort(403);
        }

        // Dispatch job de sincronización
        \App\Jobs\SyncEmailAccount::dispatch($emailAccount);

        return response()->json([
            'success' => true,
            'message' => 'Sincronización iniciada. Esto puede tomar unos minutos.',
        ]);
    }

    /**
     * Obtener estado de sincronización de una cuenta (para polling desde frontend)
     */
    public function syncStatus(EmailAccount $emailAccount)
    {
        // Verificar que pertenece al usuario
        if ($emailAccount->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'account' => [
                'id' => $emailAccount->id,
                'email_address' => $emailAccount->email_address,
                'provider' => $emailAccount->provider,
            ],
            'sync' => $emailAccount->getSyncProgressData(),
            'emails_count' => \App\Models\Email::where('email_account_id', $emailAccount->id)->count(),
        ]);
    }

    /**
     * Obtener resumen de todas las cuentas del usuario
     */
    public function accountsSummary()
    {
        $accounts = EmailAccount::where('user_id', Auth::id())->get();

        $summary = [
            'total_accounts' => $accounts->count(),
            'has_accounts' => $accounts->count() > 0,
            'syncing_accounts' => [],
            'active_accounts' => [],
            'any_syncing' => false,
        ];

        foreach ($accounts as $account) {
            if ($account->isSyncing()) {
                $summary['syncing_accounts'][] = [
                    'id' => $account->id,
                    'email_address' => $account->email_address,
                    'sync_data' => $account->getSyncProgressData(),
                ];
                $summary['any_syncing'] = true;
            } else {
                $summary['active_accounts'][] = [
                    'id' => $account->id,
                    'email_address' => $account->email_address,
                    'has_completed_initial_sync' => $account->hasCompletedInitialSync(),
                ];
            }
        }

        return response()->json($summary);
    }
}
