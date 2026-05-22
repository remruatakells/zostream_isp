<?php

use App\Models\AdminUser;
use App\Models\Branch;
use App\Models\RenewSuccessLog;
use App\Services\JazeApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

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

    $jaze = Mockery::mock(JazeApiClient::class);
    $jaze->shouldReceive('get')
        ->once()
        ->withArgs(fn (Branch $branch, string $path): bool => $branch->is($firstBranch)
            && $path === 'api/v1/get_details/user-1')
        ->andReturn([
            'status' => 200,
            'data' => ['id' => 'user-1', 'userName' => 'customer001'],
            'successful' => true,
        ]);
    $jaze->shouldReceive('get')
        ->once()
        ->withArgs(fn (Branch $branch, string $path): bool => $branch->is($secondBranch)
            && $path === 'api/v1/get_details/user-2')
        ->andReturn([
            'status' => 200,
            'data' => ['id' => 'user-2', 'userName' => 'customer002'],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);

    $response = $this->getJson('/api/jaze/renew-successes?admin_login=9000000000&admin_password=password123');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.jaze_user.id', 'user-1')
        ->assertJsonPath('data.1.jaze_user.id', 'user-2');
});

test('renew success list can be filtered by month', function () {
    $branch = Branch::create([
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
        'branch_id' => $branch->id,
        'admin_user_id' => $superAdmin->id,
        'jaze_user_id' => 'may-user',
        'status' => 'success',
        'payload' => ['userId' => 'may-user'],
        'renewed_at' => '2026-05-10 12:00:00',
    ]);
    RenewSuccessLog::create([
        'branch_id' => $branch->id,
        'admin_user_id' => $superAdmin->id,
        'jaze_user_id' => 'june-user',
        'status' => 'success',
        'payload' => ['userId' => 'june-user'],
        'renewed_at' => '2026-06-10 12:00:00',
    ]);

    $jaze = Mockery::mock(JazeApiClient::class);
    $jaze->shouldReceive('get')
        ->once()
        ->withArgs(fn (Branch $jazeBranch, string $path): bool => $jazeBranch->is($branch)
            && $path === 'api/v1/get_details/may-user')
        ->andReturn([
            'status' => 200,
            'data' => ['id' => 'may-user'],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);

    $response = $this->getJson('/api/jaze/renew-successes?admin_login=9000000000&admin_password=password123&month=2026-05');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.jaze_user_id', 'may-user')
        ->assertJsonPath('data.0.jaze_user.id', 'may-user');
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
    Http::fake([
        config('services.zostream_isp.subscribe_url') => Http::response([
            'status' => 'success',
            'message' => 'Zostream ISP Thla 1 subscription added successfully',
            'data' => [
                'user_created' => true,
                'user' => [
                    'uid' => 'subscription-user-1',
                    'auth_phone' => '9876543210',
                ],
                'subscriptions' => [
                    [
                        'id' => 501,
                        'plan' => [
                            'id' => 22,
                            'name' => 'Thla 1',
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->postJson('/api/jaze/renew', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'userId' => 'user-123',
        'userName' => 'customer001',
        'accountId' => 'account-1',
        'phone_no' => '9876543210',
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
        ->toHaveKey('zostream_isp_subscription.data.user.auth_phone', '9876543210')
        ->not->toHaveKey('request.admin_password');

    Http::assertSent(fn ($request): bool => $request->url() === config('services.zostream_isp.subscribe_url')
        && $request['phone_no'] === '9876543210');
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
        'phone_no' => '9876543210',
        'renewDefaultSettings' => 'true',
        'isRenewPresentDate' => 'true',
    ]);

    $response->assertStatus(422);

    $this->assertDatabaseCount('renew_success_logs', 0);
});

test('successful jaze renew stores success log when zostream response is wrapped', function () {
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
            'status' => 200,
            'data' => ['message' => 'Renewed successfully'],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);
    Http::fake([
        config('services.zostream_isp.subscribe_url') => Http::response([
            'response' => [
                'original' => [
                    'status' => 'success',
                    'message' => 'Zostream ISP Thla 1 subscription added successfully',
                    'data' => [
                        'user_created' => true,
                        'user' => [
                            'auth_phone' => '9876543210',
                        ],
                        'subscriptions' => [],
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->postJson('/api/jaze/renew', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'userId' => 'user-123',
        'userName' => 'customer001',
        'accountId' => 'account-1',
        'phone_no' => '9876543210',
        'renewDefaultSettings' => 'true',
        'isRenewPresentDate' => 'true',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Renewed successfully');

    $this->assertDatabaseHas('renew_success_logs', [
        'branch_id' => $branch->id,
        'jaze_user_id' => 'user-123',
        'status' => 'success',
    ]);

    $renewSuccessLog = RenewSuccessLog::first();

    expect($renewSuccessLog->payload)
        ->toHaveKey('zostream_isp_subscription.status', 'success')
        ->toHaveKey('zostream_isp_subscription.message', 'Zostream ISP Thla 1 subscription added successfully')
        ->toHaveKey('zostream_isp_subscription.data.user.auth_phone', '9876543210');
});

test('successful jaze renew does not store success log when zostream subscription fails', function () {
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
            'status' => 200,
            'data' => ['message' => 'Renewed successfully'],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);
    Http::fake([
        config('services.zostream_isp.subscribe_url') => Http::response([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => [
                'phone_no' => [
                    'The phone no field is required when none of phone / phone number are present.',
                ],
            ],
        ], 422),
    ]);

    $response = $this->postJson('/api/jaze/renew', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'userId' => 'user-123',
        'phone_no' => '9876543210',
        'renewDefaultSettings' => 'true',
        'isRenewPresentDate' => 'true',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('message', 'Zostream ISP subscription failed.')
        ->assertJsonPath('zostream_isp_response.status', 'error');

    $this->assertDatabaseCount('renew_success_logs', 0);
});

test('successful add user calls zostream subscription when user is active today', function () {
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
            && $path === 'api/v1/add_user'
            && $payload['userName'] === 'customer001')
        ->andReturn([
            'status' => 200,
            'data' => ['message' => 'User added successfully'],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);
    Http::fake([
        config('services.zostream_isp.subscribe_url') => Http::response(['status' => 'success'], 200),
    ]);

    $response = $this->postJson('/api/jaze/users', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'userGroupId' => 'group-1',
        'accountId' => 'account-1',
        'userName' => 'customer001',
        'activationDate' => 'setnow',
        'expirationDate' => now()->addMonth()->toDateString(),
        'phoneNumber' => '9876543210',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'User added successfully');

    Http::assertSent(fn ($request): bool => $request->url() === config('services.zostream_isp.subscribe_url')
        && $request['phone_no'] === '9876543210');
});

test('successful add user does not call zostream subscription for future activation date', function () {
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
            'status' => 200,
            'data' => ['message' => 'User added successfully'],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);
    Http::fake();

    $response = $this->postJson('/api/jaze/users', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'userGroupId' => 'group-1',
        'accountId' => 'account-1',
        'userName' => 'customer001',
        'activationDate' => now()->addDay()->toDateString(),
        'expirationDate' => now()->addMonth()->toDateString(),
        'phoneNumber' => '9876543210',
    ]);

    $response->assertOk();

    Http::assertNothingSent();
});

test('successful add user does not call zostream subscription for expired user', function () {
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
            'status' => 200,
            'data' => ['message' => 'User added successfully'],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);
    Http::fake();

    $response = $this->postJson('/api/jaze/users', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'userGroupId' => 'group-1',
        'accountId' => 'account-1',
        'userName' => 'customer001',
        'activationDate' => now()->subMonth()->toDateString(),
        'expirationDate' => now()->subDay()->toDateString(),
        'phoneNumber' => '9876543210',
    ]);

    $response->assertOk();

    Http::assertNothingSent();
});
