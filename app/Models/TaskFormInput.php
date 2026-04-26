<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskFormInput extends Model
{
    protected $fillable = [
        'task_id', 'template_id', 'form_data',
        'generated_at', 'drive_file_id', 'local_path',
    ];

    protected $casts = [
        'form_data'    => 'array',
        'generated_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }
}
