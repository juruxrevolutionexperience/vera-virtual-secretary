<?php

namespace App\Services;

use App\Models\Email;
use App\Models\VeraRule;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * VeraRulesEngine - Motor de reglas automáticas
 *
 * Aplica reglas configuradas por el usuario a los emails
 */
class VeraRulesEngine
{
    protected VeraActivityService $activityService;

    public function __construct()
    {
        $this->activityService = new VeraActivityService();
    }

    /**
     * Aplicar todas las reglas habilitadas de un usuario a un email
     *
     * @param Email $email
     * @return array Reglas aplicadas
     */
    public function applyRules(Email $email): array
    {
        $user = $email->user;

        if (!$user) {
            return [];
        }

        // Obtener reglas habilitadas ordenadas por prioridad
        $rules = VeraRule::where('user_id', $user->id)
            ->enabled()
            ->ordered()
            ->get();

        if ($rules->isEmpty()) {
            return [];
        }

        $appliedRules = [];

        foreach ($rules as $rule) {
            if ($rule->matches($email)) {
                $this->executeRule($rule, $email);
                $appliedRules[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'action' => $rule->action,
                ];

                Log::info('🎯 Regla aplicada', [
                    'email_id' => $email->id,
                    'rule' => $rule->name,
                    'action' => $rule->rule_type,
                ]);
            }
        }

        return $appliedRules;
    }

    /**
     * Ejecutar la acción de una regla específica
     *
     * @param VeraRule $rule
     * @param Email $email
     * @return void
     */
    protected function executeRule(VeraRule $rule, Email $email): void
    {
        match($rule->rule_type) {
            'auto_archive' => $this->applyAutoArchive($email, $rule),
            'auto_categorize' => $this->applyAutoCategorize($email, $rule),
            'auto_prioritize' => $this->applyAutoPrioritize($email, $rule),
            'auto_respond' => $this->applyAutoRespond($email, $rule),
            'auto_forward' => $this->applyAutoForward($email, $rule),
            default => null,
        };

        // Incrementar contador
        $rule->incrementApplied();
    }

    /**
     * Archivar automáticamente
     */
    protected function applyAutoArchive(Email $email, VeraRule $rule): void
    {
        $email->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        // Registrar actividad de archivado
        $this->activityService->logEmailArchived($email, "Regla automática: {$rule->name}");

        Log::info('📦 Email archivado automáticamente', [
            'email_id' => $email->id,
            'rule' => $rule->name,
        ]);
    }

    /**
     * Categorizar automáticamente
     */
    protected function applyAutoCategorize(Email $email, VeraRule $rule): void
    {
        $category = $rule->action['category'] ?? null;

        if ($category) {
            $email->update(['category' => $category]);

            // Registrar actividad de categorización
            $this->activityService->logEmailCategorized($email, $category, 1.0);

            Log::info('📁 Email categorizado automáticamente', [
                'email_id' => $email->id,
                'category' => $category,
                'rule' => $rule->name,
            ]);
        }
    }

    /**
     * Priorizar automáticamente
     */
    protected function applyAutoPrioritize(Email $email, VeraRule $rule): void
    {
        $priority = $rule->action['priority'] ?? 'high';

        $email->update([
            'priority' => $priority,
        ]);

        Log::info('⭐ Email priorizado automáticamente', [
            'email_id' => $email->id,
            'priority' => $priority,
            'rule' => $rule->name,
        ]);
    }

    /**
     * Responder automáticamente (placeholder)
     */
    protected function applyAutoRespond(Email $email, VeraRule $rule): void
    {
        // TODO: Implementar en Fase 3
        Log::info('📤 Auto-respuesta pendiente de implementar', [
            'email_id' => $email->id,
            'rule' => $rule->name,
        ]);
    }

    /**
     * Reenviar automáticamente (placeholder)
     */
    protected function applyAutoForward(Email $email, VeraRule $rule): void
    {
        // TODO: Implementar si es necesario
        Log::info('➡️  Auto-reenvío pendiente de implementar', [
            'email_id' => $email->id,
            'rule' => $rule->name,
        ]);
    }

    /**
     * Crear regla desde insights
     *
     * @param User $user
     * @param string $name
     * @param string $type
     * @param array $conditions
     * @param array $action
     * @return VeraRule
     */
    public function createRule(
        User $user,
        string $name,
        string $type,
        array $conditions,
        array $action,
        ?string $description = null,
        int $priority = 0
    ): VeraRule {
        return VeraRule::create([
            'user_id' => $user->id,
            'name' => $name,
            'description' => $description,
            'rule_type' => $type,
            'conditions' => $conditions,
            'action' => $action,
            'priority' => $priority,
            'enabled' => true,
        ]);
    }
}
