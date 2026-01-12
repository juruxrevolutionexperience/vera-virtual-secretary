<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscalationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'is_active',
        'priority',
        'conditions',
        'action_type',
        'action_config',
        'max_executions_per_email',
        'cooldown_minutes',
        'times_triggered',
        'last_triggered_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'conditions' => 'array',
        'action_config' => 'array',
        'max_executions_per_email' => 'integer',
        'cooldown_minutes' => 'integer',
        'times_triggered' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ==========================================
    // MÉTODOS DE EVALUACIÓN
    // ==========================================

    /**
     * Evaluar si un email cumple con las condiciones de la regla
     */
    public function matches(Email $email): bool
    {
        // No evaluar si la regla está inactiva
        if (!$this->is_active) {
            return false;
        }

        // Verificar cooldown
        if (!$this->isReadyToTrigger()) {
            return false;
        }

        // Evaluar cada condición
        foreach ($this->conditions as $field => $value) {
            if (!$this->evaluateCondition($email, $field, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluar una condición específica
     */
    protected function evaluateCondition(Email $email, string $field, $value): bool
    {
        return match($field) {
            // Condiciones de prioridad
            'email_priority' => $email->priority === $value,
            'min_priority' => in_array($email->priority, ['high', 'urgent']) && $value === true,

            // Condiciones de categoría
            'category_id' => $email->email_category_id == $value,
            'category_in' => in_array($email->email_category_id, (array) $value),

            // Condiciones de tiempo
            'no_response_hours' => $this->checkNoResponseTime($email, $value),
            'received_hours_ago' => $email->received_at->diffInHours(now()) >= $value,
            'has_due_date' => $email->due_date !== null,
            'is_overdue' => $email->isOverdue(),

            // Condiciones de estado
            'is_read' => $email->is_read === $value,
            'is_starred' => $email->is_starred === $value,
            'requires_action' => $email->requires_action === $value,
            'workflow_status' => $email->workflow_status === $value,
            'workflow_status_in' => in_array($email->workflow_status, (array) $value),

            // Condiciones de contenido
            'has_attachments' => $email->has_attachments === $value,
            'from_domain' => str_contains($email->from_email, $value),
            'subject_contains' => str_contains(strtolower($email->subject), strtolower($value)),

            // Condiciones de Vera
            'vera_sentiment_below' => $email->vera_sentiment_score < $value,
            'vera_action_required' => $email->vera_action_required === $value,
            'vera_tags_contain' => $email->vera_tags && in_array($value, $email->vera_tags),

            default => false,
        };
    }

    /**
     * Verificar si el email no ha sido respondido en X horas
     */
    protected function checkNoResponseTime(Email $email, int $hours): bool
    {
        // Si ya fue respondido, no cumple la condición
        if ($email->replied_at) {
            return false;
        }

        // Verificar si han pasado las horas especificadas
        return $email->received_at->diffInHours(now()) >= $hours;
    }

    /**
     * Verificar si la regla está lista para ejecutarse (cooldown)
     */
    public function isReadyToTrigger(): bool
    {
        if (!$this->cooldown_minutes || !$this->last_triggered_at) {
            return true;
        }

        return $this->last_triggered_at->addMinutes($this->cooldown_minutes)->isPast();
    }

    // ==========================================
    // MÉTODOS DE EJECUCIÓN
    // ==========================================

    /**
     * Ejecutar la acción de la regla
     */
    public function execute(Email $email): bool
    {
        // Verificar si hemos alcanzado el máximo de ejecuciones para este email
        if ($this->hasReachedMaxExecutionsFor($email)) {
            return false;
        }

        // Ejecutar la acción según el tipo
        $result = match($this->action_type) {
            'create_alert' => $this->createAlert($email),
            'notify_email' => $this->sendEmailNotification($email),
            'change_priority' => $this->changePriority($email),
            'assign_to_folder' => $this->assignToFolder($email),
            'add_tag' => $this->addTag($email),
            'mark_as_urgent' => $this->markAsUrgent($email),
            default => false,
        };

        // Actualizar estadísticas
        if ($result) {
            $this->increment('times_triggered');
            $this->update(['last_triggered_at' => now()]);
            $this->recordExecution($email);
        }

        return $result;
    }

    /**
     * Crear una alerta
     */
    protected function createAlert(Email $email): bool
    {
        EmailAlert::create([
            'user_id' => $this->user_id,
            'email_id' => $email->id,
            'type' => 'escalation',
            'severity' => $this->action_config['severity'] ?? 'medium',
            'title' => $this->action_config['title'] ?? "Escalado: {$this->name}",
            'message' => $this->action_config['message'] ?? "El email cumple las condiciones de escalado: {$this->name}",
            'status' => 'pending',
            'metadata' => [
                'escalation_rule_id' => $this->id,
                'escalation_rule_name' => $this->name,
            ],
        ]);

        return true;
    }

    /**
     * Enviar notificación por email
     */
    protected function sendEmailNotification(Email $email): bool
    {
        // TODO: Implementar envío de email
        return true;
    }

    /**
     * Cambiar prioridad del email
     */
    protected function changePriority(Email $email): bool
    {
        $newPriority = $this->action_config['priority'] ?? 'high';
        $email->update(['priority' => $newPriority]);
        return true;
    }

    /**
     * Asignar a carpeta
     */
    protected function assignToFolder(Email $email): bool
    {
        $folderId = $this->action_config['folder_id'] ?? null;
        if ($folderId) {
            $email->folders()->syncWithoutDetaching([$folderId]);
            return true;
        }
        return false;
    }

    /**
     * Añadir etiqueta
     */
    protected function addTag(Email $email): bool
    {
        $tag = $this->action_config['tag'] ?? null;
        if ($tag) {
            $tags = $email->vera_tags ?? [];
            if (!in_array($tag, $tags)) {
                $tags[] = $tag;
                $email->update(['vera_tags' => $tags]);
            }
            return true;
        }
        return false;
    }

    /**
     * Marcar como urgente
     */
    protected function markAsUrgent(Email $email): bool
    {
        $email->update([
            'priority' => 'urgent',
            'requires_action' => true,
        ]);
        return true;
    }

    /**
     * Verificar si se ha alcanzado el máximo de ejecuciones para un email
     */
    protected function hasReachedMaxExecutionsFor(Email $email): bool
    {
        if (!$this->max_executions_per_email) {
            return false;
        }

        // TODO: Implementar tracking de ejecuciones por email
        // Por ahora retornamos false
        return false;
    }

    /**
     * Registrar ejecución de la regla
     */
    protected function recordExecution(Email $email): void
    {
        // TODO: Implementar log de ejecuciones
        \Log::info('Escalation rule executed', [
            'rule_id' => $this->id,
            'rule_name' => $this->name,
            'email_id' => $email->id,
            'action_type' => $this->action_type,
        ]);
    }

    // ==========================================
    // MÉTODOS DE UTILIDAD
    // ==========================================

    /**
     * Activar regla
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Desactivar regla
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Resetear estadísticas
     */
    public function resetStats(): void
    {
        $this->update([
            'times_triggered' => 0,
            'last_triggered_at' => null,
        ]);
    }
}
