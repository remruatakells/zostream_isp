<?php

use App\Models\AdminUser;
use App\Models\JazePlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('admin can create and list jaze plans with amounts', function () {
    AdminUser::create([
        'name' => 'Super Admin',
        'phone' => '9000000000',
        'email' => 'super@example.com',
        'password' => Hash::make('password123'),
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $createResponse = $this->postJson('/api/jaze-plans', [
        'admin_login' => '9000000000',
        'admin_password' => 'password123',
        'group_id' => '631431159525504',
        'group_name' => 'ROOKIE',
        'profile_id' => '693790769412928',
        'profile_name' => 'ZOSTREAM_ROOKIE',
        'amount' => 499,
    ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('group_id', '631431159525504')
        ->assertJsonPath('group_name', 'ROOKIE')
        ->assertJsonPath('profile_name', 'ZOSTREAM_ROOKIE')
        ->assertJsonPath('amount', '499.00');

    $listResponse = $this->getJson('/api/jaze-plans?admin_login=9000000000&admin_password=password123');

    $listResponse
        ->assertOk()
        ->assertJsonPath('data.0.group_id', '631431159525504')
        ->assertJsonPath('data.0.amount', '499.00');
});

test('admin can update jaze plan amount', function () {
    AdminUser::create([
        'name' => 'Branch Admin',
        'phone' => '9000000001',
        'email' => 'branch@example.com',
        'password' => Hash::make('password123'),
        'role' => 'branch_admin',
        'status' => 'active',
    ]);

    $plan = JazePlan::create([
        'group_id' => '631431201745408',
        'group_name' => 'ELITE',
        'profile_id' => '693790886685952',
        'profile_name' => 'ZOSTREAM_ELITE',
        'amount' => 799,
    ]);

    $response = $this->patchJson("/api/jaze-plans/{$plan->group_id}", [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'amount' => 899,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('amount', '899.00');

    $this->assertDatabaseHas('jaze_plans', [
        'group_id' => $plan->group_id,
        'amount' => 899,
    ]);
});

test('jaze plan seeder stores group id as the key', function () {
    $this->seed(JazePlanSeeder::class);

    $this->assertDatabaseHas('jaze_plans', [
        'group_id' => '631431159525504',
        'group_name' => 'ROOKIE',
        'profile_name' => 'ZOSTREAM_ROOKIE',
        'amount' => 0,
    ]);

    expect(JazePlan::find('631431159525504'))->not->toBeNull();
});
