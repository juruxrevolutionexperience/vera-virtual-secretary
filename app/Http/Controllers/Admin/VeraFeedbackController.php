<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VeraFeedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VeraFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $query = VeraFeedback::with(['user:id,name,email', 'attachments'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $feedbacks = $query->paginate(20)->withQueryString();

        $counts = VeraFeedback::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return view('admin.feedbacks.index', [
            'feedbacks'  => $feedbacks,
            'counts'     => $counts,
            'categories' => VeraFeedback::CATEGORIES,
            'statuses'   => VeraFeedback::STATUSES,
        ]);
    }

    public function show(VeraFeedback $feedback): View
    {
        $feedback->load(['user', 'attachments', 'conversation']);

        return view('admin.feedbacks.show', [
            'feedback'   => $feedback,
            'categories' => VeraFeedback::CATEGORIES,
            'statuses'   => VeraFeedback::STATUSES,
        ]);
    }

    public function update(Request $request, VeraFeedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'status'      => 'required|in:' . implode(',', array_keys(VeraFeedback::STATUSES)),
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $feedback->update($validated);

        return redirect()
            ->route('admin.feedbacks.show', $feedback)
            ->with('status', 'Feedback actualizado.');
    }
}
