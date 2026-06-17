<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VirtualSecretaryController;
use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use Laravel\Cashier\Http\Controllers\WebhookController;
use App\Http\Controllers\VeraController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CancellationController;

// Google OAuth login
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Invitaciones beta
Route::get('/invite/{token}', [InvitationController::class, 'accept'])->name('invite.accept');

// Web de reservas y cancelación (públicas, enlace tokenizado que envía Vera).
// throttle:30,1 = máx 30 peticiones/min por IP → anti-abuso/flood (los slots tocan
// Google Calendar, que cuesta) sin estorbar a un cliente real navegando la agenda.
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/reservar/{token}',        [BookingController::class, 'show'])->name('booking.show');
    Route::get('/reservar/{token}/slots',  [BookingController::class, 'slots'])->name('booking.slots');
    Route::post('/reservar/{token}',       [BookingController::class, 'store'])->name('booking.store');

    Route::get('/cancelar/{token}',  [CancellationController::class, 'show'])->name('cancel.show');
    Route::post('/cancelar/{token}', [CancellationController::class, 'cancel'])->name('cancel.cancel');
});

// Beta cerrada: redirige la raíz a login. Descomentar Route::view cuando abramos al público.
// Route::view('/', 'landing-new')->name('landing-new');
Route::redirect('/', '/login', 301)->name('landing-new');

// Legal Pages (public)
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

// Newsletter routes (public)
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// 2FA Challenge (rutas públicas — usuario no logueado aún)
Route::get('/2fa/challenge', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'showChallenge'])->name('2fa.challenge');
Route::post('/2fa/challenge', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'verifyChallenge'])->name('2fa.verify');

// Dashboard Principal (requiere autenticación)
Route::get('/dashboard', function () {
    $user = auth()->user();
    $meetingsThisMonth = \App\Models\Meeting::forUser($user->id)
        ->whereMonth('scheduled_at', now()->month)->confirmed()->count();
    $meetingsThisWeek = \App\Models\Meeting::forUser($user->id)
        ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])->confirmed()->count();
    $recentActivity = \App\Models\VeraActivityLog::where('user_id', $user->id)
        ->latest()->limit(5)->get();
    $upcomingMeetings = \App\Models\Meeting::forUser($user->id)
        ->upcoming()->confirmed()->limit(4)->get();
    $pendingMeetings = \App\Models\Meeting::forUser($user->id)
        ->where('status', 'pending')->upcoming()->get();
    // Incidencias/quejas escaladas y aún no gestionadas por el dueño.
    $escalations = \App\Models\MeetingRequest::forUser($user->id)
        ->where('state', 'escalated')
        ->whereNull('context->handled_at')
        ->with('contact')
        ->latest()->get();
    return view('dashboard', compact('meetingsThisMonth', 'meetingsThisWeek', 'recentActivity', 'upcomingMeetings', 'pendingMeetings', 'escalations'));
})->middleware(['auth', 'verified', 'subscribed'])->name('dashboard');

// Confirmación / rechazo de reservas pendientes + gestión de incidencias
Route::middleware(['auth', 'verified', 'subscribed'])->group(function () {
    Route::post('/meetings/{meeting}/confirm', [\App\Http\Controllers\MeetingConfirmController::class, 'confirm'])->name('meetings.confirm');
    Route::post('/meetings/{meeting}/reject',  [\App\Http\Controllers\MeetingConfirmController::class, 'reject'])->name('meetings.reject');
    Route::post('/incidencias/{meetingRequest}/gestionada', [\App\Http\Controllers\MeetingConfirmController::class, 'resolveEscalation'])->name('incidencias.gestionada');
});

// Rutas de Perfil (requiere autenticación)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rutas de Facturación/Billing (auth sin 'subscribed' — necesario para poder pagar)
Route::middleware(['auth'])->group(function () {
    Route::get('/billing', [BillingController::class, 'index'])->name('billing');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    Route::get('/billing/manage', [BillingController::class, 'manage'])->name('billing.manage');
});

// Webhook de Stripe (no requiere autenticación)
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// ========================================
// WEBHOOKS DE CANALES — Sprint 3
// ========================================
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\TelegramWebhookController;

