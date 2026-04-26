<?php

namespace App\Models;

use App\Enums\RoleCode;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 工程レコード。仕様書 8.2.1 / 5.x の中核。
 *
 * v2.0 の主役テーブル。assignee_user_id で「誰が何をやるか」を、
 * planned_date で「いつまでにやるか」を持たせ、UIの可視化レイヤーから
 * 直接参照される。
 */
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id', 'task_code', 'task_type_code', 'role_code',
        'display_order', 'task_name',
        'assignee_user_id', 'assigned_by_user_id', 'assigned_at',
        'planned_date', 'status', 'reviewer_user_id',
        'completed_at', 'payload',
    ];

    protected $casts = [
        'role_code'    => RoleCode::class,
        'status'       => TaskStatus::class,
        'assigned_at'  => 'datetime',
        'planned_date' => 'date',
        'completed_at' => 'datetime',
        'payload'      => 'array',
    ];

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class, 'job_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at');
    }

    public function formInputs(): HasMany
    {
        return $this->hasMany(TaskFormInput::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isCompleted()
            && $this->planned_date instanceof Carbon
            && $this->planned_date->isBefore(now()->startOfDay());
    }

    public function daysUntilDue(): ?int
    {
        if (! $this->planned_date instanceof Carbon) {
            return null;
        }
        return now()->startOfDay()->diffInDays($this->planned_date->startOfDay(), false);
    }

    /**
     * 3.1.2 ステッパーの円の状態色を決定する。
     * UI 側で同じロジックを書き直すと不整合が起きるので、このメソッドが
     * 一次情報源（single source of truth）。
     */
    public function visualState(): string
    {
        if ($this->isCompleted()) return 'completed';
        if ($this->isOverdue())   return 'overdue';
        if ($this->status === TaskStatus::InProgress) return 'in_progress';

        return 'not_started';
    }
}
