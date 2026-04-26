<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 8.2.1 tasks
 * v2.0 で 6 カラム（assignee_user_id 等）を新規追加。
 * 既存 v1 では tasks は単純な状態列だけだったが、v2.0 で
 * 「担当者軸の業務管理」を実現するためにここを核として拡張する。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('matter_files')->cascadeOnDelete()->comment('事件ID');
            $table->unsignedTinyInteger('task_code')->comment('1〜16');
            $table->unsignedTinyInteger('task_type_code')->comment('1〜5');
            $table->unsignedTinyInteger('role_code')->default(0);
            $table->unsignedSmallInteger('display_order');
            $table->string('task_name', 100);

            // ★ v2.0 で追加（5.1）
            $table->foreignId('assignee_user_id')->nullable()->constrained('users');
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users');
            $table->dateTime('assigned_at')->nullable();
            $table->date('planned_date')->nullable()->comment('期日');
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'awaiting_review'])
                ->default('not_started');
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users');

            $table->dateTime('completed_at')->nullable();
            $table->json('payload')->nullable()->comment('工程ごとの可変入力（チェック状態、メモ等）');
            $table->timestamps();

            // 8.2.1 推奨インデックス
            $table->index(['assignee_user_id', 'planned_date']);
            $table->index(['planned_date', 'status']);
            $table->index(['job_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
