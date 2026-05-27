<?php

use App\Models\AdminUser;
use App\Models\Branch;
use App\Services\JazeApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('admin can get user logoff time and online status', function () {
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
    $jaze->shouldReceive('get')
        ->once()
        ->withArgs(fn (Branch $calledBranch, string $path, array $query): bool => $calledBranch->is($branch)
            && $path === 'api/v1/get_logofftime_onlinestatus/user-123'
            && $query === [])
        ->andReturn([
            'status' => 200,
            'data' => [
                'userId' => 'user-123',
                'onlineStatus' => 'online',
                'logoffTime' => null,
            ],
            'successful' => true,
        ]);

    $this->app->instance(JazeApiClient::class, $jaze);

    $response = $this->getJson('/api/v1/get_logofftime_onlinestatus/user-123?admin_login=9000000001&admin_password=password123');

    $response
        ->assertOk()
        ->assertJsonPath('userId', 'user-123')
        ->assertJsonPath('onlineStatus', 'online');
});
