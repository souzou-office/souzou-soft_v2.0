<?php

namespace App\Services\Tasks;

use App\Models\Matter;
use App\Models\Task;

/**
 * 5 フェーズ（受任 / 書類準備 / 決済前 / 決済 / 後処理）と task_code の対応を解決。
 * 「現在のフェーズ」「フェーズ別 進捗」を計算する。
 */
class PhaseResolver
{
    /** @return array<int, array{key:string,label:string,codes:int[]}> */
    public function definitions(): array
    {
        return config('matter.phases', []);
    }

    /**
     * Matter 全体のフェーズ別集計。各フェーズについて total/done を返す。
     *
     * @return array<int, array{key:string,label:string,codes:int[],total:int,done:int}>
     */
    public function summarize(Matter $matter): array
    {
        $tasks = $matter->tasks;
        return array_map(function (array $phase) use ($tasks) {
            $inPhase = $tasks->whereIn('task_code', $phase['codes']);
            return $phase + [
                'total' => $inPhase->count(),
                'done'  => $inPhase->whereNotNull('completed_at')->count(),
            ];
        }, $this->definitions());
    }

    /**
     * 「今のフェーズ」= 未完了 task のうち最も進行順の早いものが属するフェーズ。
     * すべて完了している場合は最後のフェーズ、未着手しかなければ最初。
     */
    public function currentPhase(Matter $matter): ?array
    {
        $next = $matter->tasks
            ->whereNull('completed_at')
            ->sortBy('display_order')
            ->first();

        if (! $next) {
            return $this->definitions()[count($this->definitions()) - 1] ?? null;
        }

        foreach ($this->definitions() as $phase) {
            if (in_array($next->task_code, $phase['codes'], true)) {
                return $phase;
            }
        }
        return null;
    }

    public function phaseKeyForCode(int $taskCode): ?string
    {
        foreach ($this->definitions() as $phase) {
            if (in_array($taskCode, $phase['codes'], true)) {
                return $phase['key'];
            }
        }
        return null;
    }
}
