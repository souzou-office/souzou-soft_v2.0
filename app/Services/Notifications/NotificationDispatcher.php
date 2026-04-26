<?php

namespace App\Services\Notifications;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * 5.4 / B.1 通知の生成・配信。
 *
 * Notification レコードを作成し、ユーザー設定（notification_preferences）に
 * 従ってメール送信フラグを立てる。Webhook 化を将来追加可能な構造。
 */
class NotificationDispatcher
{
    public function dispatch(
        User $target,
        NotificationType $type,
        array $payload,
        ?int $relatedJobId = null,
        ?int $relatedTaskId = null,
    ): Notification {
        $title = $this->renderTemplate($type->subjectTemplate(), $payload);
        $body  = $this->renderTemplate($type->bodyTemplate(), $payload);

        $notification = Notification::create([
            'target_user_id'  => $target->id,
            'type'            => $type,
            'title'           => $title,
            'body'            => $body,
            'related_job_id'  => $relatedJobId,
            'related_task_id' => $relatedTaskId,
            'payload'         => $payload,
            'is_read'         => false,
            'sent_email'      => false,
        ]);

        if ($this->shouldSendEmail($target, $type)) {
            $this->sendEmail($target, $title, $body);
            $notification->update(['sent_email' => true]);
        }

        return $notification;
    }

    /**
     * task_assigned 通知を簡便に発火するヘルパー。
     */
    public function taskAssigned(Task $task): ?Notification
    {
        if (! $task->assignee_user_id || ! $task->assigned_by_user_id) {
            return null;
        }
        if ($task->assignee_user_id === $task->assigned_by_user_id) {
            return null; // 自己割当（5.2 自動割当）には通知不要
        }

        $assignee = $task->assignee;
        $assigner = $task->assignedBy;
        $matter   = $task->matter;

        return $this->dispatch(
            $assignee,
            NotificationType::TaskAssigned,
            [
                '割当人名'        => $assigner?->name ?? '',
                '事件番号'        => $matter?->matter_number ?? '',
                '工程名'          => $task->task_name,
                'planned_date'    => optional($task->planned_date)->format('Y-m-d'),
            ],
            relatedJobId: $matter?->id,
            relatedTaskId: $task->id,
        );
    }

    private function shouldSendEmail(User $user, NotificationType $type): bool
    {
        if (! $type->sendsEmail()) {
            return false;
        }
        $pref = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('type', $type->value)
            ->first();
        return $pref?->email ?? true;
    }

    private function sendEmail(User $user, string $subject, string $body): void
    {
        // 実装は Mailable 化するが、ここではプレーンテキストで送信。
        Mail::raw($body, function ($message) use ($user, $subject) {
            $message->to($user->email)->subject($subject);
        });
    }

    private function renderTemplate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }
        return $template;
    }
}
