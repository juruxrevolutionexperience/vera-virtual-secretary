<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VirtualSecretaryController;
use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use Laravel\Cashier\Http\Controllers\WebhookController;
use App\Http\Controllers\EmailAccountController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EmailFolderController;
use App\Http\Controllers\EmailWorkflowController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\ScheduledEmailController;
use App\Http\Controllers\AutoResponderController;
use App\Http\Controllers\VeraController;
use App\Http\Controllers\Admin\VeraAnalyticsController as AdminVeraAnalyticsController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EmailAlertController;
use App\Http\Controllers\EscalationRuleController;
use App\Http\Controllers\FollowUpReminderController;

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

Route::middleware(['auth'])->prefix('emails')->name('emails.')->group(function () {
    // API endpoint para obtener emails sin reload (AJAX)
    Route::get('/api/list', [EmailController::class, 'apiList'])->name('api.list');
    // API endpoint para obtener estadísticas de due dates y snooze (DEBE ir ANTES de api/{email})
    Route::get('/api/stats', [EmailController::class, 'apiStats'])->name('api.stats');
    // API endpoint para obtener un email individual (vista previa)
    Route::get('/api/{email}', [EmailController::class, 'apiShow'])->name('api.show');

    // Command Palette API endpoints
    Route::get('/api/command-palette/search', [EmailController::class, 'commandPaletteSearch'])->name('api.command-palette.search');
    Route::get('/api/command-palette/stats', [EmailController::class, 'commandPaletteStats'])->name('api.command-palette.stats');
    Route::post('/api/command-palette/filter', [EmailController::class, 'commandPaletteFilter'])->name('api.command-palette.filter');

    // Vistas especiales (antes de rutas dinámicas)
    Route::get('/trash', [EmailController::class, 'trash'])->name('trash');
    Route::get('/drafts', [EmailController::class, 'drafts'])->name('drafts');
    // Guardar borrador de email (AJAX)
    Route::post('/drafts/save', [EmailController::class, 'saveDraft'])->name('save-draft');

    // Vista principal de emails
    Route::get('/', [EmailController::class, 'index'])->name('index');

    Route::patch('/{email}/workflow-status', [EmailController::class, 'updateWorkflowStatus'])->name('update-workflow-status');

    // Ver un email específico
    Route::get('/{email}', [EmailController::class, 'show'])->name('show');

    // Acciones sobre emails (AJAX)
    Route::post('/{email}/mark-read', [EmailController::class, 'markAsRead'])->name('mark-read');
    Route::post('/{email}/mark-unread', [EmailController::class, 'markAsUnread'])->name('mark-unread');
    Route::post('/{email}/toggle-star', [EmailController::class, 'toggleStar'])->name('toggle-star');
    Route::post('/{email}/archive', [EmailController::class, 'archive'])->name('archive');
    Route::post('/{email}/unarchive', [EmailController::class, 'unarchive'])->name('unarchive');
    Route::post('/{email}/reply', [EmailController::class, 'sendReply'])->name('reply');
    Route::post('/send', [EmailController::class, 'sendEmail'])->name('send');
    Route::delete('/{email}', [EmailController::class, 'destroy'])->name('destroy');

    // Acciones masivas
    Route::post('/bulk-action', [EmailController::class, 'bulkAction'])->name('bulk-action');

});

// ========================================
// RUTAS DE CARPETAS DE EMAILS
// ========================================

Route::middleware(['auth'])->prefix('email-folders')->name('email-folders.')->group(function () {
    // Obtener árbol de carpetas (AJAX)
    Route::get('/tree', [EmailFolderController::class, 'tree'])->name('tree');

    // Gestión de carpetas
    Route::get('/', [EmailFolderController::class, 'index'])->name('index');
    Route::post('/', [EmailFolderController::class, 'store'])->name('store');
    Route::get('/{folder}', [EmailFolderController::class, 'show'])->name('show');
    Route::get('/{folder}/emails', [EmailFolderController::class, 'getEmailsInFolder'])->name('emails');
    Route::put('/{folder}', [EmailFolderController::class, 'update'])->name('update');
    Route::delete('/{folder}', [EmailFolderController::class, 'destroy'])->name('destroy');

    // Asignar emails a carpetas
    Route::post('/{folder}/assign-email', [EmailFolderController::class, 'assignEmail'])->name('assign-email');
    Route::post('/{folder}/bulk-assign', [EmailFolderController::class, 'bulkAssignEmails'])->name('bulk-assign');

    // Reordenar carpetas (drag & drop)
    Route::post('/reorder', [EmailFolderController::class, 'reorder'])->name('reorder');

    // Crear carpetas del sistema
    Route::post('/system/create', [EmailFolderController::class, 'createSystemFolders'])->name('system.create');
});

