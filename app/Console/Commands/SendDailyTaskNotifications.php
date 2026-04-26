<?php

namespace App\Console\Commands;

use App\Enums\NotificationType;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;

/**
 * 5.4.2 / B.3 毎日朝7時に動作するスケジュールジョブ。
 *  - 期日3日前 (task_due_soon)
 *  - 期日超過 (task_overdue)
 * を一括送信する。
 */
class SendDailyTaskNotifications extends Command
{
    protected $signature = 'tasks:send-daily-notifications';
    protected $description = '期日3日前および期日超過のタスク通知を一括送信する';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $today      = now()->startOfDay();
        $threeDays  = $today->copy()->addDays(3);

        // 期日超過
        Task::query()
            ->with(['matter', 'assignee'])
            ->whereDate('planned_date', '<', $today)
            ->whereNot('status', TaskStatus::Completed)
            ->whereNotNull('assignee_user_id')
            ->chunk(200, function ($tasks) use ($dispatcher) {
                foreach ($tasks as $task) {
                    if (! $task->assignee) continue;
                    $dispatcher->dispatch(
                        $task->assignee,
                        NotificationType::TaskOverdue,
                        [
                            '事件番号'    => $task->matter?->matter_number ?? '',
                            '工程名'      => $task->task_name,
                            'planned_date'=> $task->planned_date->format('Y-m-d'),
                            '担当者名'    => $task->assignee->name,
                        ],
                        $task->job_id,
                        $task->id,
                    );
                }
            });

        // 期日3日前
        Task::query()
            ->with(['matter', 'assignee'])
            ->whereDate('planned_date', $threeDays)
            ->whereNot('status', TaskStatus::Completed)
            ->whereNotNull('assignee_user_id')
            ->chunk(200, function ($tasks) use ($dispatcher) {
                foreach ($tasks as $task) {
                    if (! $task->assignee) continue;
                    $dispatcher->dispatch(
                        $task->assignee,
                        NotificationType::TaskDueSoon,
                        [
                            '事件番号'    => $task->matter?->matter_number ?? '',
                            '工程名'      => $task->task_name,
                            'planned_date'=> $task->planned_date->format('Y-m-d'),
                            'days'        => 3,
                        ],
                        $task->job_id,
                        $task->id,
                    );
                }
            });

        $this->info('Daily notifications dispatched.');
        return self::SUCCESS;
    }
}
