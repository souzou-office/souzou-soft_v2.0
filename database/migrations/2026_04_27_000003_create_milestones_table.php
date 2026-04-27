<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 事件ごとのマイルストーン（4種固定）。
 *
 *  pre_settlement_postal     -10日 : 事前郵送書類確定・送付完了
 *  postal_returned           -2日  : 押印書類返送受領
 *  financial_ready           -2日  : 融資関係書類受領
 *  all_confirmed             -1日  : 全書類最終確定
 *
 * 各マイルストーンは紐付く書類が全て該当状態に達した時点で完了扱いとする。
 * 単一マイルストーンでは現実が表現できないので 4 個に分けるのが本仕様の核。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->constrained('matter_files')->cascadeOnDelete();
            $table->string('key', 40);
            $table->string('name');
            $table->integer('deadline_offset_days');
            $table->date('deadline')->nullable()->comment('決済日 + offset');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['matter_id', 'key']);
            $table->index('deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
