<?php

namespace App\Services\Tasks;

use App\Enums\TaskStatus;
use App\Models\Matter;
use App\Models\Task;
use App\Models\TaskPlanningTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 5.2 主担当者の自動割当 + 5.3 期日（planned_date）の自動生成。
 *
 * 事件作成（または業務種別変更）時に呼び出し、task_planning_templates から
 * 全 task を生成。assignee_user_id は事件主担当者を初期値としてコピー。
 */
class TaskPlanner
{
    /**
     * Matter の決済日と job_type からタスクを一括生成する。
     * 既存の task は削除しない（業務種別変更時の二重実行は呼び出し側で制御）。
     */
    public function plan(Matter $matter): void
    {
        if (! $matter->settlement_date) {
            return; // 決済日未確定なら期日逆算ができないので保留
        }

        $templates = TaskPlanningTemplate::where('job_type', $matter->job_type->value)
            ->orderBy('display_order')
            ->get();

        DB::transaction(function () use ($matter, $templates) {
            foreach ($templates as $tpl) {
                $plannedDate = $this->calculatePlannedDate($matter->settlement_date, $tpl->days_before_settlement);

                Task::create([
                    'job_id'             => $matter->id,
                    'task_code'          => $tpl->task_code,
                    'task_type_code'     => $tpl->task_type_code,
                    'role_code'          => $tpl->role_code->value,
                    'display_order'      => $tpl->display_order,
                    'task_name'          => $tpl->task_name,
                    'assignee_user_id'   => $matter->main_user_id,
                    'assigned_by_user_id'=> $matter->main_user_id,
                    'assigned_at'        => $matter->main_user_id ? now() : null,
                    'planned_date'       => $plannedDate,
                    'status'             => TaskStatus::NotStarted,
                ]);
            }
        });
    }

    private function calculatePlannedDate(Carbon $settlementDate, int $daysBeforeSettlement): Carbon
    {
        // 仕様書 5.3.1: 負の値=決済前、正=決済後
        return $settlementDate->copy()->startOfDay()->addDays($daysBeforeSettlement);
    }
}
