<?php

namespace App\Http\Controllers;

use App\Enums\NotificationType;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly NotificationDispatcher $notifier) {}

    public function show(Request $request, $matter, Task $task)
    {
        // 事件詳細画面の左サイドナビ「工程ジャンプ」の到達点。
        return redirect()->route('matters.show', ['matter' => $task->job_id, 'task' => $task->id]);
    }

    /**
     * インライン編集（2.3.3）からの PATCH。
     * 担当者変更時には task_assigned 通知を発火する（5.4.1）。
     */
    public function update(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'assignee_user_id' => 'nullable|exists:users,id',
            'planned_date'     => 'nullable|date',
            'status'           => 'nullable|in:not_started,in_progress,completed,awaiting_review',
            'reviewer_user_id' => 'nullable|exists:users,id',
        ]);

        $previousAssignee = $task->assignee_user_id;

        if (array_key_exists('assignee_user_id', $data)
            && $data['assignee_user_id'] !== $previousAssignee
        ) {
            $task->update([
                'assignee_user_id'    => $data['assignee_user_id'],
                'assigned_by_user_id' => $request->user()->id,
                'assigned_at'         => now(),
            ]);
            $this->notifier->taskAssigned($task->fresh());
        }

        if (array_key_exists('planned_date', $data)) {
            $task->update(['planned_date' => $data['planned_date']]);
        }

        if (array_key_exists('status', $data)) {
            $task->update(['status' => TaskStatus::from($data['status'])]);
            if ($data['status'] === TaskStatus::Completed->value) {
                $task->update(['completed_at' => now()]);
                $this->fireCompletionNotification($task, $request->user()->name);
            }
        }

        if (array_key_exists('reviewer_user_id', $data)) {
            $task->update(['reviewer_user_id' => $data['reviewer_user_id']]);
        }

        return back();
    }

    public function complete(Request $request, Task $task): RedirectResponse
    {
        $task->update([
            'status'       => TaskStatus::Completed,
            'completed_at' => now(),
        ]);
        $this->fireCompletionNotification($task, $request->user()->name);

        return back();
    }

    public function returnTask(Request $request, Task $task): RedirectResponse
    {
        $previousAssignee = $task->assignee;

        $task->update([
            'status'       => TaskStatus::NotStarted,
            'completed_at' => null,
        ]);

        if ($previousAssignee) {
            $this->notifier->dispatch(
                $previousAssignee,
                NotificationType::TaskReturned,
                [
                    '割当人名' => $request->user()->name,
                    '事件番号' => $task->matter?->matter_number ?? '',
                    '工程名'   => $task->task_name,
                ],
                $task->job_id,
                $task->id,
            );
        }

        return back();
    }

    public function addComment(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        // task 関係者全員に通知（5.7）
        $recipients = collect([
            $task->assignee_user_id,
            $task->assigned_by_user_id,
        ])
            ->merge($task->comments->pluck('user_id'))
            ->unique()
            ->reject(fn ($id) => $id === $request->user()->id || $id === null);

        foreach ($recipients as $userId) {
            $user = \App\Models\User::find($userId);
            if (! $user) continue;

            $this->notifier->dispatch(
                $user,
                NotificationType::TaskComment,
                [
                    '投稿者名'    => $request->user()->name,
                    '事件番号'    => $task->matter?->matter_number ?? '',
                    '工程名'      => $task->task_name,
                    'コメント本文' => mb_substr($data['body'], 0, 100),
                ],
                $task->job_id,
                $task->id,
            );
        }

        return back();
    }

    private function fireCompletionNotification(Task $task, string $completerName): void
    {
        if (! $task->assigned_by_user_id) return;
        if ($task->assigned_by_user_id === $task->assignee_user_id) return;

        $assigner = $task->assignedBy;
        if (! $assigner) return;

        $this->notifier->dispatch(
            $assigner,
            NotificationType::TaskCompleted,
            [
                '完了者名' => $completerName,
                '事件番号' => $task->matter?->matter_number ?? '',
                '工程名'   => $task->task_name,
            ],
            $task->job_id,
            $task->id,
        );
    }
}
