<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jaze_plans', function (Blueprint $table): void {
            $table->string('user_group_id')->nullable()->after('group_id');
        });
    }

    public function down(): void
    {
        Schema::table('jaze_plans', function (Blueprint $table): void {
            $table->dropColumn('user_group_id');
        });
    }
};
