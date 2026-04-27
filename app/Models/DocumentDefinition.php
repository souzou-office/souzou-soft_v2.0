<?php

namespace App\Models;

use App\Enums\DeliveryMethod;
use App\Enums\DocumentKind;
use App\Enums\MilestoneKey;
use App\Enums\RoleCode;
use Illuminate\Database\Eloquent\Model;

/**
 * 書類マスタ。事件作成時にこの定義から documents インスタンスが生成される。
 */
class DocumentDefinition extends Model
{
    protected $fillable = [
        'code', 'name', 'kind', 'requested_from_role',
        'delivery_method', 'deadline_offset_days', 'milestone_key',
        'confirmation_requires', 'applies_to_job_types',
        'needs_seal', 'is_active',
    ];

    protected $casts = [
        'kind'                  => DocumentKind::class,
        'requested_from_role'   => RoleCode::class,
        'delivery_method'       => DeliveryMethod::class,
        'milestone_key'         => MilestoneKey::class,
        'confirmation_requires' => 'array',
        'applies_to_job_types'  => 'array',
        'needs_seal'            => 'boolean',
        'is_active'             => 'boolean',
    ];

    public function appliesTo(int $jobType): bool
    {
        return in_array($jobType, $this->applies_to_job_types ?? [], true);
    }
}
