<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'target_user_id', 'type', 'title', 'body',
        'related_job_id', 'related_task_id', 'payload',
        'is_read', 'sent_email',
    ];

    protected $casts = [
        'type'       => NotificationType::class,
        'payload'    => 'array',
        'is_read'    => 'boolean',
        'sent_email' => 'boolean',
    ];

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function relatedMatter(): BelongsTo
    {
        return $this->belongsTo(Matter::class, 'related_job_id');
    }

    public function relatedTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'related_task_id');
    }
}
