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

Route::middleware(['auth'])->prefix('emails')->name('emails.')->group(function () {
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

    // Filtros de respuesta automática (NUEVO)
    Route::get('/filters', [VeraController::class, 'getResponseFilters'])->name('filters.get');
    Route::patch('/filters', [VeraController::class, 'updateResponseFilters'])->name('filters.update');
    Route::post('/filters/pattern/add', [VeraController::class, 'addFilterPattern'])->name('filters.pattern.add');
    Route::delete('/filters/pattern/remove', [VeraController::class, 'removeFilterPattern'])->name('filters.pattern.remove');
    Route::post('/filters/category/toggle', [VeraController::class, 'toggleCategory'])->name('filters.category.toggle');

    // Sugerencias inteligentes para filtros (NUEVO)
    Route::get('/suggestions/contacts', [VeraController::class, 'getContactsSuggestions'])->name('suggestions.contacts');
    Route::get('/suggestions/keywords', [VeraController::class, 'getKeywordsSuggestions'])->name('suggestions.keywords');

    // Análisis manual (REQUIERE VERIFICACIÓN DE LÍMITE)
    Route::post('/analyze', [VeraController::class, 'runAnalysis'])
        ->middleware('vera.limit')
        ->name('analyze');

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

// ========================================
// RUTAS DE SETTINGS (USAGE & PLANS)
// ========================================
use App\Http\Controllers\VeraUsageController;
use App\Http\Controllers\VeraNotificationController;

Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {
    // Página de uso - Vista de consumo de emails
    Route::get('/usage', [VeraUsageController::class, 'index'])->name('usage');

    // API para obtener uso actual (AJAX)
    Route::get('/usage/current', [VeraUsageController::class, 'current'])->name('usage.current');

    // Página de planes - Comparación y upgrade
    Route::get('/plans', [VeraUsageController::class, 'plans'])->name('plans');

    // Actualizar plan del usuario
    Route::post('/plans/update', [VeraUsageController::class, 'updatePlan'])->name('plans.update');

    // Configuración de Notificaciones (FASE 4)
    Route::get('/notifications', [VeraNotificationController::class, 'index'])->name('notifications');
    Route::get('/notifications/data', [VeraNotificationController::class, 'data'])->name('notifications.data');
    Route::patch('/notifications', [VeraNotificationController::class, 'update'])->name('notifications.update');
});

// ========================================
// RUTAS DE VERA - REGISTRO DE ACTIVIDAD
// ========================================
use App\Http\Controllers\VeraActivityController;

Route::middleware(['auth'])->prefix('vera')->name('vera.')->group(function () {
    // Registro de actividad principal
    Route::get('/activity', [VeraActivityController::class, 'index'])->name('activity.index');

    // Ver detalles de una actividad específica
    Route::get('/activity/{id}', [VeraActivityController::class, 'show'])->name('activity.show');

    // Actualizar feedback de una actividad
    Route::patch('/activity/{id}/feedback', [VeraActivityController::class, 'updateFeedback'])->name('activity.feedback');

    // API: Obtener estadísticas (para gráficos)
    Route::get('/activity/stats', [VeraActivityController::class, 'stats'])->name('activity.stats');

    // Exportar actividades a CSV
    Route::get('/activity/export', [VeraActivityController::class, 'export'])->name('activity.export');
});

// ========================================
// RUTAS DE VERA - MÉTRICAS Y ANALYTICS (FASE 2)
// ========================================
use App\Http\Controllers\VeraAnalyticsController;

Route::middleware(['auth'])->prefix('vera')->name('vera.')->group(function () {
    // Dashboard principal de analytics
    Route::get('/analytics', [VeraAnalyticsController::class, 'index'])->name('analytics.index');

    // API: Tendencia de actividad
    Route::get('/analytics/api/trend', [VeraAnalyticsController::class, 'apiActivityTrend'])->name('analytics.api.trend');

    // API: Distribución por tipo
    Route::get('/analytics/api/distribution', [VeraAnalyticsController::class, 'apiDistribution'])->name('analytics.api.distribution');

    // API: Comparativa mes a mes
    Route::get('/analytics/api/comparison', [VeraAnalyticsController::class, 'apiMonthComparison'])->name('analytics.api.comparison');

    // API: Costos por día de la semana
    Route::get('/analytics/api/cost-by-day', [VeraAnalyticsController::class, 'apiCostByDayOfWeek'])->name('analytics.api.cost-by-day');

    // API: Top categorías de emails
    Route::get('/analytics/api/top-categories', [VeraAnalyticsController::class, 'apiTopCategories'])->name('analytics.api.top-categories');

    // API: Predicción de consumo mensual
    Route::get('/analytics/api/prediction', [VeraAnalyticsController::class, 'apiPrediction'])->name('analytics.api.prediction');

    // API: Análisis de feedback
    Route::get('/analytics/api/feedback', [VeraAnalyticsController::class, 'apiFeedbackAnalysis'])->name('analytics.api.feedback');

    // API: Resumen ejecutivo completo
    Route::get('/analytics/api/summary', [VeraAnalyticsController::class, 'apiExecutiveSummary'])->name('analytics.api.summary');
});

// ========================================
// RUTAS DE VERA - CHAT INTERACTIVO (FASE 3)
// ========================================
use App\Http\Controllers\VeraChatController;

Route::middleware(['auth'])->prefix('vera')->name('vera.')->group(function () {
    // Vista principal de chat
    Route::get('/chat', [VeraChatController::class, 'index'])->name('chat.index');

    // API: Enviar mensaje
    Route::post('/chat/message', [VeraChatController::class, 'sendMessage'])->name('chat.send');

    // API: Obtener mensajes de conversación
    Route::get('/chat/conversation/{conversationId}/messages', [VeraChatController::class, 'getMessages'])->name('chat.messages');

    // API: Crear nueva conversación
    Route::post('/chat/conversation', [VeraChatController::class, 'createConversation'])->name('chat.create');

    // API: Archivar conversación
    Route::post('/chat/conversation/{conversationId}/archive', [VeraChatController::class, 'archiveConversation'])->name('chat.archive');

    // API: Obtener todas las conversaciones
    Route::get('/chat/conversations', [VeraChatController::class, 'getConversations'])->name('chat.conversations');

    // API: Dar feedback a un mensaje
    Route::post('/chat/message/{messageId}/feedback', [VeraChatController::class, 'messageFeedback'])->name('chat.feedback');

    // API: Obtener contexto actual
    Route::get('/chat/context', [VeraChatController::class, 'getContext'])->name('chat.context');
});

// ========================================
// RUTAS DE ADMIN - VERA ANALYTICS
// ========================================
use App\Http\Controllers\Admin\VeraAnalyticsController as AdminVeraAnalyticsController;

Route::middleware(['auth'])->prefix('admin/vera')->name('admin.vera.')->group(function () {
    // Dashboard de analytics
    Route::get('/analytics', [AdminVeraAnalyticsController::class, 'index'])->name('analytics');

    // Analytics de usuario específico
    Route::get('/analytics/user/{userId}', [AdminVeraAnalyticsController::class, 'user'])->name('analytics.user');

    // Exportar datos a CSV
    Route::get('/analytics/export', [AdminVeraAnalyticsController::class, 'export'])->name('analytics.export');
});

require __DIR__ . '/auth.php';
