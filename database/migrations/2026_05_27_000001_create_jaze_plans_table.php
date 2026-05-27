<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jaze_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('group_id')->unique();
            $table->string('group_name');
            $table->string('profile_id')->nullable();
            $table->string('profile_name');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jaze_plans');
    }
};
