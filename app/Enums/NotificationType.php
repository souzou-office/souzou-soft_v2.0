<?php

namespace App\Enums;

enum NotificationType: string
{
    case AutoSettlement = 'auto_settlement';
    case TaskAssigned = 'task_assigned';
    case TaskCompleted = 'task_completed';
    case TaskOverdue = 'task_overdue';
    case TaskDueSoon = 'task_due_soon';
    case TaskReturned = 'task_returned';
    case TaskComment = 'task_comment';

    /** B.1 件名テンプレ */
    public function subjectTemplate(): string
    {
        return match ($this) {
            self::AutoSettlement => '【souzou-soft】明日決済予定の事件',
            self::TaskAssigned   => '【souzou-soft】タスクが割り当てられました',
            self::TaskCompleted  => '【souzou-soft】タスクが完了しました',
            self::TaskOverdue    => '【souzou-soft】期日超過のタスクがあります',
            self::TaskDueSoon    => '【souzou-soft】期日3日前のお知らせ',
            self::TaskReturned   => '【souzou-soft】タスクが差し戻されました',
            self::TaskComment    => '【souzou-soft】コメントが投稿されました',
        };
    }

    /** B.1 本文テンプレ。差込変数は {変数名} 形式。 */
    public function bodyTemplate(): string
    {
        return match ($this) {
            self::AutoSettlement => '明日{settlement_date}決済予定の事件は{count}件です。',
            self::TaskAssigned   => '{割当人名}様より{事件番号}{工程名}が割り当てられました。期日:{planned_date}',
            self::TaskCompleted  => '{完了者名}様が{事件番号}{工程名}を完了しました',
            self::TaskOverdue    => '{事件番号}{工程名}の期日{planned_date}を過ぎています。担当:{担当者名}',
            self::TaskDueSoon    => '{事件番号}{工程名}の期日は{planned_date}です。残り{days}日です',
            self::TaskReturned   => '{割当人名}様が{事件番号}{工程名}を未完了に戻しました',
            self::TaskComment    => '{投稿者名}様が{事件番号}{工程名}にコメントしました: {コメント本文}',
        };
    }

    /** B.3: メールも飛ばすか */
    public function sendsEmail(): bool
    {
        return match ($this) {
            self::TaskAssigned, self::TaskOverdue, self::TaskReturned,
            self::AutoSettlement => true,
            default => false,
        };
    }
}
