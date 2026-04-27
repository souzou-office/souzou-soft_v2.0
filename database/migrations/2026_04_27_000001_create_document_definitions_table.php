<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 書類マスタ。「不動産売買決済では何の書類が発生するか」を体系定義。
 *
 *  - kind          : 収集系 (collection) / 作成系 (creation)
 *  - delivery_method: 配送経路（postal / handover / online / bank_receipt 等）
 *  - deadline_offset_days: 決済日基準の確定締切（負=決済前）
 *  - milestone_key : この書類が属するマイルストーン
 *  - confirmation_requires: この書類を確定するために必要な他書類 code（DAG）
 *  - applies_to_job_types: 業務種別 1〜11 のうちどれで発生するか（JSON配列）
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->enum('kind', ['collection', 'creation']);
            $table->unsignedTinyInteger('requested_from_role')->default(0)
                ->comment('0=共通 1=売主 2=買主 3=抹消金融機関 4=設定金融機関 5=仲介');
            $table->enum('delivery_method', [
                'postal_to_party',          // 当事者へ郵送して押印・返送
                'handover_at_settlement',   // 決済当日その場で
                'online_submission',        // 法務局オンライン送信（基本サムポローニア管轄）
                'bank_receipt',             // 銀行から受領
                'office_internal',          // 事務所内で取得
                'other',
            ])->default('other');
            $table->integer('deadline_offset_days')->comment('決済日基準');
            $table->string('milestone_key', 40)->nullable()
                ->comment('pre_settlement_postal / postal_returned / financial_ready / all_confirmed');
            $table->json('confirmation_requires')->nullable()
                ->comment('依存する書類 code の配列');
            $table->json('applies_to_job_types')
                ->comment('1〜11 のうちどれで発生するか');
            $table->boolean('needs_seal')->default(false)->comment('押印必要');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_definitions');
    }
};
