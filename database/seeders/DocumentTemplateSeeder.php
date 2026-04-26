<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;

/**
 * 6.4 統合後のテンプレ構成。
 *
 * 22→11/12種への圧縮例。第1段階移行（6.5）では metadata_yaml を後付けし、
 * 既存テンプレファイルを触らずに選択ロジックだけメタデータ駆動に置き換える。
 */
class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // 6.4 必要書類等一覧 1種に統合
            [
                'code'        => 'required_docs_buyer',
                'description' => '必要書類等一覧（買主）',
                'file_path'   => 'required_docs_buyer.docx',
                'priority'    => 10,
                'metadata_yaml' => <<<YAML
applies_when:
  role: 2
YAML,
                'ui_definition_yaml' => <<<YAML
fields:
  - { type: radio, name: buyer_entity, label: 買主種別, options: [個人, 法人] }
  - { type: checkbox, name: address_change, label: 住所変更あり }
  - { type: checkbox, name: setting_bank, label: 設定金融機関あり }
  - { type: number, name: setting_bank_count, label: 設定金融機関数, show_when: setting_bank }
  - { type: radio, name: stamp_timing, label: 押印タイミング, options: [事前押印, 決済日当日] }
presets:
  - { name: 個人標準, values: { buyer_entity: 個人, setting_bank: true, setting_bank_count: 1, stamp_timing: 事前押印 } }
YAML,
            ],

            // 6.4 送付書兼受領書（汎用 → 3〜4種）
            [
                'code'        => 'send_receipt_buyer',
                'description' => '送付書兼受領書（買主）',
                'file_path'   => 'send_receipt_buyer.docx',
                'priority'    => 10,
                'metadata_yaml' => <<<YAML
applies_when:
  role: 2
YAML,
            ],
            [
                'code'        => 'send_receipt_seller',
                'description' => '送付書兼受領書（売主）',
                'file_path'   => 'send_receipt_seller.docx',
                'priority'    => 10,
                'metadata_yaml' => <<<YAML
applies_when:
  role: 1
YAML,
            ],
            [
                'code'        => 'send_receipt_setting_bank',
                'description' => '送付書兼受領書（設定金融機関）',
                'file_path'   => 'send_receipt_setting_bank.docx',
                'priority'    => 10,
                'metadata_yaml' => <<<YAML
applies_when:
  role: 4
YAML,
            ],

            // 特定顧客（priority=100、6.4 の据置）
            [
                'code'        => 'send_receipt_katitas_buyer',
                'description' => '送付書兼受領書（カチタス様（買主））',
                'file_path'   => 'send_receipt_katitas_buyer.docx',
                'priority'    => 100,
                'metadata_yaml' => <<<YAML
applies_when:
  job_type: [4, 5, 7, 8]
  role: 2
  customer.name_contains: [カチタス, Katitas]
YAML,
            ],
            [
                'code'        => 'send_receipt_flux_buyer',
                'description' => '送付書兼受領書（フラックス様（買主））',
                'file_path'   => 'send_receipt_flux_buyer.docx',
                'priority'    => 100,
                'metadata_yaml' => <<<YAML
applies_when:
  role: 2
  customer.name_contains: [フラックス, Flux]
YAML,
            ],
            [
                'code'        => 'send_receipt_sekisui_buyer',
                'description' => '送付書兼受領書（積水ハウス（買主））',
                'file_path'   => 'send_receipt_sekisui_buyer.docx',
                'priority'    => 100,
                'metadata_yaml' => <<<YAML
applies_when:
  role: 2
  customer.name_contains: [積水ハウス]
YAML,
            ],

            [
                'code'        => 'fax_cover',
                'description' => '送付状（FAX）',
                'file_path'   => 'fax_cover.docx',
                'priority'    => 10,
                'metadata_yaml' => "applies_when:\n  role: 0\n",
            ],
            [
                'code'        => 'rights_cover',
                'description' => '権利証表紙',
                'file_path'   => 'rights_cover.docx',
                'priority'    => 10,
                'metadata_yaml' => "applies_when:\n  role: 0\n",
            ],
            [
                'code'        => 'reception_card',
                'description' => '受付票',
                'file_path'   => 'reception_card.docx',
                'priority'    => 10,
                'metadata_yaml' => "applies_when:\n  role: 0\n",
            ],
            [
                'code'        => 'fax_cover_receipt',
                'description' => 'FAX送付状（受領書用）',
                'file_path'   => 'fax_cover_receipt.docx',
                'priority'    => 10,
                'metadata_yaml' => "applies_when:\n  role: 0\n",
            ],
        ];

        foreach ($templates as $row) {
            DocumentTemplate::updateOrCreate(
                ['code' => $row['code']],
                $row + ['format' => 'docx', 'is_active' => true],
            );
        }
    }
}
