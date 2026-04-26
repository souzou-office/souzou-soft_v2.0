<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 4.1 マイビュー / 4.3 個別担当者ビュー。
 * 自分の場合は誰でも閲覧可、他人の場合は admin のみ閲覧可。
 */
class UserDashboardController extends Controller
{
    public function me(Request $request): Response
    {
        return $this->renderFor($request->user(), $request, mine: true);
    }

    public function show(Request $request, User $user): Response
    {
        // 4.3.2 権限制御: 他人のビューは管理者のみ
        if ($user->id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403);
        }
        return $this->renderFor($user, $request, mine: $user->id === $request->user()->id);
    }

    private function renderFor(User $user, Request $request, bool $mine): Response
    {
        $tasks = Task::query()
            ->with(['matter', 'assignedBy'])
            ->where('assignee_user_id', $user->id)
            ->whereNull('completed_at')
            ->orderBy('planned_date')
            ->get();

        $today    = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $weekEnd  = $today->copy()->addDays(7);

        $sections = [
            'overdue' => [],
            'today'   => [],
            'tomorrow'=> [],
            'this_week'=> [],
        ];

        foreach ($tasks as $task) {
            $serialized = $this->serialize($task);
            $date = $task->planned_date?->startOfDay();
            if (! $date) {
                continue;
            }
            if ($date->isBefore($today)) {
                $sections['overdue'][] = $serialized;
            } elseif ($date->isSameDay($today)) {
                $sections['today'][] = $serialized;
            } elseif ($date->isSameDay($tomorrow)) {
                $sections['tomorrow'][] = $serialized;
            } elseif ($date->lessThanOrEqualTo($weekEnd)) {
                $sections['this_week'][] = $serialized;
            }
        }

        return Inertia::render('User/Dashboard', [
            'user'     => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role],
            'mine'     => $mine,
            'sections' => $sections,
        ]);
    }

    private function serialize(Task $task): array
    {
        return [
            'id'             => $task->id,
            'matter_id'      => $task->job_id,
            'matter_number'  => $task->matter?->matter_number,
            'task_name'      => $task->task_name,
            'planned_date'   => optional($task->planned_date)->format('Y-m-d'),
            'days_left'      => $task->daysUntilDue(),
            'state'          => $task->visualState(),
            'assigned_by'    => $task->assignedBy?->name,
            'comments_count' => $task->comments()->count(),
        ];
    }
}
