<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed admin user records.
     */
    public function run(): void
    {
        $lungphoBranch = Branch::where('code', 'LUNGPHO')->first();
        $ngopaBranch = Branch::where('code', 'NGOPA')->first();

        AdminUser::updateOrCreate(
            ['phone' => '9999999999'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'branch_id' => $lungphoBranch?->id,
                'status' => 'active',
            ]
        );

        AdminUser::updateOrCreate(
            ['phone' => '8888888888'],
            [
                'name' => 'Support Staff',
                'email' => 'support@example.com',
                'password' => Hash::make('password123'),
                'role' => 'support',
                'branch_id' => $ngopaBranch?->id,
                'status' => 'active',
            ]
        );
    }
}
