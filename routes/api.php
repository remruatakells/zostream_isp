<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\JazeApiController;
use App\Http\Controllers\JazePlanController;
use App\Http\Controllers\RenewSuccessLogController;
use Illuminate\Support\Facades\Route;

Route::post('admin-login', [AdminAuthController::class, 'login']);
Route::post('admin-users/add', [AdminUserController::class, 'store']);

Route::middleware('admin.auth')->group(function (): void {
    Route::get('admin-me', [AdminAuthController::class, 'me']);
    Route::post('admin-logout', [AdminAuthController::class, 'logout']);

    Route::apiResource('branches', BranchController::class);
    Route::apiResource('admin-users', AdminUserController::class);
    Route::apiResource('jaze-plans', JazePlanController::class);

    Route::prefix('jaze')->group(function (): void {
        Route::post('auth/authenticate', [JazeApiController::class, 'authenticate']);

        Route::get('users', [JazeApiController::class, 'users']);
        Route::get('users/all', [JazeApiController::class, 'allUsers']);
        Route::post('users', [JazeApiController::class, 'addUser']);
        Route::post('users/{userId}', [JazeApiController::class, 'editUser']);
        Route::get('users/username/{username}', [JazeApiController::class, 'userByUsername']);
        Route::get('users/{userId}/logofftime-onlinestatus', [JazeApiController::class, 'userLogoffTimeOnlineStatus']);
        Route::get('users/{userId}', [JazeApiController::class, 'userDetails']);
        Route::get('users/{userId}/balance', [JazeApiController::class, 'userBalance']);
        Route::get('groups', [JazeApiController::class, 'groupDetails']);
        Route::get('groups/{groupId}', [JazeApiController::class, 'groupDetailsById']);
        Route::get('users-count/{accountId}', [JazeApiController::class, 'usersCount']);

        Route::post('payments', [JazeApiController::class, 'makePayment']);
        Route::get('payments/users/{userId}', [JazeApiController::class, 'paymentDetails']);

        Route::post('renew/default-settings', [JazeApiController::class, 'renewDefaultSettings']);
        Route::post('renew', [JazeApiController::class, 'renew']);
        Route::get('renew-successes', [RenewSuccessLogController::class, 'index']);
        Route::post('renew-successes', [RenewSuccessLogController::class, 'store']);
        Route::get('renew-successes/{renewSuccessLog}', [RenewSuccessLogController::class, 'show']);

        Route::post('tickets', [JazeApiController::class, 'raiseTicket']);
        Route::post('tickets/search', [JazeApiController::class, 'tickets']);
        Route::get('tickets/{ticketId}', [JazeApiController::class, 'ticketDetails']);

        Route::get('accounts/admins', [JazeApiController::class, 'admins']);
        Route::get('accounts/{accountId}', [JazeApiController::class, 'accountDetails']);

        Route::get('profiles/bandwidth', [JazeApiController::class, 'bandwidthDetails']);
        Route::get('profiles/{profileId}', [JazeApiController::class, 'profileDetails']);
    });

    Route::get('v1/get_logofftime_onlinestatus/{userId}', [JazeApiController::class, 'userLogoffTimeOnlineStatus']);
});
