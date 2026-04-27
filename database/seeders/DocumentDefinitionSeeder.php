<?php

namespace Database\Seeders;

use App\Enums\DeliveryMethod;
use App\Enums\DocumentKind;
use App\Enums\MilestoneKey;
use App\Enums\RoleCode;
use App\Models\DocumentDefinition;
use Illuminate\Database\Seeder;

/**
 * 不動産売買決済の標準書類群。書類は task の sub-artifact として束ねる。
 *
 *  linked_task_code: 一括受領タスクと紐付ける（例: 売主の収集系は全て
 *  task_code=5「書類受領（売主）」に紐づく）。
 *  DocumentPlanner が事件作成時に task_documents pivot を自動生成。
 */
class DocumentDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $defs = [
            // 売主側 収集 → task 5 (書類受領（売主）)
            ['code'=>'seller_inkan', 'name'=>'売主印鑑証明書', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::Seller, 'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>-7, 'milestone_key'=>MilestoneKey::PostalReturned,
             'linked_task_code'=>5, 'applies_to_job_types'=>[1,3,4,5,8,9]],
            ['code'=>'seller_jyuminhyo', 'name'=>'売主住民票', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::Seller, 'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>-7, 'milestone_key'=>MilestoneKey::PostalReturned,
             'linked_task_code'=>5, 'applies_to_job_types'=>[1,3,4,5,8,9]],
            ['code'=>'seller_certificate_of_registration', 'name'=>'登記識別情報通知（売主）',
             'kind'=>DocumentKind::Collection, 'requested_from_role'=>RoleCode::Seller,
             'delivery_method'=>DeliveryMethod::HandoverAtSettlement,
             'deadline_offset_days'=>0, 'milestone_key'=>MilestoneKey::AllConfirmed,
             'linked_task_code'=>5, 'applies_to_job_types'=>[1,3,4,5,8]],

            // 売主側 作成（押印往復用）→ task 7 (書類作成)
            ['code'=>'seller_attorney_letter', 'name'=>'売主用委任状（押印往復）',
             'kind'=>DocumentKind::Creation, 'requested_from_role'=>RoleCode::Seller,
             'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>-10, 'milestone_key'=>MilestoneKey::PreSettlementPostal,
             'linked_task_code'=>7, 'needs_seal'=>true,
             'confirmation_requires'=>['sale_contract'], 'applies_to_job_types'=>[1,3,4,5,8,9]],
            ['code'=>'seller_registration_cause_doc', 'name'=>'登記原因証明情報（売主押印分）',
             'kind'=>DocumentKind::Creation, 'requested_from_role'=>RoleCode::Seller,
             'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>-10, 'milestone_key'=>MilestoneKey::PreSettlementPostal,
             'linked_task_code'=>7, 'needs_seal'=>true,
             'confirmation_requires'=>['sale_contract'], 'applies_to_job_types'=>[1,3,4,5,8]],

            // 買主側 収集 → task 18 (書類受領（買主）)
            ['code'=>'buyer_jyuminhyo', 'name'=>'買主住民票', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::Buyer, 'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>-3, 'milestone_key'=>MilestoneKey::AllConfirmed,
             'linked_task_code'=>18, 'applies_to_job_types'=>[1,3,4,5,8]],
            ['code'=>'buyer_inkan', 'name'=>'買主印鑑証明書', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::Buyer, 'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>-3, 'milestone_key'=>MilestoneKey::AllConfirmed,
             'linked_task_code'=>18, 'applies_to_job_types'=>[4,5,6,7,8,10]],

            // 買主側 作成 → task 7
            ['code'=>'buyer_attorney_letter', 'name'=>'買主用委任状', 'kind'=>DocumentKind::Creation,
             'requested_from_role'=>RoleCode::Buyer, 'delivery_method'=>DeliveryMethod::HandoverAtSettlement,
             'deadline_offset_days'=>-1, 'milestone_key'=>MilestoneKey::AllConfirmed,
             'linked_task_code'=>7, 'needs_seal'=>true, 'applies_to_job_types'=>[1,3,4,5,6,7,8,10]],

            // 既存抵当権者 収集 → task 6 (書類受領（抹消金融機関）)
            ['code'=>'existing_mortgage_release', 'name'=>'抹消書類一式', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::CancelBank, 'delivery_method'=>DeliveryMethod::BankReceipt,
             'deadline_offset_days'=>-3, 'milestone_key'=>MilestoneKey::FinancialReady,
             'linked_task_code'=>6, 'applies_to_job_types'=>[2,3,4,7,8,10]],
            ['code'=>'existing_mortgage_release_letter', 'name'=>'抵当権抹消委任状（既存銀行）',
             'kind'=>DocumentKind::Collection, 'requested_from_role'=>RoleCode::CancelBank,
             'delivery_method'=>DeliveryMethod::BankReceipt,
             'deadline_offset_days'=>-3, 'milestone_key'=>MilestoneKey::FinancialReady,
             'linked_task_code'=>6, 'applies_to_job_types'=>[2,3,4,7,8,10]],
            ['code'=>'existing_mortgage_id_notice', 'name'=>'登記識別情報通知（既存抵当権者）',
             'kind'=>DocumentKind::Collection, 'requested_from_role'=>RoleCode::CancelBank,
             'delivery_method'=>DeliveryMethod::BankReceipt,
             'deadline_offset_days'=>-3, 'milestone_key'=>MilestoneKey::FinancialReady,
             'linked_task_code'=>6, 'applies_to_job_types'=>[2,3,4,7,8,10]],

            // 新規抵当権者 収集 → task 17 (書類受領（設定金融機関）)
            ['code'=>'new_mortgage_contract', 'name'=>'設定契約書', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::SettingBank, 'delivery_method'=>DeliveryMethod::BankReceipt,
             'deadline_offset_days'=>-2, 'milestone_key'=>MilestoneKey::FinancialReady,
             'linked_task_code'=>17, 'applies_to_job_types'=>[4,5,6,7,8,10]],
            ['code'=>'new_mortgage_attorney_letter', 'name'=>'設定委任状', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::SettingBank, 'delivery_method'=>DeliveryMethod::BankReceipt,
             'deadline_offset_days'=>-2, 'milestone_key'=>MilestoneKey::FinancialReady,
             'linked_task_code'=>17, 'applies_to_job_types'=>[4,5,6,7,8,10]],
            ['code'=>'new_mortgagee_inkan', 'name'=>'印鑑証明書（新規銀行）', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::SettingBank, 'delivery_method'=>DeliveryMethod::BankReceipt,
             'deadline_offset_days'=>-2, 'milestone_key'=>MilestoneKey::FinancialReady,
             'linked_task_code'=>17, 'applies_to_job_types'=>[4,5,6,7,8,10]],

            // 共通の収集 → task 3 (書類受領)
            ['code'=>'sale_contract', 'name'=>'売買契約書写し', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::Broker, 'delivery_method'=>DeliveryMethod::Other,
             'deadline_offset_days'=>-14, 'milestone_key'=>MilestoneKey::PreSettlementPostal,
             'linked_task_code'=>3, 'applies_to_job_types'=>[1,3,4,5,8]],
            ['code'=>'tax_evaluation', 'name'=>'評価証明書', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::Common, 'delivery_method'=>DeliveryMethod::OfficeInternal,
             'deadline_offset_days'=>-5, 'milestone_key'=>MilestoneKey::AllConfirmed,
             'linked_task_code'=>3, 'applies_to_job_types'=>[1,2,3,4,5,6,7,8,9,10,11]],
            ['code'=>'property_register', 'name'=>'登記情報', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::Common, 'delivery_method'=>DeliveryMethod::OfficeInternal,
             'deadline_offset_days'=>-14, 'milestone_key'=>MilestoneKey::PreSettlementPostal,
             'linked_task_code'=>3, 'applies_to_job_types'=>[1,2,3,4,5,6,7,8,9,10,11]],
            ['code'=>'property_map', 'name'=>'公図', 'kind'=>DocumentKind::Collection,
             'requested_from_role'=>RoleCode::Common, 'delivery_method'=>DeliveryMethod::OfficeInternal,
             'deadline_offset_days'=>-14, 'milestone_key'=>MilestoneKey::PreSettlementPostal,
             'linked_task_code'=>3, 'applies_to_job_types'=>[1,2,3,4,5,6,7,8,9,10,11]],

            // 作成系（recast 管轄）→ task 4 / 14
            ['code'=>'required_docs_list_seller', 'name'=>'必要書類一覧（売主用）',
             'kind'=>DocumentKind::Creation, 'requested_from_role'=>RoleCode::Seller,
             'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>-14, 'milestone_key'=>MilestoneKey::PreSettlementPostal,
             'linked_task_code'=>4, 'applies_to_job_types'=>[1,3,4,5,8,9]],
            ['code'=>'required_docs_list_buyer', 'name'=>'必要書類一覧（買主用）',
             'kind'=>DocumentKind::Creation, 'requested_from_role'=>RoleCode::Buyer,
             'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>-14, 'milestone_key'=>MilestoneKey::PreSettlementPostal,
             'linked_task_code'=>4, 'applies_to_job_types'=>[1,3,4,5,6,7,8,10]],
            ['code'=>'cover_letter_seller', 'name'=>'送付状（売主向け）', 'kind'=>DocumentKind::Creation,
             'requested_from_role'=>RoleCode::Seller, 'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>-10, 'milestone_key'=>MilestoneKey::PreSettlementPostal,
             'linked_task_code'=>14, 'confirmation_requires'=>['seller_attorney_letter'],
             'applies_to_job_types'=>[1,3,4,5,8,9]],
            ['code'=>'receipt_letter', 'name'=>'受領書', 'kind'=>DocumentKind::Creation,
             'requested_from_role'=>RoleCode::Common, 'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>3, 'milestone_key'=>null,
             'linked_task_code'=>14, 'applies_to_job_types'=>[1,2,3,4,5,6,7,8,9,10,11]],
            ['code'=>'completion_report', 'name'=>'完了報告書', 'kind'=>DocumentKind::Creation,
             'requested_from_role'=>RoleCode::Common, 'delivery_method'=>DeliveryMethod::PostalToParty,
             'deadline_offset_days'=>14, 'milestone_key'=>null,
             'linked_task_code'=>16, 'applies_to_job_types'=>[1,2,3,4,5,6,7,8,9,10,11]],
        ];

        foreach ($defs as $row) {
            DocumentDefinition::updateOrCreate(['code' => $row['code']], [
                'name'                  => $row['name'],
                'kind'                  => $row['kind']->value,
                'requested_from_role'   => $row['requested_from_role']->value,
                'delivery_method'       => $row['delivery_method']->value,
                'deadline_offset_days'  => $row['deadline_offset_days'],
                'milestone_key'         => $row['milestone_key']?->value,
                'linked_task_code'      => $row['linked_task_code'] ?? null,
                'applies_to_job_types'  => $row['applies_to_job_types'],
                'confirmation_requires' => $row['confirmation_requires'] ?? null,
                'needs_seal'            => $row['needs_seal'] ?? false,
                'is_active'             => true,
            ]);
        }
    }
}
