<?php

namespace App\Http\Controllers;

use App\Models\Matter;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ホーム画面（事件一覧）。3.2 進捗表示強化版。
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $matters = Matter::query()
            ->with(['mainUser', 'tasks.assignee', 'parties'])
            ->orderBy('settlement_date')
            ->paginate(30)
            ->through(fn (Matter $matter) => $this->serialize($matter));

        return Inertia::render('Home/Index', [
            'matters' => $matters,
        ]);
    }

    private function serialize(Matter $matter): array
    {
        $progress = $matter->progressSummary();
        $next     = $matter->nextTask();

        return [
            'id'             => $matter->id,
            'matter_number'  => $matter->matter_number,
            'job_type'       => $matter->job_type?->value,
            'job_type_label' => $matter->job_type?->label(),
            'settlement_at'  => optional($matter->settlement_date)->toIso8601String(),
            'main_user'      => $matter->mainUser ? [
                'id'   => $matter->mainUser->id,
                'name' => $matter->mainUser->name,
            ] : null,
            'progress'       => $progress,
            'next_task'      => $next ? [
                'id'           => $next->id,
                'name'         => $next->task_name,
                'assignee'     => $next->assignee?->name,
                'planned_date' => optional($next->planned_date)->format('Y-m-d'),
                'days_left'    => $next->daysUntilDue(),
                'state'        => $next->visualState(),
            ] : null,
            // 3.2 一覧テーブル用にステッパー描画データ
            'stepper'        => $matter->tasks->map(fn (Task $t) => [
                'id'           => $t->id,
                'name'         => $t->task_name,
                'state'        => $t->visualState(),
                'role_code'    => $t->role_code?->value,
                'assignee'     => $t->assignee?->name,
                'planned_date' => optional($t->planned_date)->format('Y-m-d'),
            ])->values(),
            'parties_summary' => $this->partiesSummary($matter),
        ];
    }

    private function partiesSummary(Matter $matter): string
    {
        $sellers = $matter->partiesOf(\App\Enums\RoleCode::Seller)->pluck('name')->take(2);
        $buyers  = $matter->partiesOf(\App\Enums\RoleCode::Buyer)->pluck('name')->take(2);
        $left    = $sellers->isEmpty() ? '—' : $sellers->join('・');
        $right   = $buyers->isEmpty() ? '—' : $buyers->join('・');
        return "$left → $right";
    }
}
