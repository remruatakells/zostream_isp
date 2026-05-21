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
                'address' => 'Lungpho branch address',
                'status' => 'active',
            ],
            [
                'name' => 'Ngopa',
                'code' => 'NGOPA',
                'location' => 'Ngopa',
                'address' => 'Ngopa branch address',
                'status' => 'active',
            ],
            [
                'name' => 'Pawlrang',
                'code' => 'PAWLRANG',
                'location' => 'Pawlrang',
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