// ========================================
// RUTAS DE WORKFLOW DE EMAILS
// ========================================

Route::middleware(['auth'])->prefix('email-workflow')->name('email-workflow.')->group(function () {
    // Resumen de workflow
    Route::get('/summary', [EmailWorkflowController::class, 'summary'])->name('summary');

    // Cambiar estado de workflow
    Route::post('/{email}/change-status', [EmailWorkflowController::class, 'changeStatus'])->name('change-status');
    Route::post('/bulk/change-status', [EmailWorkflowController::class, 'bulkChangeStatus'])->name('bulk.change-status');

    // Acciones requeridas
    Route::post('/{email}/require-action', [EmailWorkflowController::class, 'markAsRequiringAction'])->name('require-action');
    Route::post('/{email}/complete-action', [EmailWorkflowController::class, 'completeAction'])->name('complete-action');

    // Snooze (posponer)
    Route::post('/{email}/snooze', [EmailWorkflowController::class, 'snooze'])->name('snooze');
    Route::post('/{email}/wakeup', [EmailWorkflowController::class, 'wakeup'])->name('wakeup');

    // Due dates (fechas de vencimiento)
    Route::post('/{email}/set-due-date', [EmailWorkflowController::class, 'setDueDate'])->name('set-due-date');
    Route::get('/overdue', [EmailWorkflowController::class, 'overdue'])->name('overdue');
    Route::get('/due-soon', [EmailWorkflowController::class, 'dueSoon'])->name('due-soon');

    // Papelera
    Route::post('/{email}/move-to-trash', [EmailWorkflowController::class, 'moveToTrash'])->name('move-to-trash');
    Route::post('/{email}/restore', [EmailWorkflowController::class, 'restoreFromTrash'])->name('restore');
    Route::post('/bulk/move-to-trash', [EmailWorkflowController::class, 'bulkMoveToTrash'])->name('bulk.move-to-trash');
    Route::delete('/trash/empty', [EmailWorkflowController::class, 'emptyTrash'])->name('empty-trash');
});

// ========================================
// RUTAS DE TEMPLATES DE EMAIL
// ========================================

