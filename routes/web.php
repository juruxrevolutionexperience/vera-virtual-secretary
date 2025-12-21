<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VirtualSecretaryController;
use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use Laravel\Cashier\Http\Controllers\WebhookController;

// Landing Page
Route::view('/', 'landing-new')->name('landing-new');

// Legal Pages (public)
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

// Newsletter routes (public)
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Dashboard Principal (requiere autenticación)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rutas de Perfil (requiere autenticación)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rutas de Facturación/Billing (requiere autenticación)
Route::middleware(['auth'])->group(function () {
    Route::view('/billing', 'billing.index')->name('billing');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});

// Webhook de Stripe (no requiere autenticación)
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// ========================================
// RUTAS DE VIRTUAL SECRETARY
// ========================================
// Todas estas rutas requieren autenticación
Route::middleware(['auth'])->prefix('virtual-secretary')->name('virtual-secretary.')->group(function () {

    // Dashboard principal de Virtual Secretary
    // URL: /virtual-secretary
    // Nombre: virtual-secretary.dashboard
    Route::get('/', [VirtualSecretaryController::class, 'dashboard'])->name('dashboard');

    // Calendario - Gestión de reuniones y citas
    // URL: /virtual-secretary/calendar
    // Nombre: virtual-secretary.calendar
    Route::get('/calendar', [VirtualSecretaryController::class, 'calendar'])->name('calendar');

    // Emails - Bandeja de entrada y gestión de correos
    // URL: /virtual-secretary/emails
    // Nombre: virtual-secretary.emails
    Route::get('/emails', [VirtualSecretaryController::class, 'emails'])->name('emails');

    // Llamadas - Registro y gestión de llamadas
    // URL: /virtual-secretary/calls
    // Nombre: virtual-secretary.calls
    Route::get('/calls', [VirtualSecretaryController::class, 'calls'])->name('calls');
});

// ========================================
// RUTAS DE CUENTAS DE EMAIL (OAuth2)
// ========================================
use App\Http\Controllers\EmailAccountController;

Route::middleware(['auth'])->prefix('email-accounts')->name('email-accounts.')->group(function () {
    // Gestión de cuentas conectadas
    Route::get('/', [EmailAccountController::class, 'index'])->name('index');
    Route::delete('/{emailAccount}', [EmailAccountController::class, 'destroy'])->name('destroy');

    // OAuth2 - Google
    Route::get('/google/redirect', [EmailAccountController::class, 'redirectToGoogle'])->name('google.redirect');
    Route::get('/google/callback', [EmailAccountController::class, 'handleGoogleCallback'])->name('google.callback');

    // OAuth2 - Microsoft
    Route::get('/microsoft/redirect', [EmailAccountController::class, 'redirectToMicrosoft'])->name('microsoft.redirect');
    Route::get('/microsoft/callback', [EmailAccountController::class, 'handleMicrosoftCallback'])->name('microsoft.callback');

    // API endpoint para obtener estado de sincronización
    Route::get('/{emailAccount}/sync-status', [
        EmailAccountController::class,
        'syncStatus'
    ])->name('sync-status');

    // API endpoint para obtener resumen de cuentas del usuario
    Route::get('/summary', [EmailAccountController::class, 'accountsSummary'])->name('summary');


    // Acciones sobre cuentas (AJAX)
    Route::post('/{emailAccount}/toggle-sync', [EmailAccountController::class, 'toggleSync'])->name('toggle-sync');
    Route::post('/{emailAccount}/sync-now', [EmailAccountController::class, 'syncNow'])->name('sync-now');
});

// ========================================
// RUTAS DEL MÓDULO EMAILS
// ========================================
use App\Http\Controllers\EmailController;

Route::middleware(['auth', 'vera.configured'])->prefix('emails')->name('emails.')->group(function () {
    // API endpoint para obtener emails sin reload (AJAX)
    Route::get('/api/list', [EmailController::class, 'apiList'])->name('api.list');
    // API endpoint para obtener un email individual (vista previa)
    Route::get('/api/{email}', [EmailController::class, 'apiShow'])->name('api.show');
    // Vista principal de emails
    Route::get('/', [EmailController::class, 'index'])->name('index');
    // Ver un email específico
    Route::get('/{email}', [EmailController::class, 'show'])->name('show');

    // Acciones sobre emails (AJAX)
    Route::post('/{email}/mark-read', [EmailController::class, 'markAsRead'])->name('mark-read');
    Route::post('/{email}/mark-unread', [EmailController::class, 'markAsUnread'])->name('mark-unread');
    Route::post('/{email}/toggle-star', [EmailController::class, 'toggleStar'])->name('toggle-star');
    Route::post('/{email}/archive', [EmailController::class, 'archive'])->name('archive');
    Route::post('/{email}/unarchive', [EmailController::class, 'unarchive'])->name('unarchive');
    Route::delete('/{email}', [EmailController::class, 'destroy'])->name('destroy');

    // Acciones masivas
    Route::post('/bulk-action', [EmailController::class, 'bulkAction'])->name('bulk-action');
});

// ========================================
// RUTAS DE VERA (AI ASSISTANT)
// ========================================
use App\Http\Controllers\VeraController;

Route::middleware(['auth'])->prefix('vera')->name('vera.')->group(function () {
    // Dashboard de Vera
    Route::get('/dashboard', [VeraController::class, 'dashboard'])->name('dashboard');

    // Onboarding de Vera (primera configuración)
    Route::get('/onboarding', [VeraController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding', [VeraController::class, 'saveOnboarding'])->name('onboarding.save');

    // Configuración de Vera
    Route::get('/config', [VeraController::class, 'config'])->name('config');
    Route::patch('/config', [VeraController::class, 'updateConfig'])->name('config.update');

    // Análisis manual
    Route::post('/analyze', [VeraController::class, 'runAnalysis'])->name('analyze');

    // Estadísticas (API)
    Route::get('/stats', [VeraController::class, 'stats'])->name('stats');

    // Check onboarding needed (API)
    Route::get('/check-onboarding', [VeraController::class, 'checkOnboardingNeeded'])->name('check-onboarding');

    // Página de respuestas de Vera
    Route::get('/responses', [VeraController::class, 'responsesIndex'])->name('responses');

    // Respuestas automáticas de Vera (API)
    Route::get('/responses/pending', [VeraController::class, 'getPendingResponses'])->name('responses.pending');
    Route::get('/responses/{response}', [VeraController::class, 'getResponse'])->name('responses.show');
    Route::post('/responses/{response}/approve', [VeraController::class, 'approveResponse'])->name('responses.approve');
    Route::post('/responses/{response}/reject', [VeraController::class, 'rejectResponse'])->name('responses.reject');
    Route::patch('/responses/{response}', [VeraController::class, 'updateResponse'])->name('responses.update');
    Route::post('/responses/{response}/send', [VeraController::class, 'sendResponse'])->name('responses.send');
});

require __DIR__ . '/auth.php';
