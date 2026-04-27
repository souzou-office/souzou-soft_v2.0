<?php

namespace App\Enums;

/**
 * 書類の状態機械。
 * 収集系: not_started → requested → received → confirmed
 * 作成系: not_started → drafted → confirmed
 */
enum DocumentState: string
{
    case NotStarted = 'not_started';
    case Requested  = 'requested';   // 必要書類一覧送付済 / 依頼済
    case Received   = 'received';    // 受領
    case Drafted    = 'drafted';     // ドラフト作成済
    case Confirmed  = 'confirmed';   // 確定（チェック通過）

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => '未着手',
            self::Requested  => '依頼済',
            self::Received   => '受領',
            self::Drafted    => 'ドラフト',
            self::Confirmed  => '確定',
        };
    }

    /** UI で集計するときの色キー */
    public function tone(): string
    {
        return match ($this) {
            self::NotStarted => 'gray',
            self::Requested  => 'blue',
            self::Received   => 'cyan',
            self::Drafted    => 'amber',
            self::Confirmed  => 'emerald',
        };
    }
}
