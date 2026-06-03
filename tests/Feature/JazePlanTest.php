<?php

use App\Models\AdminUser;
use App\Models\Branch;
use App\Models\JazePlan;
use Database\Seeders\BranchSeeder;
use Database\Seeders\JazePlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('admin can create and list jaze plans with amounts', function () {
    $branch = Branch::create([
        'name' => 'Ngopa',
        'code' => 'jaze9',
        'status' => 'active',
    ]);

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
        'branch_id' => $branch->id,
        'group_id' => '631431159525504',
        'group_name' => 'ROOKIE',
        'profile_id' => '693790769412928',
        'profile_name' => 'ZOSTREAM_ROOKIE',
        'amount' => 499,
    ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('branch_id', $branch->id)
        ->assertJsonPath('group_id', '631431159525504')
        ->assertJsonPath('group_name', 'ROOKIE')
        ->assertJsonPath('profile_name', 'ZOSTREAM_ROOKIE')
        ->assertJsonPath('amount', '499.00');

    $listResponse = $this->getJson("/api/jaze-plans?admin_login=9000000000&admin_password=password123&branch_id={$branch->id}");

    $listResponse
        ->assertOk()
        ->assertJsonPath('data.0.group_id', '631431159525504')
        ->assertJsonPath('data.0.amount', '499.00');
});

test('admin can update jaze plan amount', function () {
    $branch = Branch::create([
        'name' => 'Lungpho',
        'code' => 'pho',
        'status' => 'active',
    ]);

    AdminUser::create([
        'name' => 'Branch Admin',
        'phone' => '9000000001',
        'email' => 'branch@example.com',
        'password' => Hash::make('password123'),
        'role' => 'branch_admin',
        'branch_id' => $branch->id,
        'status' => 'active',
    ]);

    $plan = JazePlan::create([
        'branch_id' => $branch->id,
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
        'branch_id' => $branch->id,
        'group_id' => $plan->group_id,
        'amount' => 899,
    ]);
});

test('same jaze group id can belong to different branches', function () {
    $ngopa = Branch::create([
        'name' => 'Ngopa',
        'code' => 'jaze9',
        'status' => 'active',
    ]);
    $lungpho = Branch::create([
        'name' => 'Lungpho',
        'code' => 'pho',
        'status' => 'active',
    ]);

    JazePlan::create([
        'branch_id' => $ngopa->id,
        'group_id' => '631431159525504',
        'group_name' => 'ROOKIE',
        'profile_id' => '693790769412928',
        'profile_name' => 'ZOSTREAM_ROOKIE',
        'amount' => 550,
    ]);

    JazePlan::create([
        'branch_id' => $lungpho->id,
        'group_id' => '631431159525504',
        'group_name' => 'ROOKIE',
        'profile_id' => '693790769412928',
        'profile_name' => 'ZOSTREAM_ROOKIE',
        'amount' => 650,
    ]);

    $this->assertDatabaseHas('jaze_plans', [
        'branch_id' => $ngopa->id,
        'group_id' => '631431159525504',
        'amount' => 550,
    ]);
    $this->assertDatabaseHas('jaze_plans', [
        'branch_id' => $lungpho->id,
        'group_id' => '631431159525504',
        'amount' => 650,
    ]);
});

test('jaze plan seeder stores plans per branch', function () {
    $this->seed(BranchSeeder::class);
    $this->seed(JazePlanSeeder::class);

    $branch = Branch::where('code', 'jaze9')->firstOrFail();

    $this->assertDatabaseHas('jaze_plans', [
        'branch_id' => $branch->id,
        'group_id' => '631431159525504',
        'group_name' => 'ROOKIE',
        'profile_name' => 'ZOSTREAM_ROOKIE',
        'amount' => 550,
    ]);

    expect(JazePlan::where('branch_id', $branch->id)->where('group_id', '631431159525504')->first())->not->toBeNull();
});
