<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * document_templates: 22種のテンプレマスタ。
 * v2.0 で metadata_yaml / ui_definition_yaml を追加し、選択ロジックの
 * メタデータ駆動化（6.2.1）と UI 自動生成（6.2.3）を実現する。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('テンプレ識別子。例: 16_send_receipt_katitas_buyer');
            $table->string('description');
            $table->string('file_path')->comment('storage/app/templates 配下のパス');
            $table->enum('format', ['docx', 'xlsx'])->default('docx');
            $table->unsignedSmallInteger('priority')->default(10)
                ->comment('6.2.1: 100=特定顧客, 50=業務種別固有, 10=汎用');
            $table->longText('metadata_yaml')->nullable()->comment('applies_when の YAML');
            $table->longText('ui_definition_yaml')->nullable()->comment('チェックボックスUI 定義');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['priority', 'is_active']);
        });

        Schema::create('task_form_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('document_templates');
            $table->json('form_data')->comment('チェックボックス・プルダウンの選択値');
            $table->dateTime('generated_at')->nullable();
            $table->string('drive_file_id', 100)->nullable();
            $table->string('local_path')->nullable()->comment('Drive 失敗時のフォールバック先');
            $table->timestamps();

            $table->index(['task_id', 'template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_form_inputs');
        Schema::dropIfExists('document_templates');
    }
};
