<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * matter_files: 事件レコード本体。
 * v2.0で drive_folder_id を新設（Chapter 7 Drive 連携）。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('matter_files', function (Blueprint $table) {
            $table->id();
            $table->string('matter_number', 20)->unique()->comment('10桁の事件番号');
            $table->unsignedTinyInteger('job_type')->comment('業務種別 1〜11');
            $table->date('received_at')->nullable()->comment('受任日');
            $table->dateTime('settlement_date')->nullable()->comment('決済日時');
            $table->foreignId('main_user_id')->nullable()->constrained('users')->comment('主担当者');
            $table->enum('progress', ['pre_acceptance', 'in_progress', 'completed', 'not_required'])
                ->default('pre_acceptance');
            $table->string('drive_folder_id', 100)->nullable()->comment('Chapter 7: Google Drive フォルダID');
            $table->json('meta')->nullable()->comment('業務種別固有のメタ情報');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['settlement_date', 'progress']);
            $table->index('main_user_id');
        });

        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->constrained('matter_files')->cascadeOnDelete();
            $table->unsignedTinyInteger('role_code')->comment('0=共通,1=売主,2=買主,3=抹消金融機関,4=設定金融機関,5=仲介,6=その他,7=確認者');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('name');
            $table->string('name_kana')->nullable();
            $table->enum('entity_type', ['individual', 'corporate'])->default('individual');
            $table->string('postal_code', 8)->nullable();
            $table->string('address')->nullable();
            $table->string('tel', 20)->nullable();
            $table->string('email')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->index(['matter_id', 'role_code', 'display_order']);
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matter_id')->constrained('matter_files')->cascadeOnDelete();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('location')->comment('所在');
            $table->string('parcel_number')->nullable()->comment('地番');
            $table->string('land_category')->nullable()->comment('地目');
            $table->string('area')->nullable()->comment('地積');
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->index('matter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
        Schema::dropIfExists('parties');
        Schema::dropIfExists('matter_files');
    }
};
