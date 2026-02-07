<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class EmailTemplateService
{
    /**
     * Crear un nuevo template
     */
    public function createTemplate(User $user, array $data): EmailTemplate
    {
        // Determinar organization_id si se comparte con organización
        $organizationId = null;
        if (($data['is_organization_shared'] ?? false) && $user->organizations()->wherePivot('is_active', true)->exists()) {
            $organizationId = $user->organizations()->wherePivot('is_active', true)->first()->id;
        }

        $template = EmailTemplate::create([
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'reply',
            'placeholders' => $data['placeholders'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $data['is_default'] ?? false,
            'category' => $data['category'] ?? null,
        ]);

        // Si se marca como default, actualizar los demás
        if ($template->is_default) {
            $template->setAsDefault();
        }

        Log::info('📝 Template creado', [
            'template_id' => $template->id,
            'name' => $template->name,
            'type' => $template->type,
            'user_id' => $user->id,
        ]);

        return $template;
    }

    /**
     * Actualizar un template
     */
    public function updateTemplate(EmailTemplate $template, array $data): EmailTemplate
    {
        $wasDefault = $template->is_default;

        $template->update($data);

        // Si cambió a default, actualizar los demás
        if (!$wasDefault && $template->is_default) {
            $template->setAsDefault();
        }

        Log::info('✏️ Template actualizado', [
            'template_id' => $template->id,
            'name' => $template->name,
        ]);

        return $template->fresh();
    }

    /**
     * Eliminar un template
     */
    public function deleteTemplate(EmailTemplate $template): void
    {
        $templateId = $template->id;
        $templateName = $template->name;

        $template->delete();

        Log::info('🗑️ Template eliminado', [
            'template_id' => $templateId,
            'name' => $templateName,
        ]);
    }

    /**
     * Obtener templates del usuario (propios + compartidos de organización)
     */
    public function getUserTemplates(User $user, ?string $type = null, bool $onlyActive = false): Collection
    {
        // Incluir templates propios + templates compartidos de la organización
        $query = EmailTemplate::accessibleByUser($user);

        if ($type) {
            $query->ofType($type);
        }

        if ($onlyActive) {
            $query->active();
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Obtener template por defecto para un tipo
     */
    public function getDefaultTemplate(User $user, string $type): ?EmailTemplate
    {
        return EmailTemplate::forUser($user->id)
            ->defaultFor($type)
            ->first();
    }

    /**
     * Renderizar un template con datos
     */
    public function renderTemplate(EmailTemplate $template, array $data = []): array
    {
        // Incrementar contador de uso
        $template->incrementUsage();

        return $template->render($data);
    }

    /**
     * Duplicar un template
     */
    public function duplicateTemplate(EmailTemplate $template, string $newName): EmailTemplate
    {
        $duplicate = $template->replicate();
        $duplicate->name = $newName;
        $duplicate->is_default = false;
        $duplicate->usage_count = 0;
        $duplicate->last_used_at = null;
        $duplicate->save();

        Log::info('📋 Template duplicado', [
            'original_id' => $template->id,
            'duplicate_id' => $duplicate->id,
            'new_name' => $newName,
        ]);

        return $duplicate;
    }

    /**
     * Obtener templates más usados
     */
    public function getMostUsedTemplates(User $user, int $limit = 5): Collection
    {
        return EmailTemplate::forUser($user->id)
            ->active()
            ->where('usage_count', '>', 0)
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener templates recientes
     */
    public function getRecentTemplates(User $user, int $limit = 5): Collection
    {
        return EmailTemplate::forUser($user->id)
            ->active()
            ->whereNotNull('last_used_at')
            ->orderBy('last_used_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Activar/Desactivar template
     */
    public function toggleActive(EmailTemplate $template): EmailTemplate
    {
        $template->update(['is_active' => !$template->is_active]);

        Log::info('🔄 Template ' . ($template->is_active ? 'activado' : 'desactivado'), [
            'template_id' => $template->id,
        ]);

        return $template;
    }

    /**
     * Establecer template como predeterminado
     */
    public function setAsDefault(EmailTemplate $template): EmailTemplate
    {
        $template->setAsDefault();

        Log::info('⭐ Template marcado como predeterminado', [
            'template_id' => $template->id,
            'type' => $template->type,
        ]);

        return $template->fresh();
    }

    /**
     * Obtener estadísticas de templates del usuario
     */
    public function getStats(User $user): array
    {
        $templates = EmailTemplate::forUser($user->id)->get();

        return [
            'total' => $templates->count(),
            'active' => $templates->where('is_active', true)->count(),
            'inactive' => $templates->where('is_active', false)->count(),
            'by_type' => $templates->groupBy('type')->map->count(),
            'total_usage' => $templates->sum('usage_count'),
            'most_used' => $templates->sortByDesc('usage_count')->first(),
        ];
    }
}
