<?php

namespace App\Enums;

/**
 * 4 マイルストーン。「決済日 1 個」では現実が表現できないので分解する。
 *  PreSettlementPostal     -10 : 事前郵送書類確定・送付完了（押印往復のため）
 *  PostalReturned           -2 : 押印書類返送受領
 *  FinancialReady           -2 : 融資関係書類受領（銀行から）
 *  AllConfirmed             -1 : 全書類最終確定
 */
enum MilestoneKey: string
{
    case PreSettlementPostal = 'pre_settlement_postal';
    case PostalReturned      = 'postal_returned';
    case FinancialReady      = 'financial_ready';
    case AllConfirmed        = 'all_confirmed';

    public function label(): string
    {
        return match ($this) {
            self::PreSettlementPostal => '事前郵送確定',
            self::PostalReturned      => '押印返送受領',
            self::FinancialReady      => '融資受領',
            self::AllConfirmed        => '全書類確定',
        };
    }

    public function defaultOffsetDays(): int
    {
        return match ($this) {
            self::PreSettlementPostal => -10,
            self::PostalReturned      => -2,
            self::FinancialReady      => -2,
            self::AllConfirmed        => -1,
        };
    }

    /** @return self[] */
    public static function ordered(): array
    {
        return [
            self::PreSettlementPostal,
            self::PostalReturned,
            self::FinancialReady,
            self::AllConfirmed,
        ];
    }
}
