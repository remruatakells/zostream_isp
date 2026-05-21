<?php

use App\Models\AdminUser;
use App\Models\Branch;
use App\Services\JazeApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('super admin can read jaze data from all branches without selecting one', function () {
    Branch::create([
        'name' => 'Lungpho',
        'code' => 'LUNGPHO',
        'status' => 'active',
    ]);
    Branch::create([
        'name' => 'Ngopa',
        'code' => 'NGOPA',
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

    $jaze = Mockery::mock(JazeApiClient::class);
    $jaze->shouldReceive('get')
        ->twice()
        ->andReturnUsing(fn (Branch $branch): array => [
            'status' => 200,
            'data' => ['branch_code' => $branch->code],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);

    $response = $this->getJson('/api/jaze/users/all?admin_login=9000000000&admin_password=password123');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'branches')
        ->assertJsonPath('branches.0.branch.code', 'LUNGPHO')
        ->assertJsonPath('branches.1.branch.code', 'NGOPA');
});

test('super admin jaze write calls still require a selected branch', function () {
    AdminUser::create([
        'name' => 'Super Admin',
        'phone' => '9000000000',
        'email' => 'super@example.com',
        'password' => Hash::make('password123'),
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    $response = $this->postJson('/api/jaze/users', [
        'admin_login' => '9000000000',
        'admin_password' => 'password123',
        'userGroupId' => 'group-1',
        'accountId' => 'account-1',
        'userName' => 'TESTUSER001',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('message', 'branch_id or branch_code is required for super_admin Jaze API write calls.');
});
