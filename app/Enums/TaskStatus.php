<?php

namespace App\Enums;

enum TaskStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case AwaitingReview = 'awaiting_review';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => '未着手',
            self::InProgress => '進行中',
            self::Completed => '完了',
            self::AwaitingReview => '確認待ち',
        };
    }
}
