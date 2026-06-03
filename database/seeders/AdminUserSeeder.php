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
        $ngopaBranch = Branch::where('code', 'jaze9')->first();
        $lungphoBrach = Branch::where('code', 'pho')->first();

        AdminUser::updateOrCreate(
            ['phone' => '8732856261'],
            [
                'name' => 'Super Admin',
                'email' => 'support@zostream.com',
                'password' => Hash::make('password123'),
                'role' => 'super_admin',
                'branch_id' => null,
                'status' => 'active',
            ]
        );

        AdminUser::updateOrCreate(
            ['phone' => '8888888888'],
            [
                'name' => 'Support Staff',
                'email' => 'support66@zostream.com',
                'password' => Hash::make('password123'),
                'role' => 'support',
                'branch_id' => $ngopaBranch?->id,
                'status' => 'active',
            ]
        );

        AdminUser::updateOrCreate(
            ['phone' => '9999999999'],
            [
                'name' => 'Sawmtea',
                'email' => 'support44@zostream.com',
                'password' => Hash::make('password123'),
                'role' => 'support',
                'branch_id' => $lungphoBrach?->id,
                'status' => 'active',
            ]
        );

    }
}
