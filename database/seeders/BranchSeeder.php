<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Seed branch records.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Lungpho',
                'code' => 'LUNGPHO',
                'location' => 'Lungpho',
                'jaze_api_token' => 'pho',
                'jaze_api_key' => '158e8dd8fffa77e221bc59087009f36bbea636e5',
                'address' => 'Lungpho branch address',
                'status' => 'active',
            ],
            [
                'name' => 'Ngopa',
                'code' => 'NGOPA',
                'location' => 'Ngopa',
                'jaze_api_token' => 'jaze9',
                'jaze_api_key' => '939a264329feefc2a12eb52593cb6d007616caed',
                'address' => 'Ngopa branch address',
                'status' => 'active',
            ],
            [
                'name' => 'Pawlrang',
                'code' => 'PAWLRANG',
                'location' => 'Pawlrang',
                'jaze_api_token' => 'paw',
                'jaze_api_key' => '158e8dd8fffa77e221bc59087009f36bbea636e5',
                'address' => 'Pawlrang branch address',
                'status' => 'active',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['code' => $branch['code']],
                $branch
            );
        }
    }
}
