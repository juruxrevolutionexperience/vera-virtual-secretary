<?php

namespace App\Services;

use App\Models\EmailFolder;
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmailFolderService
{
    /**
     * Crear una nueva carpeta
     */
    public function createFolder(User $user, array $data): EmailFolder
    {
        // Validar que si tiene padre, pertenezca al mismo usuario
        if (isset($data['parent_folder_id'])) {
            $parentFolder = EmailFolder::find($data['parent_folder_id']);

            if (!$parentFolder || $parentFolder->user_id !== $user->id) {
                throw new \Exception('Parent folder not found or does not belong to user');
            }
        }

        // Calcular orden automáticamente (último + 1)
        if (!isset($data['order'])) {
            $maxOrder = EmailFolder::where('user_id', $user->id)
                ->where('parent_folder_id', $data['parent_folder_id'] ?? null)
                ->max('order') ?? 0;

            $data['order'] = $maxOrder + 1;
        }

        // Determinar organization_id si se comparte con organización
        $organizationId = null;
        if (($data['is_organization_shared'] ?? false) && $user->organizations()->wherePivot('is_active', true)->exists()) {
            $organizationId = $user->organizations()->wherePivot('is_active', true)->first()->id;
        }

        $folder = EmailFolder::create([
            'user_id' => $user->id,
            'organization_id' => $organizationId,
            'parent_folder_id' => $data['parent_folder_id'] ?? null,
            'name' => $data['name'],
            'icon' => $data['icon'] ?? 'folder',
            'color' => $data['color'] ?? null,
            'description' => $data['description'] ?? null,
            'order' => $data['order'],
            'is_system_folder' => $data['is_system_folder'] ?? false,
            'is_shared' => $data['is_shared'] ?? false,
            'settings' => $data['settings'] ?? null,
        ]);

        Log::info('📁 Carpeta creada', [
            'folder_id' => $folder->id,
            'name' => $folder->name,
            'user_id' => $user->id,
        ]);

        return $folder;
    }

    /**
     * Actualizar una carpeta
     */
    public function updateFolder(EmailFolder $folder, array $data): EmailFolder
    {
        // No permitir editar carpetas del sistema
        if ($folder->is_system_folder) {
            throw new \Exception('Cannot edit system folders');
        }

        // Si se cambia el padre, validar que no cree un ciclo
        if (isset($data['parent_folder_id']) && $data['parent_folder_id'] !== $folder->parent_folder_id) {
            $newParent = EmailFolder::find($data['parent_folder_id']);

            if ($newParent && $newParent->isDescendantOf($folder)) {
                throw new \Exception('Cannot move folder to its own descendant');
            }
        }

        $folder->update($data);

        Log::info('📝 Carpeta actualizada', [
            'folder_id' => $folder->id,
            'name' => $folder->name,
        ]);

        return $folder->fresh();
    }

    /**
     * Eliminar una carpeta
     */
    public function deleteFolder(EmailFolder $folder, bool $reassignEmails = false, ?int $targetFolderId = null): void
    {
        // No permitir eliminar carpetas del sistema
        if ($folder->is_system_folder) {
            throw new \Exception('Cannot delete system folders');
        }

        DB::transaction(function () use ($folder, $reassignEmails, $targetFolderId) {
            // Reasignar emails si se especifica
            if ($reassignEmails && $targetFolderId) {
                $targetFolder = EmailFolder::findOrFail($targetFolderId);

                // Mover todos los emails a la carpeta destino
                $emailIds = $folder->emails()->pluck('emails.id');

                foreach ($emailIds as $emailId) {
                    $email = Email::find($emailId);
                    $email->removeFromFolder($folder);
                    $email->moveToFolder($targetFolder);
                }
            } else {
                // Simplemente quitar los emails de esta carpeta (no se eliminan los emails)
                $folder->emails()->detach();
            }

            // Mover subcarpetas al padre de esta carpeta
            $folder->children()->update([
                'parent_folder_id' => $folder->parent_folder_id,
            ]);

            // Eliminar carpeta
            $folder->delete();

            Log::info('🗑️  Carpeta eliminada', [
                'folder_id' => $folder->id,
                'name' => $folder->name,
            ]);
        });
    }

    /**
     * Obtener árbol de carpetas del usuario (propias + compartidas)
     */
    public function getFolderTree(User $user): array
    {
        // Incluir carpetas propias + carpetas compartidas de la organización
        $rootFolders = EmailFolder::accessibleByUser($user)
            ->whereNull('parent_folder_id')
            ->orderBy('order')
            ->with('descendantsRecursive')
            ->get();

        return $this->buildTree($rootFolders);
    }

    /**
     * Construir árbol jerárquico de carpetas
     */
    protected function buildTree($folders): array
    {
        return $folders->map(function ($folder) {
            return [
                'id' => $folder->id,
                'name' => $folder->name,
                'icon' => $folder->icon,
                'color' => $folder->color,
                'description' => $folder->description,
                'order' => $folder->order,
                'is_system_folder' => $folder->is_system_folder,
                'is_shared' => $folder->is_shared,
                'organization_id' => $folder->organization_id,
                'is_organization_shared' => !is_null($folder->organization_id), // Nueva prop: es carpeta de organización
                'emails_count' => $folder->getEmailsCount(),
                'emails_count_recursive' => $folder->getEmailsCountRecursive(),
                'depth' => $folder->getDepthLevel(),
                'full_path' => $folder->getFullPath(),
                'children' => $this->buildTree($folder->children),
            ];
        })->toArray();
    }

    /**
     * Mover un email a una carpeta
     */
    public function moveEmailToFolder(Email $email, EmailFolder $folder, User $user, ?string $notes = null): void
    {
        // Verificar que la carpeta pertenece al usuario
        if ($folder->user_id !== $user->id) {
            throw new \Exception('Folder does not belong to user');
        }

        // Verificar que el email pertenece al usuario
        if ($email->user_id !== $user->id) {
            throw new \Exception('Email does not belong to user');
        }

        $email->moveToFolder($folder, $user->id, $notes);

        Log::info('📧➡️📁 Email movido a carpeta', [
            'email_id' => $email->id,
            'folder_id' => $folder->id,
            'folder_name' => $folder->name,
        ]);
    }

    /**
     * Mover múltiples emails a una carpeta
     */
    public function moveBulkEmailsToFolder(array $emailIds, EmailFolder $folder, User $user): int
    {
        $movedCount = 0;

        foreach ($emailIds as $emailId) {
            try {
                $email = Email::findOrFail($emailId);
                $this->moveEmailToFolder($email, $folder, $user);
                $movedCount++;
            } catch (\Exception $e) {
                Log::warning('Error moviendo email a carpeta', [
                    'email_id' => $emailId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $movedCount;
    }

    /**
     * Reordenar carpetas
     */
    public function reorderFolders(array $folderOrder): void
    {
        DB::transaction(function () use ($folderOrder) {
            foreach ($folderOrder as $index => $folderId) {
                EmailFolder::where('id', $folderId)->update(['order' => $index]);
            }
        });

        Log::info('🔄 Carpetas reordenadas', [
            'count' => count($folderOrder),
        ]);
    }

    /**
     * Crear carpetas del sistema por defecto para un usuario
     */
    public function createSystemFolders(User $user): array
    {
        // Verificar si el usuario ya tiene carpetas del sistema
        $existingSystemFolders = EmailFolder::where('user_id', $user->id)
            ->where('is_system_folder', true)
            ->count();

        if ($existingSystemFolders > 0) {
            Log::info('⚠️ Usuario ya tiene carpetas del sistema, omitiendo creación', [
                'user_id' => $user->id,
                'existing_count' => $existingSystemFolders,
            ]);

            return EmailFolder::where('user_id', $user->id)
                ->where('is_system_folder', true)
                ->orderBy('order')
                ->get()
                ->toArray();
        }

        $systemFolders = [
            ['name' => 'Importantes', 'icon' => 'star', 'color' => '#FFD700'],
            ['name' => 'Trabajo', 'icon' => 'briefcase', 'color' => '#4A90E2'],
            ['name' => 'Personal', 'icon' => 'user', 'color' => '#50C878'],
            ['name' => 'Archivados', 'icon' => 'archive', 'color' => '#95A5A6'],
        ];

        $createdFolders = [];

        foreach ($systemFolders as $index => $folderData) {
            $folder = EmailFolder::create([
                'user_id' => $user->id,
                'parent_folder_id' => null,
                'name' => $folderData['name'],
                'icon' => $folderData['icon'],
                'color' => $folderData['color'],
                'order' => $index,
                'is_system_folder' => true,
            ]);

            $createdFolders[] = $folder;
        }

        Log::info('📂 Carpetas del sistema creadas', [
            'user_id' => $user->id,
            'count' => count($systemFolders),
        ]);

        return $createdFolders;
    }
}
