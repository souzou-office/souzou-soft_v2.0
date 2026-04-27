<?php

namespace App\Services\Documents;

use App\Enums\DocumentState;
use App\Models\Document;
use App\Models\DocumentDefinition;
use App\Models\Matter;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * 事件作成時に document_definitions から書類を生成し、
 * linked_task_code に基づいて task_documents pivot を自動構築する。
 *
 * これで「書類受領（売主）」1 タスク内に売主の収集系書類が束ねられ、
 * UI ではタスクが主役・書類はその内訳として表示される。
 */
class DocumentPlanner
{
    public function plan(Matter $matter): void
    {
        if (! $matter->settlement_date) {
            return;
        }

        $definitions = DocumentDefinition::where('is_active', true)->get();
        // task_code → Task インスタンスのマップ（pivot 紐付けで使う）
        $tasksByCode = $matter->tasks()->get()->keyBy('task_code');

        DB::transaction(function () use ($matter, $definitions, $tasksByCode) {
            foreach ($definitions as $def) {
                if (! $def->appliesTo($matter->job_type->value)) {
                    continue;
                }

                $deadline = $matter->settlement_date->copy()
                    ->startOfDay()
                    ->addDays($def->deadline_offset_days);

                $document = Document::updateOrCreate(
                    ['matter_id' => $matter->id, 'code' => $def->code],
                    [
                        'definition_id'         => $def->id,
                        'name'                  => $def->name,
                        'kind'                  => $def->kind,
                        'requested_from_role'   => $def->requested_from_role,
                        'delivery_method'       => $def->delivery_method,
                        'milestone_key'         => $def->milestone_key,
                        'deadline'              => $deadline,
                        'state'                 => DocumentState::NotStarted,
                        'is_held'               => false,
                        'confirmation_requires' => $def->confirmation_requires,
                    ]
                );

                // linked_task_code に対応する task に紐付け
                if ($def->linked_task_code !== null && $tasksByCode->has($def->linked_task_code)) {
                    /** @var Task $task */
                    $task = $tasksByCode[$def->linked_task_code];
                    $task->documents()->syncWithoutDetaching([$document->id]);
                }
            }
        });
    }
}