// WhatsApp Business API (Meta) — sin auth, sin CSRF
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])->name('webhooks.whatsapp');

// Telegram Bot API — el {secret} en la URL actúa como token de verificación
Route::post('/webhooks/telegram/{secret}', [TelegramWebhookController::class, 'handle'])->name('webhooks.telegram');

// ========================================
// RUTAS DEV (solo usuarios con is_dev = true)
// ========================================
use App\Http\Controllers\DevController;

Route::middleware(['auth', 'dev'])->prefix('dev')->name('dev.')->group(function () {
    Route::get('/',               [DevController::class, 'index'])->name('index');
    Route::post('/switch-plan',   [DevController::class, 'switchPlan'])->name('switch-plan');
    Route::post('/reset-onboarding', [DevController::class, 'resetOnboarding'])->name('reset-onboarding');

    // Token analytics movida aquí
    Route::get('/tokens', [\App\Http\Controllers\VeraTokensController::class, 'index'])->name('tokens');

    // Simulador de mensajes entrantes
    Route::post('/simulate-message', [DevController::class, 'simulateMessage'])->name('simulate-message');
});

// ========================================
// RUTAS DE VIRTUAL SECRETARY
// ========================================
Route::middleware(['auth'])->prefix('virtual-secretary')->name('virtual-secretary.')->group(function () {
    Route::get('/', [VirtualSecretaryController::class, 'dashboard'])->name('dashboard');
    Route::get('/calendar', [VirtualSecretaryController::class, 'calendar'])->name('calendar');
    Route::get('/calls', [VirtualSecretaryController::class, 'calls'])->name('calls');
});

// ========================================
// GOOGLE CALENDAR OAuth (Sprint 2)
// ========================================
use App\Http\Controllers\GoogleCalendarAuthController;

Route::middleware(['auth'])->prefix('google')->name('google.')->group(function () {
    Route::get('/calendar/connect', [GoogleCalendarAuthController::class, 'redirect'])->name('calendar.connect');
    Route::get('/calendar/callback', [GoogleCalendarAuthController::class, 'callback'])->name('calendar.callback');
    Route::post('/calendar/disconnect', [GoogleCalendarAuthController::class, 'disconnect'])->name('calendar.disconnect');
});

// ========================================
// RUTAS DE VERA (AI ASSISTANT)
// ========================================

