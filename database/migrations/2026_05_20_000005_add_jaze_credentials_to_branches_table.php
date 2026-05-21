<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->text('jaze_api_token')->nullable()->after('status');
            $table->text('jaze_api_key')->nullable()->after('jaze_api_token');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->dropColumn([
                'jaze_api_token',
                'jaze_api_key',
            ]);
        });
    }
};
