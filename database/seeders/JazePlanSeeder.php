<?php

namespace Database\Seeders;

<<<<<<< HEAD
use Illuminate\Database\Seeder;
use App\Models\JazePlan;
=======
use App\Models\JazePlan;
use Illuminate\Database\Seeder;
>>>>>>> 8a16b0f64ab3c8a5ffb74d4f7aa7ee160d2ca634

class JazePlanSeeder extends Seeder
{
    /**
<<<<<<< HEAD
     * Run the database seeds.
=======
     * Seed Jaze plan records.
>>>>>>> 8a16b0f64ab3c8a5ffb74d4f7aa7ee160d2ca634
     */
    public function run(): void
    {
        $plans = [
            [
<<<<<<< HEAD
                'group_id' => '631431159525504',
                'group_name' => 'ROOKIE',
                'profile_id' => '693790769412928',
                'profile_name' => 'ZOSTREAM_ROOKIE',
                'amount' => 550.00,
            ],
            [
=======
>>>>>>> 8a16b0f64ab3c8a5ffb74d4f7aa7ee160d2ca634
                'group_id' => '631431201745408',
                'group_name' => 'ELITE',
                'profile_id' => '693790886685952',
                'profile_name' => 'ZOSTREAM_ELITE',
<<<<<<< HEAD
                'amount' => 800.00,
            ],
            [
                'group_id' => '631431241785280',
                'group_name' => 'PRO',
                'profile_id' => '693791008608384',
                'profile_name' => 'ZOSTREAM_PRO',
                'amount' => 1200.00,
            ],
            [
                'group_id' => '631431420215872',
                'group_name' => 'VETERAN',
                'profile_id' => '693791361590848',
                'profile_name' => 'ZOSTREAM_VETERAN',
                'amount' => 2400.00,
=======
                'amount' => '800.00',
>>>>>>> 8a16b0f64ab3c8a5ffb74d4f7aa7ee160d2ca634
            ],
            [
                'group_id' => '631431545011200',
                'group_name' => 'LEGENDARY',
                'profile_id' => '693791472432192',
                'profile_name' => 'ZOSTREAM_LEGENDARY',
<<<<<<< HEAD
                'amount' => 3000.00,
=======
                'amount' => '3000.00',
            ],
            [
                'group_id' => '631431241785280',
                'group_name' => 'PRO',
                'profile_id' => '693791008608384',
                'profile_name' => 'ZOSTREAM_PRO',
                'amount' => '1200.00',
            ],
            [
                'group_id' => '631431159525504',
                'group_name' => 'ROOKIE',
                'profile_id' => '693790769412928',
                'profile_name' => 'ZOSTREAM_ROOKIE',
                'amount' => '550.00',
            ],
            [
                'group_id' => '631431420215872',
                'group_name' => 'VETERAN',
                'profile_id' => '693791361590848',
                'profile_name' => 'ZOSTREAM_VETERAN',
                'amount' => '2400.00',
>>>>>>> 8a16b0f64ab3c8a5ffb74d4f7aa7ee160d2ca634
            ],
        ];

        foreach ($plans as $plan) {
            JazePlan::updateOrCreate(
                ['group_id' => $plan['group_id']],
                $plan
            );
        }
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 8a16b0f64ab3c8a5ffb74d4f7aa7ee160d2ca634
