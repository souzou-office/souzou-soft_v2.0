<?php

namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Models\Matter;
use App\Models\User;
use App\Services\Documents\DocumentPlanner;
use App\Services\Tasks\AssignmentDetector;
use App\Services\Tasks\MilestonePlanner;
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
        private readonly DocumentPlanner $documentPlanner,
        private readonly MilestonePlanner $milestonePlanner,
    ) {}

    public function show(Matter $matter): Response
    {
        $matter->load([
            'tasks.assignee',
            'tasks.assignedBy',
            'tasks.comments.user',
            'tasks.documents',
            'tasks.dependencies',
            'documents',
            'milestones',
            'parties',
            'properties',
            'mainUser',
            'uploadedFiles.uploader',
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
        // 並列処理対応: 4 マイルストーン + 書類インスタンスを生成
        $this->milestonePlanner->plan($matter);
        $this->documentPlanner->plan($matter);

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
                'milestone_key'  => $t->milestone_key?->value,
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
                // DAG 依存: 並列処理モデル
                'is_blocked'     => $t->isBlocked(),
                'blocked_by'     => $t->blockingTaskNames(),
                // task の中身として書類を束ねる（一括受領の表現）
                'documents' => $t->documents->map(fn ($d) => [
                    'id'           => $d->id,
                    'code'         => $d->code,
                    'name'         => $d->name,
                    'kind'         => $d->kind?->value,
                    'state'        => $d->state?->value,
                    'state_label'  => $d->state?->label(),
                    'is_held'      => $d->is_held,
                    'deadline'     => optional($d->deadline)->format('Y-m-d'),
                ])->values(),
                'documents_total' => $t->documents->count(),
                'documents_done'  => $t->documents->filter(
                    fn ($d) => in_array($d->state?->value, ['confirmed', 'received'], true)
                )->count(),
            ]),
            // 並列処理対応: 書類群（ロール別レーンで描画される）
            'documents' => $matter->documents->map(fn ($d) => [
                'id'                  => $d->id,
                'code'                => $d->code,
                'name'                => $d->name,
                'kind'                => $d->kind?->value,
                'kind_label'          => $d->kind?->label(),
                'requested_from_role' => $d->requested_from_role?->value,
                'role_label'          => $d->requested_from_role?->label(),
                'delivery_method'     => $d->delivery_method?->value,
                'delivery_label'      => $d->delivery_method?->label(),
                'milestone_key'       => $d->milestone_key?->value,
                'state'               => $d->state?->value,
                'state_label'         => $d->state?->label(),
                'is_held'             => $d->is_held,
                'deadline'            => optional($d->deadline)->format('Y-m-d'),
                'days_left'           => $d->daysUntilDeadline(),
                'is_overdue'          => $d->isOverdue(),
                'confirmation_requires' => $d->confirmation_requires ?? [],
            ]),
            // 4 マイルストーン
            'milestones' => $matter->milestones->map(fn ($m) => [
                'id'           => $m->id,
                'key'          => $m->key?->value,
                'name'         => $m->name,
                'deadline'     => optional($m->deadline)->format('Y-m-d'),
                'days_left'    => $m->daysUntilDeadline(),
                'completed_at' => optional($m->completed_at)->toIso8601String(),
                'is_overdue'   => $m->isOverdue(),
            ]),
            // ロール別レーン用の集計
            'lane_summary' => $this->laneSummary($matter),

            // ドラッグ&ドロップで上げたファイル群（Drive リンク + 最近順）
            'uploaded_files' => $matter->uploadedFiles->map(fn ($f) => [
                'id'              => $f->id,
                'original_name'   => $f->original_name,
                'mime_type'       => $f->mime_type,
                'human_size'      => $f->humanSize(),
                'drive_url'       => $f->driveUrl(),
                'is_local_only'   => $f->drive_file_id === null,
                'uploader'        => $f->uploader?->name,
                'created_at'      => $f->created_at->toIso8601String(),
            ]),
        ];
    }

    /**
     * ロール別レーン集計。タスクが主、書類はその内訳。
     * 「書類が散発タスクに分裂」を避け、タスク数だけを主指標にする。
     */
    private function laneSummary(Matter $matter): array
    {
        $roles = [
            RoleCode::Common, RoleCode::Seller, RoleCode::Buyer,
            RoleCode::CancelBank, RoleCode::SettingBank, RoleCode::Broker,
        ];

        $lanes = [];
        foreach ($roles as $role) {
            $tasks = $matter->tasks->where('role_code', $role);

            // このロールにタスクが何もないレーンは非表示（業務種別で対象外）
            if ($tasks->isEmpty()) {
                continue;
            }

            $lanes[] = [
                'role'         => $role->value,
                'role_label'   => $role->label(),
                'task_count'   => $tasks->count(),
                'task_done'    => $tasks->whereNotNull('completed_at')->count(),
                'task_overdue' => $tasks->filter(fn ($t) => $t->isOverdue())->count(),
            ];
        }
        return $lanes;
    }
}
