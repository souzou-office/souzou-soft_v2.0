<?php

namespace App\Enums;

enum DeliveryMethod: string
{
    case PostalToParty        = 'postal_to_party';        // 当事者へ郵送して押印・返送
    case HandoverAtSettlement = 'handover_at_settlement'; // 決済当日その場で
    case OnlineSubmission     = 'online_submission';      // 法務局オンライン送信
    case BankReceipt          = 'bank_receipt';           // 銀行から受領
    case OfficeInternal       = 'office_internal';        // 事務所内で取得（公図・評価証明等）
    case Other                = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PostalToParty        => '郵送（押印往復）',
            self::HandoverAtSettlement => '決済当日',
            self::OnlineSubmission     => 'オンライン申請',
            self::BankReceipt          => '銀行受領',
            self::OfficeInternal       => '事務所取得',
            self::Other                => 'その他',
        };
    }
}
