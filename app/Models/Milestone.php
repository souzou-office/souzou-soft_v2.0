<?php

namespace App\Models;

use App\Enums\MilestoneKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    protected $fillable = [
        'matter_id', 'key', 'name',
        'deadline_offset_days', 'deadline', 'completed_at',
    ];

    protected $casts = [
        'key'          => MilestoneKey::class,
        'deadline'     => 'date',
        'completed_at' => 'datetime',
    ];

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function isOverdue(): bool
    {
        return $this->completed_at === null
            && $this->deadline
            && $this->deadline->isBefore(now()->startOfDay());
    }

    public function daysUntilDeadline(): ?int
    {
        if (! $this->deadline) return null;
        return now()->startOfDay()->diffInDays($this->deadline->startOfDay(), false);
    }
}
