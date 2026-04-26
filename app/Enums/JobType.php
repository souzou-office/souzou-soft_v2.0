<?php

namespace App\Enums;

/**
 * 業務種別 1〜11（v1.x の job-type を維持）。
 * 名称は事務所運用の慣用名に合わせる。
 */
enum JobType: int
{
    case Transfer = 1;                // 移転
    case Cancel = 2;                  // 抹消
    case CancelTransfer = 3;          // 抹消・移転
    case CancelTransferSetting = 4;   // 抹消・移転・設定
    case TransferSetting = 5;         // 移転・設定
    case Setting = 6;                 // 設定
    case CancelSetting = 7;           // 抹消・設定
    case NameChangeFull = 8;          // 名変・抹消・移転・設定
    case NameChange = 9;              // 名義変更のみ
    case Refinance = 10;              // 借換
    case Other = 11;                  // その他

    public function label(): string
    {
        return match ($this) {
            self::Transfer => '移転',
            self::Cancel => '抹消',
            self::CancelTransfer => '抹消・移転',
            self::CancelTransferSetting => '抹消・移転・設定',
            self::TransferSetting => '移転・設定',
            self::Setting => '設定',
            self::CancelSetting => '抹消・設定',
            self::NameChangeFull => '名変・抹消・移転・設定',
            self::NameChange => '名義変更',
            self::Refinance => '借換',
            self::Other => 'その他',
        };
    }
}
