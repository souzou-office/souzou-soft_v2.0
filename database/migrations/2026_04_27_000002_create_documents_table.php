<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 事件ごとの書類インスタンス。document_definitions から事件作成時に生成される。
 *
 * 状態機械:
 *   収集系 (kind=collection):
 *     not_started → requested(必要書類一覧送付済) → received → confirmed
 *   作成系 (kind=creation):
 *     not_started → drafted → confirmed
 *
 * is_held: 登記識別情報通知などを「現物で預かり済」のフラグ。
 * 確定締切は document_definitions.deadline_offset_days を決済日に加算して算出。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->constrained('matter_files')->cascadeOnDelete();
            $table->foreignId('definition_id')->nullable()->constrained('document_definitions');

            // 定義からのスナップショット（マスタ変更で過去事件が壊れないように）
            $table->string('code', 80);
            $table->string('name');
            $table->enum('kind', ['collection', 'creation']);
            $table->unsignedTinyInteger('requested_from_role')->default(0);
            $table->string('delivery_method', 40);
            $table->string('milestone_key', 40)->nullable();
            $table->date('deadline')->nullable()->comment('決済日 + offset の絶対日');

            // 状態
            $table->enum('state', ['not_started', 'requested', 'received', 'drafted', 'confirmed'])
                ->default('not_started');
            $table->boolean('is_held')->default(false)->comment('現物預かり済（登記識別情報通知等）');
            $table->json('confirmation_requires')->nullable()->comment('依存書類 code 配列');

            // タイムスタンプ
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('drafted_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users');

            $table->text('memo')->nullable();
            $table->timestamps();

            $table->index(['matter_id', 'state']);
            $table->index(['matter_id', 'milestone_key']);
            $table->index('deadline');
            $table->unique(['matter_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
