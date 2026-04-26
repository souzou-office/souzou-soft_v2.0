<?php

namespace App\Models;

use App\Enums\RoleCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Party extends Model
{
    protected $fillable = [
        'matter_id', 'role_code', 'display_order',
        'name', 'name_kana', 'entity_type',
        'postal_code', 'address', 'tel', 'email', 'extra',
    ];

    protected $casts = [
        'role_code' => RoleCode::class,
        'extra'     => 'array',
    ];

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }
}