Route::middleware(['auth'])->prefix('email-templates')->name('email-templates.')->group(function () {
    Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
    Route::get('/manage', function () {
        return view('email-templates.index');
    })->name('manage');
    Route::post('/', [EmailTemplateController::class, 'store'])->name('store');
    Route::get('/stats', [EmailTemplateController::class, 'stats'])->name('stats');
    Route::post('/{template}/render', [EmailTemplateController::class, 'render'])->name('render');
    Route::get('/{template}', [EmailTemplateController::class, 'show'])->name('show');
    Route::put('/{template}', [EmailTemplateController::class, 'update'])->name('update');
    Route::delete('/{template}', [EmailTemplateController::class, 'destroy'])->name('destroy');
    Route::post('/{template}/toggle-active', [EmailTemplateController::class, 'toggleActive'])->name('toggle-active');
    Route::post('/{template}/set-default', [EmailTemplateController::class, 'setDefault'])->name('set-default');
    Route::post('/{template}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('duplicate');
});

// ========================================
// RUTAS DE EMAILS PROGRAMADOS
// ========================================

Route::middleware(['auth'])->prefix('scheduled-emails')->name('scheduled-emails.')->group(function () {
    Route::get('/', [ScheduledEmailController::class, 'index'])->name('index');
    Route::post('/', [ScheduledEmailController::class, 'store'])->name('store');
    Route::get('/stats', [ScheduledEmailController::class, 'stats'])->name('stats');
    Route::get('/{scheduledEmail}', [ScheduledEmailController::class, 'show'])->name('show');
    Route::put('/{scheduledEmail}', [ScheduledEmailController::class, 'update'])->name('update');
    Route::post('/{scheduledEmail}/cancel', [ScheduledEmailController::class, 'cancel'])->name('cancel');
    Route::post('/{scheduledEmail}/retry', [ScheduledEmailController::class, 'retry'])->name('retry');
});

// ========================================
// RUTAS DE AUTO-RESPONDERS
// ========================================

Route::middleware(['auth'])->prefix('auto-responders')->name('auto-responders.')->group(function () {
    Route::get('/', [AutoResponderController::class, 'index'])->name('index');
    Route::post('/', [AutoResponderController::class, 'store'])->name('store');
    Route::get('/stats', [AutoResponderController::class, 'stats'])->name('stats');
    Route::get('/{autoResponder}', [AutoResponderController::class, 'show'])->name('show');
    Route::put('/{autoResponder}', [AutoResponderController::class, 'update'])->name('update');
    Route::delete('/{autoResponder}', [AutoResponderController::class, 'destroy'])->name('destroy');
    Route::post('/{autoResponder}/toggle-active', [AutoResponderController::class, 'toggleActive'])->name('toggle-active');
});

// ========================================
// RUTAS DE VERA (AI ASSISTANT)
// ========================================

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

    // Configuración de Apariencia (Densidad visual)                                                                                       
    Route::get('/appearance', [\App\Http\Controllers\Settings\AppearanceController::class, 'index'])->name('appearance');
    Route::post('/appearance/density', [
        \App\Http\Controllers\Settings\AppearanceController::class,
        'updateDensity'
    ])->name('appearance.density');
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

Route::middleware(['auth'])->prefix('admin/vera')->name('admin.vera.')->group(function () {
    // Dashboard de analytics
    Route::get('/analytics', [AdminVeraAnalyticsController::class, 'index'])->name('analytics');

    // Analytics de usuario específico
    Route::get('/analytics/user/{userId}', [AdminVeraAnalyticsController::class, 'user'])->name('analytics.user');

    // Exportar datos a CSV
    Route::get('/analytics/export', [AdminVeraAnalyticsController::class, 'export'])->name('analytics.export');
});

// ========================================
// SUB-FASE 1C: CONTACTOS, ALERTAS Y SEGUIMIENTO
// ========================================

Route::middleware(['auth'])->group(function () {
    // CONTACTOS
    Route::prefix('contacts')->name('contacts.')->group(function () {
        Route::get('/', [ContactController::class, 'index'])->name('index');
        Route::post('/', [ContactController::class, 'store'])->name('store');
        Route::get('/search', [ContactController::class, 'search'])->name('search');
        Route::get('/frequent', [ContactController::class, 'frequent'])->name('frequent');
        Route::get('/vip', [ContactController::class, 'vip'])->name('vip');
        Route::get('/stats', [ContactController::class, 'stats'])->name('stats');
        Route::post('/sync', [ContactController::class, 'syncFromEmails'])->name('sync');
        Route::get('/{contact}', [ContactController::class, 'show'])->name('show');
        Route::put('/{contact}', [ContactController::class, 'update'])->name('update');
        Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
        Route::post('/{contact}/toggle-star', [ContactController::class, 'toggleStar'])->name('toggle-star');
        Route::post('/{contact}/block', [ContactController::class, 'block'])->name('block');
        Route::post('/{contact}/unblock', [ContactController::class, 'unblock'])->name('unblock');
        Route::post('/{contact}/mark-vip', [ContactController::class, 'markAsVip'])->name('mark-vip');
    });

    // ALERTAS DE EMAILS
    Route::prefix('email-alerts')->name('email-alerts.')->group(function () {
        Route::get('/', [EmailAlertController::class, 'index'])->name('index');
        Route::post('/', [EmailAlertController::class, 'store'])->name('store');
        Route::get('/stats', [EmailAlertController::class, 'stats'])->name('stats');
        Route::post('/run-checks', [EmailAlertController::class, 'runChecks'])->name('run-checks');
        Route::get('/{alert}', [EmailAlertController::class, 'show'])->name('show');
        Route::post('/{alert}/acknowledge', [EmailAlertController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{alert}/resolve', [EmailAlertController::class, 'resolve'])->name('resolve');
        Route::post('/{alert}/dismiss', [EmailAlertController::class, 'dismiss'])->name('dismiss');
    });

    // REGLAS DE ESCALADO
    Route::prefix('escalation-rules')->name('escalation-rules.')->group(function () {
        Route::get('/', [EscalationRuleController::class, 'index'])->name('index');
        Route::post('/', [EscalationRuleController::class, 'store'])->name('store');
        Route::get('/stats', [EscalationRuleController::class, 'stats'])->name('stats');
        Route::post('/process', [EscalationRuleController::class, 'processEscalations'])->name('process');
        Route::get('/{rule}', [EscalationRuleController::class, 'show'])->name('show');
        Route::put('/{rule}', [EscalationRuleController::class, 'update'])->name('update');
        Route::delete('/{rule}', [EscalationRuleController::class, 'destroy'])->name('destroy');
        Route::post('/{rule}/activate', [EscalationRuleController::class, 'activate'])->name('activate');
        Route::post('/{rule}/deactivate', [EscalationRuleController::class, 'deactivate'])->name('deactivate');
    });

    // RECORDATORIOS DE SEGUIMIENTO
    Route::prefix('follow-up-reminders')->name('follow-up-reminders.')->group(function () {
        Route::get('/', [FollowUpReminderController::class, 'index'])->name('index');
        Route::post('/', [FollowUpReminderController::class, 'store'])->name('store');
        Route::get('/stats', [FollowUpReminderController::class, 'stats'])->name('stats');
        Route::get('/upcoming', [FollowUpReminderController::class, 'upcoming'])->name('upcoming');
        Route::get('/{reminder}', [FollowUpReminderController::class, 'show'])->name('show');
        Route::put('/{reminder}', [FollowUpReminderController::class, 'update'])->name('update');
        Route::delete('/{reminder}', [FollowUpReminderController::class, 'destroy'])->name('destroy');
        Route::post('/{reminder}/snooze', [FollowUpReminderController::class, 'snooze'])->name('snooze');
        Route::post('/{reminder}/complete', [FollowUpReminderController::class, 'complete'])->name('complete');
        Route::post('/{reminder}/cancel', [FollowUpReminderController::class, 'cancel'])->name('cancel');
    });

    // ===========================================
    // RUTAS DE ORGANIZACIONES (SUB-FASE 1D)
    // ===========================================

    // GESTIÓN DE ORGANIZACIONES
    Route::resource('organizations', \App\Http\Controllers\OrganizationController::class);

    // RUTAS ADICIONALES DE ORGANIZACIONES
    Route::prefix('organizations/{organization}')->name('organizations.')->group(function () {
        // Miembros
        Route::get('/members', [\App\Http\Controllers\OrganizationController::class, 'members'])->name('members');
        Route::delete('/members/{user}', [\App\Http\Controllers\OrganizationController::class, 'removeMember'])->name('members.remove');
        Route::put('/members/{user}/role', [\App\Http\Controllers\OrganizationController::class, 'changeMemberRole'])->name('members.change-role');

        // Salir de organización
        Route::post('/leave', [\App\Http\Controllers\OrganizationController::class, 'leave'])->name('leave');

        // Invitaciones
        Route::get('/invitations', [\App\Http\Controllers\OrganizationInvitationController::class, 'index'])->name('invitations.index');
        Route::post('/invitations', [\App\Http\Controllers\OrganizationInvitationController::class, 'store'])->name('invitations.store');
        Route::delete('/invitations/{invitation}', [\App\Http\Controllers\OrganizationInvitationController::class, 'destroy'])->name('invitations.destroy');
    });

    // ACEPTAR/RECHAZAR INVITACIONES (rutas públicas con token)
    Route::prefix('invitations')->name('invitations.')->group(function () {
        Route::get('/{token}', [\App\Http\Controllers\OrganizationInvitationController::class, 'show'])->name('show');
        Route::post('/{token}/accept', [\App\Http\Controllers\OrganizationInvitationController::class, 'accept'])->name('accept');
        Route::post('/{token}/decline', [\App\Http\Controllers\OrganizationInvitationController::class, 'decline'])->name('decline');
    });

    // ===========================================
    // RUTAS DE SEGURIDAD Y COMPLIANCE (SUB-FASE 1E)
    // ===========================================

    // AUTENTICACIÓN DE DOS FACTORES (2FA)
    Route::prefix('security/2fa')->name('security.2fa.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'index'])->name('index');
        Route::post('/generate-qr', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'generateQr'])->name('generate-qr');
        Route::post('/enable', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'enable'])->name('enable');
        Route::post('/disable', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'disable'])->name('disable');
        Route::post('/regenerate-codes', [\App\Http\Controllers\Security\TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->name('regenerate-codes');
    });

    // GESTIÓN DE SESIONES
    Route::prefix('security/sessions')->name('security.sessions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Security\SessionManagementController::class, 'index'])->name('index');
        Route::delete('/{session}', [\App\Http\Controllers\Security\SessionManagementController::class, 'revoke'])->name('revoke');
        Route::post('/revoke-all', [\App\Http\Controllers\Security\SessionManagementController::class, 'revokeAll'])->name('revoke-all');
        Route::post('/{session}/trust', [\App\Http\Controllers\Security\SessionManagementController::class, 'trust'])->name('trust');
    });

    // RGPD / PRIVACIDAD
    Route::prefix('gdpr')->name('gdpr.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Security\GDPRController::class, 'index'])->name('index');
        Route::post('/export', [\App\Http\Controllers\Security\GDPRController::class, 'requestExport'])->name('export');
        Route::post('/delete', [\App\Http\Controllers\Security\GDPRController::class, 'requestDeletion'])->name('delete');
    });

    // DASHBOARD DE SEGURIDAD
    Route::get('/security/dashboard', [\App\Http\Controllers\Security\SecurityDashboardController::class, 'index'])
        ->name('security.dashboard');
});

// DESCARGA DE EXPORTACIÓN RGPD (sin auth, usa token)
Route::get('/privacy/download/{token}', [\App\Http\Controllers\Security\GDPRController::class, 'downloadExport'])
    ->name('privacy.download');

require __DIR__ . '/auth.php';
