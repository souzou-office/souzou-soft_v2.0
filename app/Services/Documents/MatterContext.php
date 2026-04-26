<?php

namespace App\Services\Documents;

use App\Enums\RoleCode;
use App\Models\Matter;

/**
 * 6.3 構造化された差込変数。
 *
 * テンプレからは ${売主.0.氏名}、${物件.0.所在} のようなドット記法で
 * アクセスする。phpword の単純差込（${var}）は階層をサポートしないため、
 * このクラスで階層を平坦化（${売主.0.氏名} → 値 のフラットマップに変換）する。
 */
class MatterContext
{
    public function build(Matter $matter): array
    {
        $context = [
            '事件.番号'         => $matter->matter_number,
            '事件.受任日'       => optional($matter->received_at)->format('Y/m/d'),
            '事件.決済日'       => optional($matter->settlement_date)->format('Y/m/d H:i'),
            '事件.業務種別'     => $matter->job_type?->value,
            '事件.業務種別名'   => $matter->job_type?->label(),
        ];

        // 当事者を role 別に配列インデックス化
        $rolesMap = [
            RoleCode::Seller       => '売主',
            RoleCode::Buyer        => '買主',
            RoleCode::CancelBank   => '抹消金融機関',
            RoleCode::SettingBank  => '設定金融機関',
            RoleCode::Broker       => '仲介',
        ];

        foreach ($rolesMap as $role => $label) {
            $parties = $matter->partiesOf($role)->values();
            foreach ($parties as $i => $party) {
                $context["$label.$i.氏名"]     = $party->name;
                $context["$label.$i.住所"]     = $party->address;
                $context["$label.$i.電話"]     = $party->tel;
                $context["$label.$i.郵便番号"] = $party->postal_code;
            }
        }

        foreach ($matter->properties as $i => $property) {
            $context["物件.$i.所在"] = $property->location;
            $context["物件.$i.地番"] = $property->parcel_number;
            $context["物件.$i.地目"] = $property->land_category;
            $context["物件.$i.地積"] = $property->area;
        }

        return $context;
    }
}
