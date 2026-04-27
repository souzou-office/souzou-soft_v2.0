<?php

namespace App\Services\Documents;

use App\Enums\DocumentState;
use App\Models\Document;
use App\Models\DocumentDefinition;
use App\Models\Matter;
use Illuminate\Support\Facades\DB;

/**
 * 事件作成時に document_definitions から書類インスタンスを生成する。
 *
 * applies_to_job_types で対象判定し、deadline_offset_days を決済日に
 * 加算して deadline を絶対日付に確定する。
 */
class DocumentPlanner
{
    public function plan(Matter $matter): void
    {
        if (! $matter->settlement_date) {
            return;
        }

        $definitions = DocumentDefinition::where('is_active', true)->get();

        DB::transaction(function () use ($matter, $definitions) {
            foreach ($definitions as $def) {
                if (! $def->appliesTo($matter->job_type->value)) {
                    continue;
                }

                $deadline = $matter->settlement_date->copy()
                    ->startOfDay()
                    ->addDays($def->deadline_offset_days);

                Document::updateOrCreate(
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
            }
        });
    }
}
