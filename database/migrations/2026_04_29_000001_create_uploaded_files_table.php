<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 事件ごとの汎用アップロードファイル。所員が事件の Drive フォルダに
 * ドラッグ&ドロップで放り込んだファイルを記録する。
 *
 * Drive 側に保存されるが、UI で「最近上げたファイル」を出すためにここに
 * メタを残す。Drive 失敗時は local_path でフォールバック保存。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->constrained('matter_files')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('drive_file_id', 100)->nullable();
            $table->string('local_path')->nullable();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['matter_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_files');
    }
};
