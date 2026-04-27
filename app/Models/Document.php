<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\DocumentKind;
use App\Enums\DocumentState;
use App\Enums\MilestoneKey;
use App\Enums\RoleCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 事件ごとの書類インスタンス。task の付属物ではなく独立エンティティ。
 *
 * 状態遷移は kind に応じて分岐:
 *   collection: not_started → requested → received → confirmed
 *   creation:   not_started → drafted → confirmed
 */
class Document extends Model
{
    protected $fillable = [
        'matter_id', 'definition_id',
        'code', 'name', 'kind', 'requested_from_role',
        'delivery_method', 'milestone_key', 'deadline',
        'state', 'is_held', 'confirmation_requires',
        'requested_at', 'received_at', 'drafted_at', 'confirmed_at',
        'updated_by_user_id', 'memo',
    ];

    protected $casts = [
        'kind'                  => DocumentKind::class,
        'requested_from_role'   => RoleCode::class,
        'delivery_method'       => DeliveryMethod::class,
        'milestone_key'         => MilestoneKey::class,
        'state'                 => DocumentState::class,
        'is_held'               => 'boolean',
        'confirmation_requires' => 'array',
        'deadline'              => 'date',
        'requested_at'          => 'datetime',
        'received_at'           => 'datetime',
        'drafted_at'            => 'datetime',
        'confirmed_at'          => 'datetime',
    ];

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(DocumentDefinition::class, 'definition_id');
    }

    public function isOverdue(): bool
    {
        return $this->deadline
            && $this->state !== DocumentState::Confirmed
            && $this->deadline->isBefore(now()->startOfDay());
    }

    public function daysUntilDeadline(): ?int
    {
        if (! $this->deadline) return null;
        return now()->startOfDay()->diffInDays($this->deadline->startOfDay(), false);
    }

    /**
     * 確定可能な状態か（依存書類が全て confirmed か）。
     * 依存解決は呼び出し側で Matter の documents コレクションを渡す形にする。
     */
    public function canConfirm(\Illuminate\Support\Collection $matterDocuments): bool
    {
        $required = $this->confirmation_requires ?? [];
        if (empty($required)) return true;

        foreach ($required as $code) {
            $dep = $matterDocuments->firstWhere('code', $code);
            if (! $dep || $dep->state !== DocumentState::Confirmed) {
                return false;
            }
        }
        return true;
    }
}
