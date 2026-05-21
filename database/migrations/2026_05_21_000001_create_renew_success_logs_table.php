<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renew_success_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('jaze_user_id')->nullable()->index();
            $table->string('jaze_username')->nullable()->index();
            $table->string('account_id')->nullable()->index();
            $table->string('status')->default('success')->index();
            $table->json('payload');
            $table->timestamp('renewed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renew_success_logs');
    }
};
