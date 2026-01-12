<?php

namespace App\Services;

use App\Models\Email;
use App\Models\EscalationRule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EscalationService
{
    /**
     * Crear una regla de escalado
     */
    public function createRule(User $user, array $data): EscalationRule
    {
        return EscalationRule::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'priority' => $data['priority'] ?? 0,
            'conditions' => $data['conditions'],
            'action_type' => $data['action_type'],
            'action_config' => $data['action_config'] ?? [],
            'max_executions_per_email' => $data['max_executions_per_email'] ?? null,
            'cooldown_minutes' => $data['cooldown_minutes'] ?? null,
        ]);
    }

    /**
     * Actualizar regla de escalado
     */
    public function updateRule(EscalationRule $rule, array $data): EscalationRule
    {
        $rule->update($data);
        return $rule->fresh();
    }

    /**
     * Activar regla
     */
    public function activateRule(EscalationRule $rule): EscalationRule
    {
        $rule->activate();
        return $rule->fresh();
    }

    /**
     * Desactivar regla
     */
    public function deactivateRule(EscalationRule $rule): EscalationRule
    {
        $rule->deactivate();
        return $rule->fresh();
    }

    /**
     * Eliminar regla
     */
    public function deleteRule(EscalationRule $rule): bool
    {
        return $rule->delete();
    }

    /**
     * Obtener todas las reglas del usuario
     */
    public function getUserRules(User $user, bool $onlyActive = false): Collection
    {
        $query = EscalationRule::where('user_id', $user->id)->byPriority();

        if ($onlyActive) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Procesar reglas de escalado para un email específico
     */
    public function processEmailEscalation(Email $email): array
    {
        $results = [
            'rules_evaluated' => 0,
            'rules_matched' => 0,
            'actions_executed' => 0,
            'errors' => 0,
        ];

        try {
            // Obtener reglas activas del usuario ordenadas por prioridad
            $rules = $this->getUserRules($email->user, true);

            foreach ($rules as $rule) {
                $results['rules_evaluated']++;

                try {
                    // Evaluar si el email cumple las condiciones
                    if ($rule->matches($email)) {
                        $results['rules_matched']++;

                        // Ejecutar la acción
                        if ($rule->execute($email)) {
                            $results['actions_executed']++;

                            Log::info('Escalation rule executed', [
                                'rule_id' => $rule->id,
                                'rule_name' => $rule->name,
                                'email_id' => $email->id,
                                'action_type' => $rule->action_type,
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                    Log::error('Error executing escalation rule', [
                        'rule_id' => $rule->id,
                        'email_id' => $email->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in processEmailEscalation', [
                'email_id' => $email->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $results;
    }

    /**
     * Procesar reglas de escalado para todos los emails del usuario
     */
    public function processUserEscalations(User $user, ?int $limit = null): array
    {
        $stats = [
            'emails_processed' => 0,
            'rules_evaluated' => 0,
            'rules_matched' => 0,
            'actions_executed' => 0,
            'errors' => 0,
        ];

        try {
            // Obtener emails que requieren evaluación
            $query = Email::where('user_id', $user->id)
                ->where('is_archived', false)
                ->where('is_deleted', false)
                ->orderBy('priority', 'desc')
                ->orderBy('received_at', 'desc');

            if ($limit) {
                $query->limit($limit);
            }

            $emails = $query->get();

            foreach ($emails as $email) {
                try {
                    $results = $this->processEmailEscalation($email);

                    $stats['emails_processed']++;
                    $stats['rules_evaluated'] += $results['rules_evaluated'];
                    $stats['rules_matched'] += $results['rules_matched'];
                    $stats['actions_executed'] += $results['actions_executed'];
                    $stats['errors'] += $results['errors'];
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error processing email escalation', [
                        'email_id' => $email->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in processUserEscalations', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        Log::info('User escalations processed', array_merge(['user_id' => $user->id], $stats));

        return $stats;
    }

    /**
     * Obtener estadísticas de reglas de escalado
     */
    public function getRuleStats(User $user): array
    {
        $rules = EscalationRule::where('user_id', $user->id)->get();

        $stats = [
            'total_rules' => $rules->count(),
            'active_rules' => $rules->where('is_active', true)->count(),
            'inactive_rules' => $rules->where('is_active', false)->count(),
            'total_triggers' => $rules->sum('times_triggered'),
            'by_action_type' => $rules->groupBy('action_type')->map->count()->toArray(),
            'most_triggered' => $rules->sortByDesc('times_triggered')->take(5)->values()->toArray(),
        ];

        return $stats;
    }

    /**
     * Testear una regla contra un email sin ejecutar la acción
     */
    public function testRule(EscalationRule $rule, Email $email): array
    {
        $result = [
            'matches' => false,
            'conditions_evaluated' => [],
            'would_execute' => false,
            'ready_to_trigger' => $rule->isReadyToTrigger(),
        ];

        foreach ($rule->conditions as $field => $value) {
            $matches = $rule->evaluateCondition($email, $field, $value);
            $result['conditions_evaluated'][$field] = [
                'expected' => $value,
                'matches' => $matches,
            ];
        }

        $result['matches'] = $rule->matches($email);
        $result['would_execute'] = $result['matches'] && !$rule->hasReachedMaxExecutionsFor($email);

        return $result;
    }
}
