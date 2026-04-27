<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('milestone_key', 40)->nullable()->after('reviewer_user_id')
                ->comment('このタスクが属するマイルストーン');
            $table->index(['job_id', 'milestone_key']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['job_id', 'milestone_key']);
            $table->dropColumn('milestone_key');
        });
    }
};
