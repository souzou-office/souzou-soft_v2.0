<?php

namespace App\Http\Controllers;

use App\Models\Matter;
use App\Models\User;
use App\Services\Tasks\AssignmentDetector;
use App\Services\Tasks\TaskPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MatterController extends Controller
{
    public function __construct(
        private readonly TaskPlanner $planner,
        private readonly AssignmentDetector $detector,
    ) {}

    public function show(Matter $matter): Response
    {
        $matter->load([
            'tasks.assignee',
            'tasks.assignedBy',
            'tasks.comments.user',
            'parties',
            'properties',
            'mainUser',
        ]);

        return Inertia::render('Matter/Show', [
            'matter'      => $this->serialize($matter),
            'unassigned'  => $this->detector->summarize($matter->id),
            'staff'       => User::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Matter/Create', [
            'staff' => User::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'matter_number'   => 'required|string|max:20|unique:matter_files,matter_number',
            'job_type'        => 'required|integer|min:1|max:11',
            'received_at'     => 'nullable|date',
            'settlement_date' => 'nullable|date',
            'main_user_id'    => 'nullable|exists:users,id',
        ]);

        $matter = Matter::create($data);

        // 5.2 / 5.3: 主担当者の自動割当 + 期日自動生成
        $this->planner->plan($matter);

        return redirect()->route('matters.show', $matter)->with('success', '事件を作成しました');
    }

    public function update(Request $request, Matter $matter): RedirectResponse
    {
        $data = $request->validate([
            'received_at'     => 'nullable|date',
            'settlement_date' => 'nullable|date',
            'main_user_id'    => 'nullable|exists:users,id',
            'progress'        => 'nullable|in:pre_acceptance,in_progress,completed,not_required',
            'drive_folder_id' => 'nullable|string|max:100',
        ]);

        $matter->update($data);

        return back()->with('success', '更新しました');
    }

    public function destroy(Matter $matter): RedirectResponse
    {
        $matter->delete();
        return redirect()->route('home')->with('success', '事件を削除しました');
    }

    public function index(Request $request): Response
    {
        return app(DashboardController::class)->index($request);
    }

    public function edit(Matter $matter): Response
    {
        return $this->show($matter);
    }

    /**
     * 3.1.x 3層構造ヘッダー描画用に整形した事件データ。
     */
    private function serialize(Matter $matter): array
    {
        $progress = $matter->progressSummary();
        $next     = $matter->nextTask();

        return [
            'id'              => $matter->id,
            'matter_number'   => $matter->matter_number,
            'job_type'        => $matter->job_type?->value,
            'job_type_label'  => $matter->job_type?->label(),
            'settlement_at'   => optional($matter->settlement_date)->toIso8601String(),
            'received_at'     => optional($matter->received_at)->toIso8601String(),
            'progress'        => $progress,
            'main_user'       => $matter->mainUser ? [
                'id' => $matter->mainUser->id, 'name' => $matter->mainUser->name,
            ] : null,
            'drive_folder_id' => $matter->drive_folder_id,
            'next_task'       => $next ? [
                'id'           => $next->id,
                'name'         => $next->task_name,
                'assignee'     => $next->assignee?->name,
                'planned_date' => optional($next->planned_date)->format('Y-m-d'),
                'days_left'    => $next->daysUntilDue(),
            ] : null,
            'parties' => $matter->parties->map(fn ($p) => [
                'id'          => $p->id,
                'role_code'   => $p->role_code?->value,
                'role_label'  => $p->role_code?->label(),
                'name'        => $p->name,
                'name_kana'   => $p->name_kana,
                'address'     => $p->address,
                'tel'         => $p->tel,
                'entity_type' => $p->entity_type,
            ]),
            'properties' => $matter->properties->map(fn ($p) => [
                'id'            => $p->id,
                'location'      => $p->location,
                'parcel_number' => $p->parcel_number,
                'land_category' => $p->land_category,
                'area'          => $p->area,
            ]),
            'tasks' => $matter->tasks->map(fn ($t) => [
                'id'             => $t->id,
                'task_code'      => $t->task_code,
                'role_code'      => $t->role_code?->value,
                'role_label'     => $t->role_code?->label(),
                'name'           => $t->task_name,
                'status'         => $t->status?->value,
                'state'          => $t->visualState(),
                'planned_date'   => optional($t->planned_date)->format('Y-m-d'),
                'days_left'      => $t->daysUntilDue(),
                'completed_at'   => optional($t->completed_at)->toIso8601String(),
                'assignee'       => $t->assignee ? [
                    'id' => $t->assignee->id, 'name' => $t->assignee->name,
                ] : null,
                'assigned_by'    => $t->assignedBy?->name,
                'comments_count' => $t->comments->count(),
            ]),
        ];
    }
}
