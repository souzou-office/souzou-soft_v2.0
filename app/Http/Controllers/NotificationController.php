<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $items = Notification::query()
            ->where('target_user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (Notification $n) => [
                'id'         => $n->id,
                'type'       => $n->type?->value,
                'title'      => $n->title,
                'body'       => $n->body,
                'is_read'    => $n->is_read,
                'created_at' => $n->created_at->toIso8601String(),
                'matter_id'  => $n->related_job_id,
                'task_id'    => $n->related_task_id,
            ]);

        return Inertia::render('Notification/Index', [
            'notifications' => $items,
        ]);
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        if ($notification->target_user_id !== $request->user()->id) {
            abort(403);
        }
        $notification->update(['is_read' => true]);
        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        Notification::where('target_user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        return back();
    }
}
