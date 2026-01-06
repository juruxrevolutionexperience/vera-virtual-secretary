<?php

namespace App\Http\Controllers;

use App\Models\EmailFolder;
use App\Services\EmailFolderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailFolderController extends Controller
{
    protected $folderService;

    public function __construct(EmailFolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Obtener todas las carpetas del usuario (en árbol jerárquico)
     */
    public function index()
    {
        $user = Auth::user();
        $folderTree = $this->folderService->getFolderTree($user);

        return response()->json([
            'success' => true,
            'folders' => $folderTree,
        ]);
    }

    /**
     * Obtener árbol de carpetas (alias para AJAX)
     */
    public function tree()
    {
        return $this->index();
    }

    /**
     * Obtener emails de una carpeta específica
     */
    public function getEmailsInFolder(EmailFolder $folder)
    {
        // Verificar que pertenece al usuario
        if ($folder->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $emails = $folder->emails()
            ->where('is_deleted', false)
            ->orderBy('received_at', 'desc')
            ->with(['category', 'attachments'])
            ->get()
            ->map(function ($email) {
                return [
                    'id' => $email->id,
                    'from_name' => $email->from_name,
                    'from_email' => $email->from_email,
                    'subject' => $email->subject,
                    'excerpt' => $email->excerpt,
                    'received_at' => $email->received_at,
                    'is_read' => $email->is_read,
                    'is_starred' => $email->is_starred,
                    'has_attachments' => $email->has_attachments,
                    'attachments_count' => $email->attachments_count,
                    'vera_processed' => $email->vera_processed,
                    'category' => $email->category ? $email->category->name : null,
                    'workflow_status' => $email->workflow_status,
                ];
            });

        return response()->json([
            'success' => true,
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'full_path' => $folder->getFullPath(),
            ],
            'emails' => $emails,
            'count' => $emails->count(),
        ]);
    }

    /**
     * Crear una nueva carpeta
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_folder_id' => 'nullable|exists:email_folders,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7', // Hex color
            'description' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        try {
            $folder = $this->folderService->createFolder($user, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Carpeta creada exitosamente',
                'folder' => $folder,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener una carpeta específica
     */
    public function show(EmailFolder $folder)
    {
        // Verificar que pertenece al usuario
        if ($folder->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'folder' => [
                'id' => $folder->id,
                'name' => $folder->name,
                'icon' => $folder->icon,
                'color' => $folder->color,
                'description' => $folder->description,
                'parent_folder_id' => $folder->parent_folder_id,
                'order' => $folder->order,
                'is_system_folder' => $folder->is_system_folder,
                'is_shared' => $folder->is_shared,
                'emails_count' => $folder->getEmailsCount(),
                'full_path' => $folder->getFullPath(),
            ],
        ]);
    }

    /**
     * Actualizar una carpeta
     */
    public function update(Request $request, EmailFolder $folder)
    {
        // Verificar que pertenece al usuario
        if ($folder->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_folder_id' => 'nullable|exists:email_folders,id',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
            'order' => 'sometimes|integer|min:0',
        ]);

        try {
            $folder = $this->folderService->updateFolder($folder, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Carpeta actualizada exitosamente',
                'folder' => $folder,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Eliminar una carpeta
     */
    public function destroy(Request $request, EmailFolder $folder)
    {
        // Verificar que pertenece al usuario
        if ($folder->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $validated = $request->validate([
            'reassign_emails' => 'sometimes|boolean',
            'target_folder_id' => 'nullable|exists:email_folders,id',
        ]);

        try {
            $this->folderService->deleteFolder(
                $folder,
                $validated['reassign_emails'] ?? false,
                $validated['target_folder_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Carpeta eliminada exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mover un email a una carpeta
     */
    public function assignEmail(Request $request, EmailFolder $folder)
    {
        $validated = $request->validate([
            'email_id' => 'required|exists:emails,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $email = \App\Models\Email::findOrFail($validated['email_id']);

        try {
            $this->folderService->moveEmailToFolder(
                $email,
                $folder,
                $user,
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Email movido a carpeta',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mover múltiples emails a una carpeta (bulk)
     */
    public function bulkAssignEmails(Request $request, EmailFolder $folder)
    {
        $validated = $request->validate([
            'email_ids' => 'required|array',
            'email_ids.*' => 'exists:emails,id',
        ]);

        $user = Auth::user();

        try {
            $movedCount = $this->folderService->moveBulkEmailsToFolder(
                $validated['email_ids'],
                $folder,
                $user
            );

            return response()->json([
                'success' => true,
                'message' => "{$movedCount} emails movidos a carpeta",
                'moved_count' => $movedCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reordenar carpetas (drag & drop)
     */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'folder_order' => 'required|array',
            'folder_order.*' => 'exists:email_folders,id',
        ]);

        try {
            $this->folderService->reorderFolders($validated['folder_order']);

            return response()->json([
                'success' => true,
                'message' => 'Carpetas reordenadas exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Crear carpetas del sistema por defecto
     */
    public function createSystemFolders()
    {
        $user = Auth::user();

        try {
            $this->folderService->createSystemFolders($user);

            return response()->json([
                'success' => true,
                'message' => 'Carpetas del sistema creadas',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
