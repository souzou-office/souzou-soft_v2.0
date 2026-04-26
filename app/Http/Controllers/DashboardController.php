<?php

namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use App\Services\Tasks\PhaseResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ホーム画面（事件一覧）。3.2 進捗表示強化版。
 *
 * 列構成（進捗中心）: 事件番号 / 依頼元 / 売主→買主 / 決済日 /
 *                     ステッパー / % / 次工程+期日 / 主担当(select)
 */
class DashboardController extends Controller
{
    public function __construct(private readonly PhaseResolver $phases) {}

    public function index(Request $request): Response
    {
        $matters = Matter::query()
            ->with(['mainUser', 'tasks.assignee', 'parties'])
            ->orderBy('settlement_date')
            ->paginate(30)
            ->through(fn (Matter $matter) => $this->serialize($matter));

        return Inertia::render('Home/Index', [
            'matters' => $matters,
            // フェーズバッジ表示用にマスタも送る（labels の翻訳を front で重複させない）
            'phases'  => $this->phases->definitions(),
            // 主担当の InlineSelect 用。全 active ユーザーを送る。
            'staff'   => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
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
                'task_code'    => $t->task_code,
                'name'         => $t->task_name,
                'state'        => $t->visualState(),
                'role_code'    => $t->role_code?->value,
                'assignee'     => $t->assignee?->name,
                'planned_date' => optional($t->planned_date)->format('Y-m-d'),
            ])->values(),
            'parties_summary' => $this->partiesSummary($matter),
            'broker_summary'  => $this->brokerSummary($matter),
            // フェーズバッジ用: 現在のフェーズキーと、各フェーズの進捗（done/total）
            'current_phase'   => $this->phases->currentPhase($matter)['key'] ?? null,
            'phase_summary'   => $this->phases->summarize($matter),
        ];
    }

    private function partiesSummary(Matter $matter): string
    {
        $sellers = $matter->partiesOf(RoleCode::Seller)->pluck('name')->take(2);
        $buyers  = $matter->partiesOf(RoleCode::Buyer)->pluck('name')->take(2);
        $left    = $sellers->isEmpty() ? '—' : $sellers->join('・');
        $right   = $buyers->isEmpty() ? '—' : $buyers->join('・');
        return "$left → $right";
    }

    /**
     * 依頼元（仲介業者・特定顧客）。
     * Broker ロールがあればそれを優先。なければ売主側に法人当事者があれば
     * その法人名（カチタス様等の特定顧客は売主側に入るケースを想定）。
     */
    private function brokerSummary(Matter $matter): ?string
    {
        $brokers = $matter->partiesOf(RoleCode::Broker);
        if ($brokers->isNotEmpty()) {
            return $brokers->pluck('name')->take(2)->join('・');
        }

        $corporateSeller = $matter->partiesOf(RoleCode::Seller)
            ->firstWhere('entity_type', 'corporate');
        return $corporateSeller?->name;
    }
}
