<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 8.2.5 notifications
 * v2.0 で type の値域を拡張、target_user_id / related_task_id 等を追加。
 * 5.4 通知システムの本体テーブル。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_user_id')->constrained('users')->comment('通知先');
            $table->enum('type', [
                'auto_settlement',
                'task_assigned',
                'task_completed',
                'task_overdue',
                'task_due_soon',
                'task_returned',
                'task_comment',
            ]);
            $table->string('title');
            $table->text('body')->nullable();
            $table->foreignId('related_job_id')->nullable()->constrained('matter_files')->nullOnDelete();
            $table->foreignId('related_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->json('payload')->nullable()->comment('テンプレ差込変数等');
            $table->boolean('is_read')->default(false);
            $table->boolean('sent_email')->default(false);
            $table->timestamps();

            $table->index(['target_user_id', 'is_read', 'created_at']);
            $table->index('type');
        });

        // 通知設定（B.3）
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50);
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
