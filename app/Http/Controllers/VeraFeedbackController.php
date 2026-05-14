<?php

namespace App\Http\Controllers;

use App\Models\VeraFeedback;
use App\Models\VeraFeedbackAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VeraFeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category'        => 'required|in:' . implode(',', array_keys(VeraFeedback::CATEGORIES)),
            'message'         => 'required|string|max:5000',
            'context_url'     => 'nullable|string|max:1000',
            'conversation_id' => 'nullable|integer|exists:vera_conversations,id',
            'attachments'     => 'nullable|array|max:5',
            'attachments.*'   => 'file|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        $user = Auth::user();

        $feedback = VeraFeedback::create([
            'user_id'         => $user->id,
            'category'        => $validated['category'],
            'message'         => $validated['message'],
            'context_url'     => $validated['context_url'] ?? null,
            'conversation_id' => $validated['conversation_id'] ?? null,
            'metadata'        => [
                'user_agent' => $request->userAgent(),
                'ip'         => $request->ip(),
            ],
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $ext      = $file->getClientOriginalExtension() ?: 'png';
                $filename = $feedback->id . '_' . Str::random(16) . '.' . $ext;
                $path     = $file->storeAs('feedback', $filename, 'public');

                VeraFeedbackAttachment::create([
                    'vera_feedback_id' => $feedback->id,
                    'path'             => $path,
                    'original_name'    => $file->getClientOriginalName(),
                    'size'             => $file->getSize(),
                    'mime_type'        => $file->getMimeType() ?: 'image/png',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'id'      => $feedback->id,
        ]);
    }
}
