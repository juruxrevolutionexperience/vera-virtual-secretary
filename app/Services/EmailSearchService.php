<?php

namespace App\Services;

use App\Models\Email;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmailSearchService
{
    /**
     * Búsqueda avanzada multi-criterio
     *
     * @param User $user
     * @param array $criteria [
     *   'query' => string,           // Búsqueda en texto (subject, body, from)
     *   'from' => string,            // Email del remitente
     *   'to' => string,              // Email del destinatario
     *   'category_id' => int,        // ID de categoría
     *   'workflow_status' => string, // Estado de workflow
     *   'folder_id' => int,          // ID de carpeta
     *   'has_attachments' => bool,   // Tiene adjuntos
     *   'is_starred' => bool,        // Destacados
     *   'is_read' => bool,           // Leídos/No leídos
     *   'is_archived' => bool,       // Archivados
     *   'vera_processed' => bool,    // Procesados por Vera
     *   'requires_action' => bool,   // Requiere acción
     *   'priority' => string,        // Prioridad (high, urgent, normal, low)
     *   'date_from' => date,         // Fecha desde
     *   'date_to' => date,           // Fecha hasta
     *   'due_date_from' => date,     // Vencimiento desde
     *   'due_date_to' => date,       // Vencimiento hasta
     *   'is_overdue' => bool,        // Vencidos
     *   'is_snoozed' => bool,        // En snooze
     *   'tags' => array,             // Tags de Vera
     *   'sentiment_min' => float,    // Sentimiento mínimo
     *   'sentiment_max' => float,    // Sentimiento máximo
     *   'sort_by' => string,         // Campo de ordenamiento
     *   'sort_order' => string,      // asc/desc
     *   'per_page' => int,           // Paginación
     * ]
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|Collection
     */
    public function search(User $user, array $criteria)
    {
        $query = Email::where('user_id', $user->id);

        // Excluir eliminados por defecto (a menos que se especifique lo contrario)
        if (!isset($criteria['include_deleted']) || !$criteria['include_deleted']) {
            $query->where('is_deleted', false);
        }

        // Excluir borradores por defecto
        if (!isset($criteria['include_drafts']) || !$criteria['include_drafts']) {
            $query->where('is_draft', false);
        }

        // BÚSQUEDA DE TEXTO
        if (!empty($criteria['query'])) {
            $searchQuery = $criteria['query'];
            $query->where(function ($q) use ($searchQuery) {
                $q->where('subject', 'like', "%{$searchQuery}%")
                    ->orWhere('body_text', 'like', "%{$searchQuery}%")
                    ->orWhere('snippet', 'like', "%{$searchQuery}%")
                    ->orWhere('from_name', 'like', "%{$searchQuery}%")
                    ->orWhere('from_email', 'like', "%{$searchQuery}%");
            });
        }

        // FILTRO: Remitente específico
        if (!empty($criteria['from'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('from_email', 'like', "%{$criteria['from']}%")
                    ->orWhere('from_name', 'like', "%{$criteria['from']}%");
            });
        }

        // FILTRO: Destinatario específico
        if (!empty($criteria['to'])) {
            $query->where('to_email', 'like', "%{$criteria['to']}%");
        }

        // FILTRO: Categoría
        if (isset($criteria['category_id'])) {
            $query->where('email_category_id', $criteria['category_id']);
        }

        // FILTRO: Estado de workflow
        if (!empty($criteria['workflow_status'])) {
            if (is_array($criteria['workflow_status'])) {
                $query->whereIn('workflow_status', $criteria['workflow_status']);
            } else {
                $query->where('workflow_status', $criteria['workflow_status']);
            }
        }

        // FILTRO: Carpeta
        if (!empty($criteria['folder_id'])) {
            $query->whereHas('folders', function ($q) use ($criteria) {
                $q->where('email_folders.id', $criteria['folder_id']);
            });
        }

        // FILTRO: Tiene adjuntos
        if (isset($criteria['has_attachments'])) {
            $query->where('has_attachments', $criteria['has_attachments']);
        }

        // FILTRO: Destacados
        if (isset($criteria['is_starred'])) {
            $query->where('is_starred', $criteria['is_starred']);
        }

        // FILTRO: Leídos/No leídos
        if (isset($criteria['is_read'])) {
            $query->where('is_read', $criteria['is_read']);
        }

        // FILTRO: Archivados
        if (isset($criteria['is_archived'])) {
            $query->where('is_archived', $criteria['is_archived']);
        }

        // FILTRO: Procesados por Vera
        if (isset($criteria['vera_processed'])) {
            $query->where('vera_processed', $criteria['vera_processed']);
        }

        // FILTRO: Requiere acción
        if (isset($criteria['requires_action'])) {
            $query->where('requires_action', $criteria['requires_action']);
        }

        // FILTRO: Prioridad
        if (!empty($criteria['priority'])) {
            if (is_array($criteria['priority'])) {
                $query->whereIn('priority', $criteria['priority']);
            } else {
                $query->where('priority', $criteria['priority']);
            }
        }

        // FILTRO: Fecha de recepción (desde)
        if (!empty($criteria['date_from'])) {
            $query->where('received_at', '>=', $criteria['date_from']);
        }

        // FILTRO: Fecha de recepción (hasta)
        if (!empty($criteria['date_to'])) {
            $query->where('received_at', '<=', $criteria['date_to']);
        }

        // FILTRO: Fecha de vencimiento (desde)
        if (!empty($criteria['due_date_from'])) {
            $query->where('due_date', '>=', $criteria['due_date_from']);
        }

        // FILTRO: Fecha de vencimiento (hasta)
        if (!empty($criteria['due_date_to'])) {
            $query->where('due_date', '<=', $criteria['due_date_to']);
        }

        // FILTRO: Vencidos
        if (isset($criteria['is_overdue']) && $criteria['is_overdue']) {
            $query->overdue();
        }

        // FILTRO: En snooze
        if (isset($criteria['is_snoozed']) && $criteria['is_snoozed']) {
            $query->snoozed();
        }

        // FILTRO: Tags de Vera (JSON search)
        if (!empty($criteria['tags']) && is_array($criteria['tags'])) {
            foreach ($criteria['tags'] as $tag) {
                $query->whereJsonContains('vera_tags', $tag);
            }
        }

        // FILTRO: Sentimiento mínimo
        if (isset($criteria['sentiment_min'])) {
            $query->where('vera_sentiment_score', '>=', $criteria['sentiment_min']);
        }

        // FILTRO: Sentimiento máximo
        if (isset($criteria['sentiment_max'])) {
            $query->where('vera_sentiment_score', '<=', $criteria['sentiment_max']);
        }

        // ORDENAMIENTO
        $sortBy = $criteria['sort_by'] ?? 'received_at';
        $sortOrder = $criteria['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);

        // PAGINACIÓN
        $perPage = $criteria['per_page'] ?? 50;

        if (isset($criteria['paginate']) && $criteria['paginate'] === false) {
            return $query->get();
        }

        return $query->paginate($perPage);
    }

    /**
     * Búsqueda rápida (solo texto)
     */
    public function quickSearch(User $user, string $query, int $limit = 20): Collection
    {
        return Email::where('user_id', $user->id)
            ->where('is_deleted', false)
            ->where('is_draft', false)
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                    ->orWhere('body_text', 'like', "%{$query}%")
                    ->orWhere('snippet', 'like', "%{$query}%")
                    ->orWhere('from_name', 'like', "%{$query}%")
                    ->orWhere('from_email', 'like', "%{$query}%");
            })
            ->orderBy('received_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Buscar emails similares (mismo remitente o thread)
     */
    public function findSimilarEmails(Email $email, int $limit = 10): Collection
    {
        return Email::where('user_id', $email->user_id)
            ->where('id', '!=', $email->id)
            ->where('is_deleted', false)
            ->where(function ($q) use ($email) {
                // Mismo thread
                if ($email->thread_id) {
                    $q->where('thread_id', $email->thread_id);
                }
                // O mismo remitente
                $q->orWhere('from_email', $email->from_email);
            })
            ->orderBy('received_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Búsqueda por remitente
     */
    public function searchBySender(User $user, string $senderEmail): Collection
    {
        return Email::where('user_id', $user->id)
            ->where('from_email', $senderEmail)
            ->where('is_deleted', false)
            ->orderBy('received_at', 'desc')
            ->get();
    }

    /**
     * Obtener estadísticas de búsqueda
     */
    public function getSearchStats(User $user, array $criteria): array
    {
        // Obtener resultados sin paginar
        $criteria['paginate'] = false;
        $results = $this->search($user, $criteria);

        return [
            'total' => $results->count(),
            'unread' => $results->where('is_read', false)->count(),
            'starred' => $results->where('is_starred', true)->count(),
            'with_attachments' => $results->where('has_attachments', true)->count(),
            'vera_processed' => $results->where('vera_processed', true)->count(),
            'requires_action' => $results->where('requires_action', true)->count(),
            'overdue' => $results->filter(fn($email) => $email->isOverdue())->count(),
            'by_workflow' => $results->groupBy('workflow_status')->map->count(),
            'by_priority' => $results->groupBy('priority')->map->count(),
        ];
    }

    /**
     * Autocompletar remitentes
     */
    public function autocompleteSenders(User $user, string $query, int $limit = 10): Collection
    {
        return Email::where('user_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('from_email', 'like', "%{$query}%")
                    ->orWhere('from_name', 'like', "%{$query}%");
            })
            ->select('from_email', 'from_name')
            ->distinct()
            ->limit($limit)
            ->get()
            ->map(function ($email) {
                return [
                    'email' => $email->from_email,
                    'name' => $email->from_name,
                    'display' => $email->from_name
                        ? "{$email->from_name} <{$email->from_email}>"
                        : $email->from_email,
                ];
            });
    }

    /**
     * Guardar búsqueda como filtro favorito
     */
    public function saveSearchAsFilter(User $user, string $name, array $criteria): array
    {
        // Obtener filtros guardados del usuario
        $savedFilters = $user->settings['saved_email_filters'] ?? [];

        // Agregar nuevo filtro
        $savedFilters[] = [
            'id' => uniqid(),
            'name' => $name,
            'criteria' => $criteria,
            'created_at' => now()->toISOString(),
        ];

        // Guardar en settings del usuario
        $user->update([
            'settings' => array_merge($user->settings ?? [], [
                'saved_email_filters' => $savedFilters,
            ]),
        ]);

        return $savedFilters;
    }

    /**
     * Obtener filtros guardados
     */
    public function getSavedFilters(User $user): array
    {
        return $user->settings['saved_email_filters'] ?? [];
    }
}
