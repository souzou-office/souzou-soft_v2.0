<?php

namespace Database\Seeders;

use App\Enums\DeliveryMethod;
use App\Enums\DocumentKind;
use App\Enums\MilestoneKey;
use App\Enums\RoleCode;
use App\Models\DocumentDefinition;
use Illuminate\Database\Seeder;

/**
 * 不動産売買決済の標準書類群。業務モデルの核心。
 *
 * 凡例:
 *   kind: collection (外部から取り寄せ) / creation (事務所内で作成)
 *   delivery_method: postal_to_party (郵送往復) / handover_at_settlement / bank_receipt / office_internal
 *   milestone_key: PreSettlementPostal -10 / PostalReturned -2 / FinancialReady -2 / AllConfirmed -1
 *
 * 業務種別 (job_type 1〜11):
 *   1=移転 / 2=抹消 / 3=抹消・移転 / 4=抹消・移転・設定 / 5=移転・設定
 *   6=設定 / 7=抹消・設定 / 8=名変・抹消・移転・設定 / 9=名変
 *   10=借換 / 11=その他
 */
class DocumentDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $defs = [
            // ============================================================
            // 収集系（売主側）
            // ============================================================
            [
                'code' => 'seller_inkan',
                'name' => '売主印鑑証明書',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::Seller,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => -7,
                'milestone_key' => MilestoneKey::PostalReturned,
                'applies_to_job_types' => [1, 3, 4, 5, 8, 9],
                'needs_seal' => false,
            ],
            [
                'code' => 'seller_jyuminhyo',
                'name' => '売主住民票',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::Seller,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => -7,
                'milestone_key' => MilestoneKey::PostalReturned,
                'applies_to_job_types' => [1, 3, 4, 5, 8, 9],
            ],
            [
                'code' => 'seller_certificate_of_registration',
                'name' => '登記識別情報通知（売主）',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::Seller,
                'delivery_method' => DeliveryMethod::HandoverAtSettlement,
                'deadline_offset_days' => 0,
                'milestone_key' => MilestoneKey::AllConfirmed,
                'applies_to_job_types' => [1, 3, 4, 5, 8],
            ],
            [
                'code' => 'seller_attorney_letter',
                'name' => '売主用委任状（押印往復）',
                'kind' => DocumentKind::Creation,
                'requested_from_role' => RoleCode::Seller,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => -10,
                'milestone_key' => MilestoneKey::PreSettlementPostal,
                'applies_to_job_types' => [1, 3, 4, 5, 8, 9],
                'needs_seal' => true,
                'confirmation_requires' => ['sale_contract'],
            ],
            [
                'code' => 'seller_registration_cause_doc',
                'name' => '登記原因証明情報（売主用押印分）',
                'kind' => DocumentKind::Creation,
                'requested_from_role' => RoleCode::Seller,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => -10,
                'milestone_key' => MilestoneKey::PreSettlementPostal,
                'applies_to_job_types' => [1, 3, 4, 5, 8],
                'needs_seal' => true,
                'confirmation_requires' => ['sale_contract'],
            ],

            // ============================================================
            // 収集系（買主側）
            // ============================================================
            [
                'code' => 'buyer_jyuminhyo',
                'name' => '買主住民票',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::Buyer,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => -3,
                'milestone_key' => MilestoneKey::AllConfirmed,
                'applies_to_job_types' => [1, 3, 4, 5, 8],
            ],
            [
                'code' => 'buyer_inkan',
                'name' => '買主印鑑証明書',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::Buyer,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => -3,
                'milestone_key' => MilestoneKey::AllConfirmed,
                'applies_to_job_types' => [4, 5, 6, 7, 8, 10],
            ],
            [
                'code' => 'buyer_attorney_letter',
                'name' => '買主用委任状',
                'kind' => DocumentKind::Creation,
                'requested_from_role' => RoleCode::Buyer,
                'delivery_method' => DeliveryMethod::HandoverAtSettlement,
                'deadline_offset_days' => -1,
                'milestone_key' => MilestoneKey::AllConfirmed,
                'applies_to_job_types' => [1, 3, 4, 5, 6, 7, 8, 10],
                'needs_seal' => true,
            ],

            // ============================================================
            // 収集系（既存抵当権者 = 抹消金融機関）
            // ============================================================
            [
                'code' => 'existing_mortgage_release',
                'name' => '抹消書類一式（既存抵当権者）',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::CancelBank,
                'delivery_method' => DeliveryMethod::BankReceipt,
                'deadline_offset_days' => -3,
                'milestone_key' => MilestoneKey::FinancialReady,
                'applies_to_job_types' => [2, 3, 4, 7, 8, 10],
            ],
            [
                'code' => 'existing_mortgage_release_letter',
                'name' => '抵当権抹消委任状（既存銀行）',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::CancelBank,
                'delivery_method' => DeliveryMethod::BankReceipt,
                'deadline_offset_days' => -3,
                'milestone_key' => MilestoneKey::FinancialReady,
                'applies_to_job_types' => [2, 3, 4, 7, 8, 10],
            ],
            [
                'code' => 'existing_mortgage_id_notice',
                'name' => '登記識別情報通知（既存抵当権者）',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::CancelBank,
                'delivery_method' => DeliveryMethod::BankReceipt,
                'deadline_offset_days' => -3,
                'milestone_key' => MilestoneKey::FinancialReady,
                'applies_to_job_types' => [2, 3, 4, 7, 8, 10],
            ],

            // ============================================================
            // 収集系（新規抵当権者 = 設定金融機関）
            // ============================================================
            [
                'code' => 'new_mortgage_contract',
                'name' => '設定契約書（新規銀行）',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::SettingBank,
                'delivery_method' => DeliveryMethod::BankReceipt,
                'deadline_offset_days' => -2,
                'milestone_key' => MilestoneKey::FinancialReady,
                'applies_to_job_types' => [4, 5, 6, 7, 8, 10],
            ],
            [
                'code' => 'new_mortgage_attorney_letter',
                'name' => '設定委任状（新規銀行）',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::SettingBank,
                'delivery_method' => DeliveryMethod::BankReceipt,
                'deadline_offset_days' => -2,
                'milestone_key' => MilestoneKey::FinancialReady,
                'applies_to_job_types' => [4, 5, 6, 7, 8, 10],
            ],
            [
                'code' => 'new_mortgagee_inkan',
                'name' => '印鑑証明書（新規銀行）',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::SettingBank,
                'delivery_method' => DeliveryMethod::BankReceipt,
                'deadline_offset_days' => -2,
                'milestone_key' => MilestoneKey::FinancialReady,
                'applies_to_job_types' => [4, 5, 6, 7, 8, 10],
            ],

            // ============================================================
            // 収集系（仲介・物件情報）
            // ============================================================
            [
                'code' => 'sale_contract',
                'name' => '売買契約書写し',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::Broker,
                'delivery_method' => DeliveryMethod::Other,
                'deadline_offset_days' => -14,
                'milestone_key' => MilestoneKey::PreSettlementPostal,
                'applies_to_job_types' => [1, 3, 4, 5, 8],
            ],
            [
                'code' => 'tax_evaluation',
                'name' => '評価証明書',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::Common,
                'delivery_method' => DeliveryMethod::OfficeInternal,
                'deadline_offset_days' => -5,
                'milestone_key' => MilestoneKey::AllConfirmed,
                'applies_to_job_types' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
            ],
            [
                'code' => 'property_register',
                'name' => '登記情報',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::Common,
                'delivery_method' => DeliveryMethod::OfficeInternal,
                'deadline_offset_days' => -14,
                'milestone_key' => MilestoneKey::PreSettlementPostal,
                'applies_to_job_types' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
            ],
            [
                'code' => 'property_map',
                'name' => '公図',
                'kind' => DocumentKind::Collection,
                'requested_from_role' => RoleCode::Common,
                'delivery_method' => DeliveryMethod::OfficeInternal,
                'deadline_offset_days' => -14,
                'milestone_key' => MilestoneKey::PreSettlementPostal,
                'applies_to_job_types' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
            ],

            // ============================================================
            // 作成系（事務所内 / recast 管轄の周辺書類）
            // ============================================================
            [
                'code' => 'required_docs_list_seller',
                'name' => '必要書類一覧（売主用）',
                'kind' => DocumentKind::Creation,
                'requested_from_role' => RoleCode::Seller,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => -14,
                'milestone_key' => MilestoneKey::PreSettlementPostal,
                'applies_to_job_types' => [1, 3, 4, 5, 8, 9],
            ],
            [
                'code' => 'required_docs_list_buyer',
                'name' => '必要書類一覧（買主用）',
                'kind' => DocumentKind::Creation,
                'requested_from_role' => RoleCode::Buyer,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => -14,
                'milestone_key' => MilestoneKey::PreSettlementPostal,
                'applies_to_job_types' => [1, 3, 4, 5, 6, 7, 8, 10],
            ],
            [
                'code' => 'cover_letter_seller',
                'name' => '送付状（売主向け）',
                'kind' => DocumentKind::Creation,
                'requested_from_role' => RoleCode::Seller,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => -10,
                'milestone_key' => MilestoneKey::PreSettlementPostal,
                'applies_to_job_types' => [1, 3, 4, 5, 8, 9],
                'confirmation_requires' => ['seller_attorney_letter'],
            ],
            [
                'code' => 'receipt_letter',
                'name' => '受領書',
                'kind' => DocumentKind::Creation,
                'requested_from_role' => RoleCode::Common,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => 3,
                'milestone_key' => null,
                'applies_to_job_types' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
            ],
            [
                'code' => 'completion_report',
                'name' => '完了報告書',
                'kind' => DocumentKind::Creation,
                'requested_from_role' => RoleCode::Common,
                'delivery_method' => DeliveryMethod::PostalToParty,
                'deadline_offset_days' => 14,
                'milestone_key' => null,
                'applies_to_job_types' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
            ],
        ];

        foreach ($defs as $row) {
            // enum を value に変換
            $payload = [
                'name' => $row['name'],
                'kind' => $row['kind']->value,
                'requested_from_role' => $row['requested_from_role']->value,
                'delivery_method' => $row['delivery_method']->value,
                'deadline_offset_days' => $row['deadline_offset_days'],
                'milestone_key' => $row['milestone_key']?->value,
                'applies_to_job_types' => $row['applies_to_job_types'],
                'confirmation_requires' => $row['confirmation_requires'] ?? null,
                'needs_seal' => $row['needs_seal'] ?? false,
                'is_active' => true,
            ];

            DocumentDefinition::updateOrCreate(['code' => $row['code']], $payload);
        }
    }
}
