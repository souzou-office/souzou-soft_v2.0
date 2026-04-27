<?php

namespace Database\Seeders;

use App\Enums\JobType;
use App\Enums\RoleCode;
use App\Models\TaskPlanningTemplate;
use Illuminate\Database\Seeder;

/**
 * 付録A 工程テンプレマスタ初期データ。
 *
 * task-code と standard relative days （A.1）を、業務種別ごとに
 * 該当する工程セット（A.2）と組み合わせて生成する。
 */
class TaskPlanningTemplateSeeder extends Seeder
{
    /**
     * task-code => [name, days_before_settlement, task_type_code, role_code]
     */
    private const TASKS = [
        1  => ['当事者情報',                       -30, 1, RoleCode::Common],
        2  => ['事件受任',                         -30, 1, RoleCode::Common],
        3  => ['書類受領',                         -21, 2, RoleCode::Common],
        4  => ['必要書類一覧・見積書送付',         -14, 3, RoleCode::Common],
        5  => ['書類受領（売主）',                 -7,  2, RoleCode::Seller],
        6  => ['書類受領（抹消金融機関）',         -7,  2, RoleCode::CancelBank],
        7  => ['書類作成',                         -5,  4, RoleCode::Common],
        8  => ['スケジュール入力',                 -3,  1, RoleCode::Common],
        9  => ['前日連絡',                         -1,  1, RoleCode::Common],
        10 => ['受付カード出力',                   -1,  4, RoleCode::Common],
        11 => ['本人確認書類等保存',               -1,  2, RoleCode::Common],
        12 => ['決済・登記申請',                    0,  5, RoleCode::Common],
        13 => ['FAX送付状出力・受領書（抹消金融機関）', 1, 4, RoleCode::CancelBank],
        14 => ['送付状・受領書・宛名シール・権利証表紙出力', 3, 4, RoleCode::Common],
        15 => ['書類送付',                          5,  3, RoleCode::Common],
        16 => ['受領書保存',                       14,  2, RoleCode::Common],
        17 => ['書類受領（設定金融機関）',         -2,  2, RoleCode::SettingBank],
        18 => ['書類受領（買主）',                 -3,  2, RoleCode::Buyer],
    ];

    /**
     * 業務種別ごとに発生する task-code セット。
     * 借換 (10) には売主関係の 5 が出ない、名変単独 (9) には 6/13 が出ない、
     * といった構造を表現する。
     */
    private const JOB_TYPE_TASKS = [
        JobType::Transfer->value              => [1,2,3,4,5,7,8,9,10,11,12,14,15,16,18],
        JobType::Cancel->value                => [1,2,3,4,6,7,8,9,10,11,12,13,14,15,16],
        JobType::CancelTransfer->value        => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,18],
        JobType::CancelTransferSetting->value => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18],
        JobType::TransferSetting->value       => [1,2,3,4,5,7,8,9,10,11,12,14,15,16,17,18],
        JobType::Setting->value               => [1,2,3,4,7,8,9,10,11,12,14,15,16,17,18],
        JobType::CancelSetting->value         => [1,2,3,4,6,7,8,9,10,11,12,13,14,15,16,17,18],
        JobType::NameChangeFull->value        => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18],
        JobType::NameChange->value            => [1,2,3,4,7,8,9,10,11,12,14,15,16],
        JobType::Refinance->value             => [1,2,3,4,6,7,8,9,10,11,12,13,14,15,16,17,18],
        JobType::Other->value                 => [1,2,3,4,7,8,9,10,11,12,14,15,16],
    ];

    public function run(): void
    {
        foreach (self::JOB_TYPE_TASKS as $jobType => $taskCodes) {
            $order = 10;
            foreach ($taskCodes as $code) {
                [$name, $days, $typeCode, $role] = self::TASKS[$code];
                TaskPlanningTemplate::updateOrCreate(
                    [
                        'job_type'      => $jobType,
                        'task_code'     => $code,
                        'role_code'     => $role->value,
                        'display_order' => $order,
                    ],
                    [
                        'days_before_settlement' => $days,
                        'task_name'              => $name,
                        'task_type_code'         => $typeCode,
                    ],
                );
                $order += 10;
            }
        }
    }
}
