<?php

use App\Models\AdminUser;
use App\Models\Branch;
use App\Services\JazeApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('customer role can login from shared users table', function () {
    $branch = Branch::create([
        'name' => 'Ngopa',
        'code' => 'NGOPA',
        'status' => 'active',
    ]);

    AdminUser::create([
        'name' => 'Customer',
        'phone' => '9000000100',
        'email' => 'customer@example.com',
        'password' => Hash::make('password123'),
        'role' => 'user',
        'branch_id' => $branch->id,
        'jaze_user_id' => 'jaze-user-1',
        'jaze_username' => 'customer001',
        'status' => 'active',
    ]);

    $response = $this->postJson('/api/admin-login', [
        'login' => 'customer001',
        'password' => 'password123',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('admin_user.role', 'user')
        ->assertJsonPath('admin_user.jaze_user_id', 'jaze-user-1');
});

test('customer role only receives own jaze user from users list', function () {
    $branch = Branch::create([
        'name' => 'Ngopa',
        'code' => 'NGOPA',
        'status' => 'active',
    ]);

    AdminUser::create([
        'name' => 'Customer',
        'phone' => '9000000100',
        'email' => 'customer@example.com',
        'password' => Hash::make('password123'),
        'role' => 'user',
        'branch_id' => $branch->id,
        'jaze_user_id' => 'jaze-user-1',
        'jaze_username' => 'customer001',
        'status' => 'active',
    ]);

    $jaze = Mockery::mock(JazeApiClient::class);
    $jaze->shouldReceive('get')
        ->once()
        ->withArgs(fn (Branch $calledBranch, string $path, array $query): bool => $calledBranch->is($branch)
            && $path === 'api/v1/get_users/1/50/'
            && $query === ['page' => '1', 'per_page' => '50'])
        ->andReturn([
            'status' => 200,
            'data' => [
                'status' => 'success',
                'totalRecords' => 2,
                'data' => [
                    ['id' => 'jaze-user-1', 'username' => 'customer001', 'phone' => '9000000100'],
                    ['id' => 'jaze-user-2', 'username' => 'other001', 'phone' => '9000000101'],
                ],
            ],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);

    $response = $this->getJson('/api/jaze/users?page=1&per_page=50&admin_login=9000000100&admin_password=password123');

    $response
        ->assertOk()
        ->assertJsonPath('totalRecords', 1)
        ->assertJsonPath('data.0.id', 'jaze-user-1')
        ->assertJsonMissing(['id' => 'jaze-user-2']);
});

test('customer role cannot renew another jaze user', function () {
    $branch = Branch::create([
        'name' => 'Ngopa',
        'code' => 'NGOPA',
        'status' => 'active',
    ]);

    AdminUser::create([
        'name' => 'Customer',
        'phone' => '9000000100',
        'email' => 'customer@example.com',
        'password' => Hash::make('password123'),
        'role' => 'user',
        'branch_id' => $branch->id,
        'jaze_user_id' => 'jaze-user-1',
        'jaze_username' => 'customer001',
        'status' => 'active',
    ]);

    $jaze = Mockery::mock(JazeApiClient::class);
    $jaze->shouldNotReceive('post');
    $this->app->instance(JazeApiClient::class, $jaze);

    $response = $this->postJson('/api/jaze/renew', [
        'admin_login' => '9000000100',
        'admin_password' => 'password123',
        'userId' => 'jaze-user-2',
        'renewDefaultSettings' => 'true',
        'isRenewPresentDate' => 'true',
        'phone' => '9000000101',
    ]);

    $response
        ->assertForbidden()
        ->assertJsonPath('message', 'This user can only access their own data.');
});

test('successful jaze add user stores local login and customer can see own data', function () {
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
        ->withArgs(fn (Branch $calledBranch, string $path, array $payload): bool => $calledBranch->is($branch)
            && $path === 'api/v1/add_user'
            && $payload['userName'] === 'customer002')
        ->andReturn([
            'status' => 200,
            'data' => [
                'message' => 'User added successfully',
                'id' => 'jaze-user-2',
                'username' => 'customer002',
            ],
            'successful' => true,
        ]);
    $jaze->shouldReceive('get')
        ->once()
        ->withArgs(fn (Branch $calledBranch, string $path): bool => $calledBranch->is($branch)
            && $path === 'api/v1/get_users/1/50/')
        ->andReturn([
            'status' => 200,
            'data' => [
                'status' => 'success',
                'totalRecords' => 2,
                'data' => [
                    ['id' => 'jaze-user-1', 'username' => 'other001', 'phone' => '9000000101'],
                    ['id' => 'jaze-user-2', 'username' => 'customer002', 'phone' => '9000000102'],
                ],
            ],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);

    $addResponse = $this->postJson('/api/jaze/users', [
        'admin_login' => '9000000001',
        'admin_password' => 'password123',
        'userGroupId' => 'group-1',
        'accountId' => 'account-1',
        'userName' => 'customer002',
        'firstName' => 'Customer',
        'lastName' => 'Two',
        'password' => 'customerpass',
        'activationDate' => now()->addDay()->toDateString(),
        'expirationDate' => now()->addMonth()->toDateString(),
        'phoneNumber' => '9000000102',
        'emailId' => 'customer002@example.com',
    ]);

    $addResponse->assertOk();

    $this->assertDatabaseHas('admin_users', [
        'name' => 'Customer Two',
        'phone' => '9000000102',
        'email' => 'customer002@example.com',
        'role' => 'user',
        'branch_id' => $branch->id,
        'jaze_user_id' => 'jaze-user-2',
        'jaze_username' => 'customer002',
        'status' => 'active',
    ]);

    $loginResponse = $this->postJson('/api/admin-login', [
        'login' => 'customer002',
        'password' => 'customerpass',
    ]);

    $token = $loginResponse
        ->assertOk()
        ->assertJsonPath('admin_user.role', 'user')
        ->json('token');

    $usersResponse = $this->withToken($token)->getJson('/api/jaze/users?page=1&per_page=50');

    $usersResponse
        ->assertOk()
        ->assertJsonPath('totalRecords', 1)
        ->assertJsonPath('data.0.id', 'jaze-user-2')
        ->assertJsonMissing(['id' => 'jaze-user-1']);
});

test('super admin add user can select branch from account id', function () {
    $branch = Branch::create([
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
    $jaze->shouldReceive('post')
        ->once()
        ->withArgs(fn (Branch $calledBranch, string $path, array $payload): bool => $calledBranch->is($branch)
            && $path === 'api/v1/add_user'
            && $payload['accountId'] === 'NGOPA')
        ->andReturn([
            'status' => 200,
            'data' => ['message' => 'User added successfully'],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);

    $response = $this->postJson('/api/jaze/users', [
        'admin_login' => '9000000000',
        'admin_password' => 'password123',
        'userGroupId' => 'group-1',
        'accountId' => 'NGOPA',
        'userName' => 'customer003',
        'activationDate' => now()->addDay()->toDateString(),
        'expirationDate' => now()->addMonth()->toDateString(),
        'phoneNumber' => '9000000103',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'User added successfully');
});

test('customer role only receives own jaze user from all users response', function () {
    $branch = Branch::create([
        'name' => 'Ngopa',
        'code' => 'NGOPA',
        'status' => 'active',
    ]);

    AdminUser::create([
        'name' => 'Customer',
        'phone' => '9000000100',
        'email' => 'customer@example.com',
        'password' => Hash::make('password123'),
        'role' => 'user',
        'branch_id' => $branch->id,
        'jaze_user_id' => 'jaze-user-1',
        'jaze_username' => 'customer001',
        'status' => 'active',
    ]);

    $jaze = Mockery::mock(JazeApiClient::class);
    $jaze->shouldReceive('get')
        ->once()
        ->withArgs(fn (Branch $calledBranch, string $path, array $query): bool => $calledBranch->is($branch)
            && $path === 'api/v1/get_all'
            && $query === [])
        ->andReturn([
            'status' => 200,
            'data' => [
                ['id' => 'jaze-user-1', 'username' => 'customer001', 'phone' => '9000000100'],
                ['id' => 'jaze-user-2', 'username' => 'other001', 'phone' => '9000000101'],
            ],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);

    $response = $this->getJson('/api/jaze/users/all?admin_login=9000000100&admin_password=password123');

    $response
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', 'jaze-user-1')
        ->assertJsonMissing(['id' => 'jaze-user-2']);
});
