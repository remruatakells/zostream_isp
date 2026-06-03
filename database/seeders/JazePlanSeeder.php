<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\JazePlan;
use Illuminate\Database\Seeder;

class JazePlanSeeder extends Seeder
{
    /**
     * Seed Jaze plan records.
     */
    public function run(): void
    {
        $plans = [
            [
                'group_id' => '631431159525504',
                'user_group_id' => '1',
                'group_name' => 'ROOKIE',
                'profile_id' => '693790769412928',
                'profile_name' => 'ZOSTREAM_ROOKIE',
                'amount' => '550.00',
            ],
            [
                'group_id' => '631431201745408',
                'user_group_id' => '2',
                'group_name' => 'ELITE',
                'profile_id' => '693790886685952',
                'profile_name' => 'ZOSTREAM_ELITE',
                'amount' => '800.00',
            ],
            [
                'group_id' => '631431241785280',
                'user_group_id' => '3',
                'group_name' => 'PRO',
                'profile_id' => '693791008608384',
                'profile_name' => 'ZOSTREAM_PRO',
                'amount' => '1200.00',
            ],
            [
                'group_id' => '631431420215872',
                'user_group_id' => '4',
                'group_name' => 'VETERAN',
                'profile_id' => '693791361590848',
                'profile_name' => 'ZOSTREAM_VETERAN',
                'amount' => '2400.00',
            ],
            [
                'group_id' => '631431545011200',
                'user_group_id' => '5',
                'group_name' => 'LEGENDARY',
                'profile_id' => '693791472432192',
                'profile_name' => 'ZOSTREAM_LEGENDARY',
                'amount' => '3000.00',
            ],
        ];

        Branch::query()->each(function (Branch $branch) use ($plans): void {
            foreach ($plans as $plan) {
                JazePlan::updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'group_id' => $plan['group_id'],
                    ],
                    $plan
                );
            }
        });
    }
}
