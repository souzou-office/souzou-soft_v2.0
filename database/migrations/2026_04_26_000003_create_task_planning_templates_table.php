<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 8.2.2 工程テンプレマスタ。
 * 事件作成時に job_type で絞り込み、display_order 順に取り出して
 * tasks レコードを生成する。付録Aの初期データを seeder で投入する。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_planning_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('job_type')->comment('1〜11');
            $table->unsignedTinyInteger('task_code')->comment('1〜16');
            $table->unsignedTinyInteger('role_code')->default(0);
            $table->integer('days_before_settlement')->comment('決済日からの相対日数（負=決済前）');
            $table->unsignedSmallInteger('display_order');
            $table->string('task_name', 100);
            $table->unsignedTinyInteger('task_type_code');
            $table->timestamps();

            $table->unique(
                ['job_type', 'task_code', 'role_code', 'display_order'],
                'task_planning_unique'
            );
            $table->index(['job_type', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_planning_templates');
    }
};
