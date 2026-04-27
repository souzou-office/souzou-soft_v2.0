<?php

namespace App\Enums;

enum DocumentKind: string
{
    case Collection = 'collection'; // 収集系（外部から取り寄せ）
    case Creation   = 'creation';   // 作成系（事務所内で作る）

    public function label(): string
    {
        return match ($this) {
            self::Collection => '収集',
            self::Creation   => '作成',
        };
    }
}
