<?php

namespace App\Models;

use App\Enums\JobType;
use App\Enums\RoleCode;
use Illuminate\Database\Eloquent\Model;

/**
 * 工程テンプレマスタ。仕様書 5.3.1 / 付録A。
 *
 * 事件作成時にこのテーブルを job_type で絞り込み、display_order 順に
 * 取り出して tasks レコードを生成する。期日は決済日 + days_before_settlement。
 */
class TaskPlanningTemplate extends Model
{
    protected $fillable = [
        'job_type', 'task_code', 'role_code',
        'days_before_settlement', 'display_order',
        'task_name', 'task_type_code',
    ];

    protected $casts = [
        'job_type'  => JobType::class,
        'role_code' => RoleCode::class,
    ];
}