Route::middleware(['auth', 'subscribed'])->prefix('vera')->name('vera.')->group(function () {
    // Dashboard de Vera
    Route::get('/dashboard', [VeraController::class, 'dashboard'])->name('dashboard');

    // Onboarding de Vera (primera configuración)
    Route::get('/onboarding', [VeraController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding', [VeraController::class, 'saveOnboarding'])->name('onboarding.save');

    // Configuración de Vera
    Route::get('/config', [VeraController::class, 'config'])->name('config');
    Route::patch('/config', [VeraController::class, 'updateConfig'])->name('config.update');

    // Estadísticas (API)
    Route::get('/stats', [VeraController::class, 'stats'])->name('stats');

    // Check onboarding needed (API)
    Route::get('/check-onboarding', [VeraController::class, 'checkOnboardingNeeded'])->name('check-onboarding');

    // Historial de tokens IA
    Route::get('/tokens', [\App\Http\Controllers\VeraTokensController::class, 'index'])->name('tokens');

    // Perfil del usuario para el RAG
    Route::post('/user-profile', [VeraController::class, 'saveUserProfile'])->name('user-profile.save');
});

// ========================================
// RUTAS DE SETTINGS (USAGE & PLANS)
// ========================================
use App\Http\Controllers\VeraUsageController;
use App\Http\Controllers\VeraNotificationController;

Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {
    // Página de uso
    Route::get('/usage', [VeraUsageController::class, 'index'])->name('usage');
    Route::get('/usage/current', [VeraUsageController::class, 'current'])->name('usage.current');

    // Página de planes
    Route::get('/plans', [VeraUsageController::class, 'plans'])->name('plans');
    Route::post('/plans/update', [VeraUsageController::class, 'updatePlan'])->name('plans.update');

    // Configuración de Notificaciones
    Route::get('/notifications', [VeraNotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/data', [VeraNotificationController::class, 'data'])->name('notifications.data');
    Route::patch('/notifications', [VeraNotificationController::class, 'update'])->name('notifications.update');

    // Configuración de Apariencia
    Route::get('/appearance', [\App\Http\Controllers\Settings\AppearanceController::class, 'index'])->name('appearance');

    // DATOS DE SEGURIDAD (AJAX para modal de ajustes)
    Route::get('/security/data', [\App\Http\Controllers\Security\PasswordPolicyController::class, 'securityData'])->name('security.data');
    Route::post('/security/password', [\App\Http\Controllers\Security\PasswordPolicyController::class, 'changePassword'])->name('security.password');
    Route::post('/security/export', [\App\Http\Controllers\Security\GDPRController::class, 'requestExport'])->name('security.export');
    Route::post('/security/delete-data', [\App\Http\Controllers\Security\GDPRController::class, 'requestDeletion'])->name('security.delete');
    Route::delete('/security/session/{session}', [\App\Http\Controllers\Security\SessionManagementController::class, 'revoke'])->name('security.session.revoke');
    Route::post('/security/sessions/revoke-all', [\App\Http\Controllers\Security\SessionManagementController::class, 'revokeAll'])->name('security.sessions.revoke-all');
});

// ========================================
// RUTAS DE VERA - REGISTRO DE ACTIVIDAD
// ========================================
use App\Http\Controllers\VeraActivityController;

Route::middleware(['auth'])->prefix('vera')->name('vera.')->group(function () {
    Route::get('/activity', [VeraActivityController::class, 'index'])->name('activity.index');
    Route::get('/activity/{id}', [VeraActivityController::class, 'show'])->name('activity.show');
    Route::patch('/activity/{id}/feedback', [VeraActivityController::class, 'updateFeedback'])->name('activity.feedback');
    Route::get('/activity/stats', [VeraActivityController::class, 'stats'])->name('activity.stats');
    Route::get('/activity/export', [VeraActivityController::class, 'export'])->name('activity.export');
});

// ========================================
// RUTAS DE VERA - CALENDAR
// ========================================
use App\Http\Controllers\VeraCalendarController;

Route::middleware(['auth'])->prefix('vera')->name('vera.')->group(function () {
    Route::get('/calendar',                   [VeraCalendarController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/events',           [VeraCalendarController::class, 'store'])->name('calendar.store');
    Route::patch('/calendar/events/{id}',     [VeraCalendarController::class, 'update'])->name('calendar.update');
    Route::delete('/calendar/events/{id}',    [VeraCalendarController::class, 'destroy'])->name('calendar.destroy');
});

// ========================================
// RUTAS DE VERA - CANALES (Telegram, WhatsApp…)
// ========================================
use App\Http\Controllers\VeraChannelsController;

Route::middleware(['auth'])->prefix('vera/channels')->name('vera.channels.')->group(function () {
    Route::get('/telegram',                  [VeraChannelsController::class, 'telegramIndex'])->name('telegram');
    Route::post('/telegram/save-token',      [VeraChannelsController::class, 'saveTelegramToken'])->name('telegram.save-token');
    Route::post('/telegram/register-webhook',[VeraChannelsController::class, 'registerWebhook'])->name('telegram.register-webhook');
    Route::delete('/telegram',               [VeraChannelsController::class, 'disconnectTelegram'])->name('telegram.disconnect');

    Route::get('/whatsapp',          [VeraChannelsController::class, 'whatsappIndex'])->name('whatsapp');
    Route::post('/whatsapp/save',    [VeraChannelsController::class, 'saveWhatsApp'])->name('whatsapp.save');
    Route::delete('/whatsapp',       [VeraChannelsController::class, 'disconnectWhatsApp'])->name('whatsapp.disconnect');
});

// ========================================
// RUTAS DE VERA - MÉTRICAS Y ANALYTICS
// ========================================
use App\Http\Controllers\VeraAnalyticsController;

Route::middleware(['auth'])->prefix('vera')->name('vera.')->group(function () {
    Route::get('/analytics', [VeraAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/api/trend', [VeraAnalyticsController::class, 'apiActivityTrend'])->name('analytics.api.trend');
    Route::get('/analytics/api/distribution', [VeraAnalyticsController::class, 'apiDistribution'])->name('analytics.api.distribution');
    Route::get('/analytics/api/comparison', [VeraAnalyticsController::class, 'apiMonthComparison'])->name('analytics.api.comparison');
    Route::get('/analytics/api/cost-by-day', [VeraAnalyticsController::class, 'apiCostByDayOfWeek'])->name('analytics.api.cost-by-day');
    Route::get('/analytics/api/prediction', [VeraAnalyticsController::class, 'apiPrediction'])->name('analytics.api.prediction');
    Route::get('/analytics/api/feedback', [VeraAnalyticsController::class, 'apiFeedbackAnalysis'])->name('analytics.api.feedback');
    Route::get('/analytics/api/summary', [VeraAnalyticsController::class, 'apiExecutiveSummary'])->name('analytics.api.summary');
});

// ========================================
// RUTAS DE VERA - CHAT INTERACTIVO
// ========================================
use App\Http\Controllers\VeraChatController;

Route::middleware(['auth'])->prefix('vera')->name('vera.')->group(function () {
    Route::get('/chat', [VeraChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/message', [VeraChatController::class, 'sendMessage'])->name('chat.send')->middleware('throttle:30,1');
    Route::get('/chat/conversation/{conversationId}/messages', [VeraChatController::class, 'getMessages'])->name('chat.messages');
    Route::post('/chat/conversation', [VeraChatController::class, 'createConversation'])->name('chat.create');
    Route::post('/chat/conversation/{conversationId}/archive', [VeraChatController::class, 'archiveConversation'])->name('chat.archive');
    Route::get('/chat/conversations', [VeraChatController::class, 'getConversations'])->name('chat.conversations');
    Route::post('/chat/message/{messageId}/feedback', [VeraChatController::class, 'messageFeedback'])->name('chat.feedback');
    Route::get('/chat/context', [VeraChatController::class, 'getContext'])->name('chat.context');
});

// ========================================
// CONTACTOS
// ========================================

Route::middleware(['auth'])->group(function () {
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::post('/', [ContactController::class, 'store'])->name('store');
        Route::get('/search', [ContactController::class, 'search'])->name('search');
        Route::get('/frequent', [ContactController::class, 'frequent'])->name('frequent');
        Route::get('/vip', [ContactController::class, 'vip'])->name('vip');
        Route::get('/stats', [ContactController::class, 'stats'])->name('stats');
        Route::get('/{contact}', [ContactController::class, 'show'])->name('show');
        Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
        Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
        Route::post('/{contact}/toggle-star', [ContactController::class, 'toggleStar'])->name('toggle-star');
        Route::post('/{contact}/block', [ContactController::class, 'block'])->name('block');
        Route::post('/{contact}/unblock', [ContactController::class, 'unblock'])->name('unblock');
        Route::post('/{contact}/mark-vip', [ContactController::class, 'markAsVip'])->name('mark-vip');
    });

    // ===========================================
    // ORGANIZACIONES (SOLO PLAN ENTERPRISE)
    // ===========================================

    Route::middleware('enterprise.access')->group(function () {
        Route::resource('organizations', \App\Http\Controllers\OrganizationController::class);

        Route::prefix('organizations/{organization}')->name('organizations.')->group(function () {
            Route::get('/members', [\App\Http\Controllers\OrganizationController::class, 'members'])->name('members');
            Route::delete('/members/{user}', [\App\Http\Controllers\OrganizationController::class, 'removeMember'])->name('members.remove');
            Route::put('/members/{user}/role', [\App\Http\Controllers\OrganizationController::class, 'changeMemberRole'])->name('members.change-role');
            Route::post('/leave', [\App\Http\Controllers\OrganizationController::class, 'leave'])->name('leave');
            Route::get('/invitations', [\App\Http\Controllers\OrganizationInvitationController::class, 'index'])->name('invitations.index');
            Route::post('/invitations', [\App\Http\Controllers\OrganizationInvitationController::class, 'store'])->name('invitations.store');
            Route::delete('/invitations/{invitation}', [\App\Http\Controllers\OrganizationInvitationController::class, 'destroy'])->name('invitations.destroy');
        });

        Route::prefix('invitations')->name('invitations.')->group(function () {
            Route::get('/{token}', [\App\Http\Controllers\OrganizationInvitationController::class, 'show'])->name('show');
            Route::post('/{token}/accept', [\App\Http\Controllers\OrganizationInvitationController::class, 'accept'])->name('accept');
            Route::post('/{token}/decline', [\App\Http\Controllers\OrganizationInvitationController::class, 'decline'])->name('decline');
        });
    });

    // ===========================================
    // SEGURIDAD Y COMPLIANCE
    // ===========================================

    Route::prefix('security/2fa')->name('security.2fa.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'index'])->name('index');
        Route::post('/generate-qr', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'generateQr'])->name('generate-qr');
        Route::post('/enable', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'enable'])->name('enable');
        Route::post('/disable', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'disable'])->name('disable');
        Route::post('/regenerate-codes', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->name('regenerate-codes');
    });

    Route::prefix('security/sessions')->name('security.sessions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Security\SessionManagementController::class, 'index'])->name('index');
        Route::delete('/{session}', [\App\Http\Controllers\Security\SessionManagementController::class, 'revoke'])->name('revoke');
        Route::post('/revoke-all', [\App\Http\Controllers\Security\SessionManagementController::class, 'revokeAll'])->name('revoke-all');
        Route::post('/{session}/trust', [\App\Http\Controllers\Security\SessionManagementController::class, 'trust'])->name('trust');
    });

    Route::prefix('security/password-policy')->name('security.password-policy.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Security\PasswordPolicyController::class, 'index'])->name('index');
        Route::post('/change', [\App\Http\Controllers\Security\PasswordPolicyController::class, 'changePassword'])->name('change');
        Route::post('/update-policy', [\App\Http\Controllers\Security\PasswordPolicyController::class, 'updatePolicy'])->name('update');
    });

    Route::prefix('gdpr')->name('gdpr.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Security\GDPRController::class, 'index'])->name('index');
        Route::post('/export', [\App\Http\Controllers\Security\GDPRController::class, 'requestExport'])->name('export');
        Route::post('/delete', [\App\Http\Controllers\Security\GDPRController::class, 'requestDeletion'])->name('delete');
        Route::delete('/deletion/{deletion}/cancel', [\App\Http\Controllers\Security\GDPRController::class, 'cancelDeletion'])->name('deletion.cancel');
    });

    Route::prefix('security/audit')->name('security.audit.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Security\AuditLogController::class, 'index'])->name('index');
        Route::get('/{auditLog}', [\App\Http\Controllers\Security\AuditLogController::class, 'show'])->name('show');
        Route::get('/export/csv', [\App\Http\Controllers\Security\AuditLogController::class, 'export'])->name('export');
    });

    Route::get('/security/dashboard', [\App\Http\Controllers\Security\SecurityDashboardController::class, 'index'])
        ->name('security.dashboard');
});

// DESCARGA DE EXPORTACIÓN RGPD (sin auth, usa token)
Route::get('/privacy/download/{token}', [\App\Http\Controllers\Security\GDPRController::class, 'downloadExport'])
    ->name('privacy.download');

// ========================================
// FEEDBACK (Beta — "Reportar a Vera")
// ========================================

Route::middleware(['auth'])->group(function () {
    Route::post('/feedback', [\App\Http\Controllers\VeraFeedbackController::class, 'store'])
        ->name('feedback.store');
});

Route::middleware(['auth', 'super.admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/feedbacks', [\App\Http\Controllers\Admin\VeraFeedbackController::class, 'index'])
        ->name('feedbacks.index');
    Route::get('/feedbacks/{feedback}', [\App\Http\Controllers\Admin\VeraFeedbackController::class, 'show'])
        ->name('feedbacks.show');
    Route::patch('/feedbacks/{feedback}', [\App\Http\Controllers\Admin\VeraFeedbackController::class, 'update'])
        ->name('feedbacks.update');
});
