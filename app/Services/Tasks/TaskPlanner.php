<?php

namespace App\Services\Tasks;

use App\Enums\TaskStatus;
use App\Models\Matter;
use App\Models\Task;
use App\Models\TaskPlanningTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 5.2 主担当者の自動割当 + 5.3 期日（planned_date）の自動生成
 * + 並列処理モデル: task 間の依存関係 (DAG) を自動構築。
 *
 * 依存マップは「書類作成は途中で挟める」「前日連絡・決済は sequential」を表現:
 *   - task 7 (書類作成) → task 4 にだけ依存（収集 5/6/17/18 には依存しない、
 *     つまり収集と並走でドラフト可能）
 *   - task 9 (前日連絡) → task 7（書類作成完了が必要）
 *   - task 12 (決済・登記申請) → task 9, 10, 11（全前提タスク完了が必要）
 */
class TaskPlanner
{
    /**
     * task_code → 依存先 task_code 配列。
     * これに基づいて task_dependencies を自動生成する。
     */
    private const DEPENDENCY_MAP = [
        // 受任時系
        2  => [],          // 事件受任
        1  => [2],         // 当事者情報
        3  => [2],         // 書類受領（共通：仲介・物件情報）

        // 必要書類一覧送付（ここから収集タスクに分岐）
        4  => [3],

        // 収集タスク（並列で走る）
        5  => [4],         // 書類受領（売主）
        6  => [4],         // 書類受領（抹消金融機関）
        17 => [4],         // 書類受領（設定金融機関）
        18 => [4],         // 書類受領（買主）

        // 書類作成: 収集に依存しない（途中で挟めるため）
        7  => [4],
        8  => [4],         // スケジュール入力

        // 集約後の sequential 部分
        11 => [5, 6, 17, 18], // 本人確認書類等保存（収集が一通り終わってから）
        9  => [7],            // 前日連絡（書類作成完了が必要）
        10 => [7],            // 受付カード出力
        12 => [7, 9, 10, 11], // 決済・登記申請

        // 後処理（順次）
        13 => [12],
        14 => [12],
        15 => [14],
        16 => [15],
    ];

    public function plan(Matter $matter): void
    {
        if (! $matter->settlement_date) {
            return;
        }

        $templates = TaskPlanningTemplate::where('job_type', $matter->job_type->value)
            ->orderBy('display_order')
            ->get();

        DB::transaction(function () use ($matter, $templates) {
            // 1. tasks をまとめて作成
            $createdByCode = [];
            foreach ($templates as $tpl) {
                $plannedDate = $this->calculatePlannedDate(
                    $matter->settlement_date, $tpl->days_before_settlement
                );

                $task = Task::create([
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

                $createdByCode[$tpl->task_code] = $task;
            }

            // 2. 依存関係を構築（task_dependencies pivot）
            foreach ($createdByCode as $code => $task) {
                $deps = self::DEPENDENCY_MAP[$code] ?? [];
                foreach ($deps as $depCode) {
                    if (isset($createdByCode[$depCode])) {
                        $task->dependencies()->attach($createdByCode[$depCode]->id);
                    }
                }
            }
        });
    }

    private function calculatePlannedDate(Carbon $settlementDate, int $daysBeforeSettlement): Carbon
    {
        return $settlementDate->copy()->startOfDay()->addDays($daysBeforeSettlement);
    }
}
