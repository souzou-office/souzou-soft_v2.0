<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadedFile extends Model
{
    protected $fillable = [
        'matter_id', 'original_name', 'mime_type', 'size',
        'drive_file_id', 'local_path', 'uploaded_by_user_id',
    ];

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function driveUrl(): ?string
    {
        return $this->drive_file_id
            ? "https://drive.google.com/file/d/{$this->drive_file_id}/view"
            : null;
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
        return number_format($bytes / 1024 / 1024, 1) . ' MB';
    }
}
