<?php

namespace App\Services\Tasks;

use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * 5.6 未割当検知 + 5.6.1 段階的警告ルール。
 *
 * 未割当 task を抽出し、期日までの日数に応じて警告レベルを返す。
 *  7日以上: なし
 *  3〜6日:  yellow
 *  2日以内: red
 *  期日超過: critical
 */
class AssignmentDetector
{
    public const LEVEL_NONE = 'none';
    public const LEVEL_YELLOW = 'yellow';
    public const LEVEL_RED = 'red';
    public const LEVEL_CRITICAL = 'critical';

    /**
     * Matter 単位で未割当 task の数を警告レベル別に集計。
     */
    public function summarize(int $matterId): array
    {
        $unassigned = Task::query()
            ->where('job_id', $matterId)
            ->whereNull('assignee_user_id')
            ->whereNull('completed_at')
            ->get();

        return $this->bucketByLevel($unassigned);
    }

    public function levelFor(?\Illuminate\Support\Carbon $plannedDate): string
    {
        if (! $plannedDate) {
            return self::LEVEL_NONE;
        }
        $days = now()->startOfDay()->diffInDays($plannedDate->startOfDay(), false);

        return match (true) {
            $days < 0   => self::LEVEL_CRITICAL,
            $days <= 2  => self::LEVEL_RED,
            $days <= 6  => self::LEVEL_YELLOW,
            default     => self::LEVEL_NONE,
        };
    }

    private function bucketByLevel(Collection $tasks): array
    {
        $buckets = [
            self::LEVEL_CRITICAL => 0,
            self::LEVEL_RED      => 0,
            self::LEVEL_YELLOW   => 0,
            self::LEVEL_NONE     => 0,
        ];

        foreach ($tasks as $task) {
            $buckets[$this->levelFor($task->planned_date)]++;
        }

        return $buckets;
    }
}
