<?php

use App\Models\AdminUser;
use App\Models\Branch;
use App\Models\RenewSuccessLog;
use App\Services\JazeApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('admin can store renew success data for their branch', function () {
    $branch = Branch::create([
        'name' => 'Ngopa',
        'code' => 'NGOPA',
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

    $response = $this->postJson('/api/jaze/renew-successes', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'userId' => 'user-123',
        'userName' => 'customer001',
        'accountId' => 'account-1',
        'status' => 'success',
        'renewDefaultSettings' => 'true',
        'isRenewPresentDate' => 'true',
        'jaze_response' => [
            'message' => 'Renewed successfully',
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('branch_id', $branch->id)
        ->assertJsonPath('jaze_user_id', 'user-123')
        ->assertJsonPath('payload.jaze_response.message', 'Renewed successfully')
        ->assertJsonMissingPath('payload.admin_password');

    $this->assertDatabaseHas('renew_success_logs', [
        'branch_id' => $branch->id,
        'jaze_user_id' => 'user-123',
        'jaze_username' => 'customer001',
        'account_id' => 'account-1',
        'status' => 'success',
    ]);
});

test('super admin can list renew success data from all branches', function () {
    $firstBranch = Branch::create([
        'name' => 'Lungpho',
        'code' => 'LUNGPHO',
        'status' => 'active',
    ]);
    $secondBranch = Branch::create([
        'name' => 'Ngopa',
        'code' => 'NGOPA',
        'status' => 'active',
    ]);

    $superAdmin = AdminUser::create([
        'name' => 'Super Admin',
        'phone' => '9000000000',
        'email' => 'super@example.com',
        'password' => Hash::make('password123'),
        'role' => 'super_admin',
        'status' => 'active',
    ]);

    RenewSuccessLog::create([
        'branch_id' => $firstBranch->id,
        'admin_user_id' => $superAdmin->id,
        'jaze_user_id' => 'user-1',
        'status' => 'success',
        'payload' => ['userId' => 'user-1'],
        'renewed_at' => now(),
    ]);
    RenewSuccessLog::create([
        'branch_id' => $secondBranch->id,
        'admin_user_id' => $superAdmin->id,
        'jaze_user_id' => 'user-2',
        'status' => 'success',
        'payload' => ['userId' => 'user-2'],
        'renewed_at' => now(),
    ]);

    $response = $this->getJson('/api/jaze/renew-successes?admin_login=9000000000&admin_password=password123');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('branch admin cannot store renew success data for another branch', function () {
    $ownBranch = Branch::create([
        'name' => 'Ngopa',
        'code' => 'NGOPA',
        'status' => 'active',
    ]);
    $otherBranch = Branch::create([
        'name' => 'Lungpho',
        'code' => 'LUNGPHO',
        'status' => 'active',
    ]);

    AdminUser::create([
        'name' => 'Branch Admin',
        'phone' => '9000000001',
        'email' => 'branch@example.com',
        'password' => Hash::make('password123'),
        'role' => 'branch_admin',
        'branch_id' => $ownBranch->id,
        'status' => 'active',
    ]);

    $response = $this->postJson('/api/jaze/renew-successes', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'branch_id' => $otherBranch->id,
        'userId' => 'user-123',
    ]);

    $response->assertForbidden();
});

test('successful jaze renew stores renew success log automatically', function () {
    $branch = Branch::create([
        'name' => 'Ngopa',
        'code' => 'NGOPA',
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

    $jaze = Mockery::mock(JazeApiClient::class);
    $jaze->shouldReceive('post')
        ->once()
        ->withArgs(fn (Branch $jazeBranch, string $path, array $payload): bool => $jazeBranch->is($branch)
            && $path === 'api/v1/renew'
            && $payload['userId'] === 'user-123')
        ->andReturn([
            'status' => 200,
            'data' => ['message' => 'Renewed successfully', 'transactionId' => 'txn-1'],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);

    $response = $this->postJson('/api/jaze/renew', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'userId' => 'user-123',
        'userName' => 'customer001',
        'accountId' => 'account-1',
        'renewDefaultSettings' => 'true',
        'isRenewPresentDate' => 'true',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Renewed successfully');

    $this->assertDatabaseHas('renew_success_logs', [
        'branch_id' => $branch->id,
        'jaze_user_id' => 'user-123',
        'jaze_username' => 'customer001',
        'account_id' => 'account-1',
        'status' => 'success',
    ]);

    $renewSuccessLog = RenewSuccessLog::first();

    expect($renewSuccessLog->payload)
        ->toHaveKey('request.userId', 'user-123')
        ->toHaveKey('response.message', 'Renewed successfully')
        ->not->toHaveKey('request.admin_password');
});

test('failed jaze renew does not store renew success log', function () {
    $branch = Branch::create([
        'name' => 'Ngopa',
        'code' => 'NGOPA',
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

    $jaze = Mockery::mock(JazeApiClient::class);
    $jaze->shouldReceive('post')
        ->once()
        ->andReturn([
            'status' => 422,
            'data' => ['message' => 'Renew failed'],
            'successful' => false,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);

    $response = $this->postJson('/api/jaze/renew', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'userId' => 'user-123',
        'renewDefaultSettings' => 'true',
        'isRenewPresentDate' => 'true',
    ]);

    $response->assertStatus(422);

    $this->assertDatabaseCount('renew_success_logs', 0);
});
