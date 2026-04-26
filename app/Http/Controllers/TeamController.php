<?php

namespace App\Http\Controllers;

use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 4.2 チームビュー。所長または管理者向け。
 *  - モードA: 担当者列ビュー（横軸=担当者、縦軸=期日帯）
 *  - モードB: 作業マトリクスビュー（縦軸=案件、横軸=担当者）
 */
class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $users = User::where('is_active', true)->orderBy('id')->get(['id', 'name']);

        $today    = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $weekEnd  = $today->copy()->addDays(7);

        $loadByUser = $users->mapWithKeys(function (User $user) use ($today, $tomorrow, $weekEnd) {
            $base = Task::query()
                ->where('assignee_user_id', $user->id)
                ->whereNull('completed_at');

            return [$user->id => [
                'overdue'   => (clone $base)->whereDate('planned_date', '<', $today)->count(),
                'today'     => (clone $base)->whereDate('planned_date', $today)->count(),
                'this_week' => (clone $base)
                    ->whereDate('planned_date', '>', $tomorrow)
                    ->whereDate('planned_date', '<=', $weekEnd)
                    ->count(),
                'next_week' => (clone $base)
                    ->whereDate('planned_date', '>', $weekEnd)
                    ->whereDate('planned_date', '<=', $weekEnd->copy()->addDays(7))
                    ->count(),
                'total'     => (clone $base)->count(),
            ]];
        });

        // モードB 用: 縦=案件、横=担当者の格子
        $matters = Matter::query()
            ->with(['tasks.assignee', 'parties'])
            ->orderBy('settlement_date')
            ->limit(30)
            ->get();

        $matrixRows = $matters->map(function (Matter $matter) use ($users) {
            $cells = [];
            foreach ($users as $user) {
                $userTasks = $matter->tasks->where('assignee_user_id', $user->id);
                $hasOverdue = $userTasks->contains(fn ($t) => $t->isOverdue());
                $hasIncomplete = $userTasks->contains(fn ($t) => ! $t->isCompleted());
                $hasComplete = $userTasks->contains(fn ($t) => $t->isCompleted());

                $cells[$user->id] = match (true) {
                    $hasOverdue    => 'overdue',
                    $hasIncomplete => 'in_progress',
                    $hasComplete   => 'completed',
                    default        => 'none',
                };
            }
            $unassignedCount = $matter->tasks->whereNull('assignee_user_id')->whereNull('completed_at')->count();

            return [
                'matter_id'        => $matter->id,
                'matter_number'    => $matter->matter_number,
                'settlement_at'    => optional($matter->settlement_date)->format('Y-m-d'),
                'cells'            => $cells,
                'unassigned_count' => $unassignedCount,
            ];
        });

        return Inertia::render('Team/Index', [
            'users'        => $users,
            'load_by_user' => $loadByUser,
            'matrix_rows'  => $matrixRows,
        ]);
    }
}
