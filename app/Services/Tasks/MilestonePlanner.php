<?php

namespace App\Services\Tasks;

use App\Enums\MilestoneKey;
use App\Models\Matter;
use App\Models\Milestone;
use Illuminate\Support\Facades\DB;

/**
 * 事件作成時に 4 マイルストーンを生成する。
 *  pre_settlement_postal -10 / postal_returned -2 / financial_ready -2 / all_confirmed -1
 *
 * オフセットは MilestoneKey::defaultOffsetDays() のデフォルト値を使うが、
 * 案件特性で調整できるよう Matter.meta['milestone_offsets'] で上書き可能。
 */
class MilestonePlanner
{
    public function plan(Matter $matter): void
    {
        if (! $matter->settlement_date) {
            return;
        }

        $overrides = $matter->meta['milestone_offsets'] ?? [];

        DB::transaction(function () use ($matter, $overrides) {
            foreach (MilestoneKey::ordered() as $key) {
                $offset = $overrides[$key->value] ?? $key->defaultOffsetDays();
                $deadline = $matter->settlement_date->copy()
                    ->startOfDay()
                    ->addDays($offset);

                Milestone::updateOrCreate(
                    ['matter_id' => $matter->id, 'key' => $key->value],
                    [
                        'name'                 => $key->label(),
                        'deadline_offset_days' => $offset,
                        'deadline'             => $deadline,
                    ]
                );
            }
        });
    }
}
