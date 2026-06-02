<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_users', function (Blueprint $table): void {
            if (! Schema::hasColumn('admin_users', 'jaze_user_id')) {
                $table->string('jaze_user_id')->nullable()->index()->after('branch_id');
            }

            if (! Schema::hasColumn('admin_users', 'jaze_username')) {
                $table->string('jaze_username')->nullable()->index()->after('jaze_user_id');
            }
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE admin_users MODIFY role ENUM('super_admin', 'branch_admin', 'staff', 'support', 'user') DEFAULT 'user'"
            );
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE admin_users MODIFY role ENUM('super_admin', 'branch_admin', 'staff', 'support') DEFAULT 'staff'"
            );
        }

        Schema::table('admin_users', function (Blueprint $table): void {
            if (Schema::hasColumn('admin_users', 'jaze_username')) {
                $table->dropColumn('jaze_username');
            }

            if (Schema::hasColumn('admin_users', 'jaze_user_id')) {
                $table->dropColumn('jaze_user_id');
            }
        });
    }
};
