<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Property extends Model
{
    protected $fillable = [
        'matter_id', 'display_order', 'location',
        'parcel_number', 'land_category', 'area', 'extra',
    ];

    protected $casts = ['extra' => 'array'];

    public function matter(): BelongsTo
    {
        return $this->belongsTo(Matter::class);
    }
}
