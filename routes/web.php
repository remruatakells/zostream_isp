<?php

use App\Http\Controllers\JazeApiController;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});

Route::match(['get', 'post'], '/test-user', function (Request $request, JazeApiController $controller) {
    $defaults = [
        'admin_login' => '8732856261',
        'admin_password' => 'password123',
        'branch_code' => 'pho',
        'userGroupId' => '1',
        'accountId' => 'pho',
        'userName' => 'TEST'.now()->format('His'),
        'password' => 'password123',
        'userState' => 'active',
        'userType' => 'home',
        'activationDate' => 'now',
        'expirationDate' => 'never',
        'customExpirationDate' => '',
        'phoneNumber' => '8732856261',
        'emailId' => 'test@example.com',
        'firstName' => 'Test',
        'lastName' => 'User',
    ];

    if ($request->isMethod('get')) {
        return response()->make(view('test-user', ['defaults' => $defaults]));
    }

    $adminUser = AdminUser::where('phone', $request->input('admin_login'))->first();
    if (! $adminUser || ! Hash::check((string) $request->input('admin_password'), $adminUser->password)) {
        return response()->json(['message' => 'Invalid admin login/password.'], 401);
    }

    $request->attributes->set('admin_user', $adminUser);

    return $controller->addUser($request);
});

Route::get('/test-group', function (Request $request, JazeApiController $controller) {
    $adminLogin = $request->query('admin_login', '8732856261');
    $adminPassword = $request->query('admin_password', 'password123');
    $groupId = (string) $request->query('groupId', '631431159525504');

    $adminUser = AdminUser::where('phone', $adminLogin)->first();
    if (! $adminUser || ! Hash::check((string) $adminPassword, $adminUser->password)) {
        return response()->json(['message' => 'Invalid admin login/password.'], 401);
    }

    $request->attributes->set('admin_user', $adminUser);
    $request->merge([
        'branch_code' => $request->query('branch_code', 'pho'),
        'accountId' => $request->query('accountId', 'pho'),
    ]);

    return $controller->groupDetailsById($request, $groupId);
});
