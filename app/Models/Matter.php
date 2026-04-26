<?php

namespace App\Models;

use App\Enums\JobType;
use App\Enums\RoleCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 事件レコード（matter_files テーブル）。
 *
 * 仕様書 1.2.1 原則1: スコープは不動産登記決済に限定。
 * v1 の構造（job_type, parties[role_code], properties）を維持しつつ、
 * v2.0 で drive_folder_id を追加した。
 */
class Matter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'matter_files';

    protected $fillable = [
        'matter_number',
        'job_type',
        'received_at',
        'settlement_date',
        'main_user_id',
        'progress',
        'drive_folder_id',
        'meta',
    ];

    protected $casts = [
        'job_type'        => JobType::class,
        'received_at'     => 'date',
        'settlement_date' => 'datetime',
        'meta'            => 'array',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'job_id')->orderBy('display_order');
    }

    public function parties(): HasMany
    {
        return $this->hasMany(Party::class)->orderBy('display_order');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class)->orderBy('display_order');
    }

    public function mainUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'main_user_id');
    }

    /**
     * 当事者をロール別にグルーピング。テンプレ差込・サマリーバー表示で使う。
     */
    public function partiesByRole(): array
    {
        return $this->parties
            ->groupBy(fn (Party $p) => $p->role_code->value)
            ->all();
    }

    /**
     * 3.1.1 サマリーバーの「進捗（完了/全 + %）」用。
     */
    public function progressSummary(): array
    {
        $total = $this->tasks->count();
        $done  = $this->tasks->whereNotNull('completed_at')->count();
        return [
            'total'    => $total,
            'done'     => $done,
            'percent'  => $total === 0 ? 0 : (int) round(($done / $total) * 100),
        ];
    }

    /**
     * 3.1.1 サマリーバー「次工程」: 未完了 task のうち期日が最も近いもの。
     */
    public function nextTask(): ?Task
    {
        return $this->tasks
            ->whereNull('completed_at')
            ->sortBy(fn (Task $t) => $t->planned_date?->timestamp ?? PHP_INT_MAX)
            ->first();
    }

    public function partiesOf(RoleCode $role): \Illuminate\Support\Collection
    {
        return $this->parties->where('role_code', $role);
    }
}
