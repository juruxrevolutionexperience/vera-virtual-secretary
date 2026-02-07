<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailTemplateController extends Controller
{
    protected $templateService;

    public function __construct(EmailTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $templates = $this->templateService->getUserTemplates(
            $user,
            $request->type,
            $request->boolean('active_only')
        );

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'description' => 'nullable|string',
            'type' => 'required|in:reply,forward,new,auto_response',
            'placeholders' => 'nullable|array',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_organization_shared' => 'nullable|boolean',
        ]);

        $template = $this->templateService->createTemplate(Auth::user(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Template creado exitosamente',
            'template' => $template,
        ], 201);
    }

    public function show(EmailTemplate $template)
    {
        if ($template->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }

    public function update(Request $request, EmailTemplate $template)
    {
        if ($template->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:reply,forward,new,auto_response',
            'placeholders' => 'nullable|array',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $template = $this->templateService->updateTemplate($template, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Template actualizado exitosamente',
            'template' => $template,
        ]);
    }

    public function destroy(EmailTemplate $template)
    {
        if ($template->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $this->templateService->deleteTemplate($template);

        return response()->json([
            'success' => true,
            'message' => 'Template eliminado exitosamente',
        ]);
    }

    public function toggleActive(EmailTemplate $template)
    {
        if ($template->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $template = $this->templateService->toggleActive($template);

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }

    public function setDefault(EmailTemplate $template)
    {
        if ($template->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $template = $this->templateService->setAsDefault($template);

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }

    public function duplicate(Request $request, EmailTemplate $template)
    {
        if ($template->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $duplicate = $this->templateService->duplicateTemplate($template, $validated['name']);

        return response()->json([
            'success' => true,
            'message' => 'Template duplicado exitosamente',
            'template' => $duplicate,
        ], 201);
    }

    public function stats()
    {
        $stats = $this->templateService->getStats(Auth::user());

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Renderizar template con datos específicos (preview)
     */
    public function render(Request $request, EmailTemplate $template)
    {
        if ($template->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        $rendered = $template->render($validated['data']);

        return response()->json([
            'success' => true,
            'rendered' => $rendered,
        ]);
    }
}
