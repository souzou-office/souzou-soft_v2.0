<?php

namespace App\Enums;

/**
 * ロール 0〜7（v1.x の role-code を維持）。付録Aの定義に従う。
 */
enum RoleCode: int
{
    case Common = 0;
    case Seller = 1;
    case Buyer = 2;
    case CancelBank = 3;
    case SettingBank = 4;
    case Broker = 5;
    case Other = 6;
    case Reviewer = 7;

    public function label(): string
    {
        return match ($this) {
            self::Common => '共通',
            self::Seller => '売主',
            self::Buyer => '買主',
            self::CancelBank => '抹消金融機関',
            self::SettingBank => '設定金融機関',
            self::Broker => '仲介',
            self::Other => 'その他',
            self::Reviewer => '確認者',
        };
    }
}
