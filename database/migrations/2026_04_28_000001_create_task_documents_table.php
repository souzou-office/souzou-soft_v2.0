<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * task と document の多対多。「書類受領（売主）」1 タスクに売主の書類群が
 * 紐づくように、書類はタスクの sub-artifact として束ねる。
 *
 * 一括受領タスクを 1 行で表現できるようになり、書類が散発タスクに分裂しない。
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'document_id']);
        });

        Schema::table('document_definitions', function (Blueprint $table) {
            $table->unsignedTinyInteger('linked_task_code')->nullable()->after('milestone_key')
                ->comment('この書類が属する task_code。DocumentPlanner が pivot を自動生成');
        });
    }

    public function down(): void
    {
        Schema::table('document_definitions', function (Blueprint $table) {
            $table->dropColumn('linked_task_code');
        });
        Schema::dropIfExists('task_documents');
    }
};
