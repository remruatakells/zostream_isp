<?php

use App\Http\Controllers\JazeApiController;
use App\Models\AdminUser;
use App\Models\JazePlan;
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
        'userGroupId' => '631431159525504',
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
        $groups = [];
        $groupError = null;
        $adminUser = AdminUser::where('phone', $defaults['admin_login'])->first();

        if ($adminUser && Hash::check((string) $defaults['admin_password'], $adminUser->password)) {
            $request->attributes->set('admin_user', $adminUser);
            $request->merge([
                'branch_code' => $defaults['branch_code'],
                'accountId' => $defaults['accountId'],
            ]);

            $groupsResponse = $controller->groupDetails($request);
            $groupsPayload = $groupsResponse->getData(true);
            $groups = is_array(data_get($groupsPayload, 'data')) ? data_get($groupsPayload, 'data') : [];

            if ($groups === []) {
                $groupError = is_string(data_get($groupsPayload, 'message'))
                    ? data_get($groupsPayload, 'message')
                    : 'No live Jaze groups returned for this branch.';
            } else {
                $defaults['userGroupId'] = (string) data_get($groups, '0.Group_id', $defaults['userGroupId']);
            }
        } else {
            $groupError = 'Invalid default admin login/password.';
        }

        return response()->make(view('test-user', [
            'defaults' => $defaults,
            'groups' => $groups,
            'groupError' => $groupError,
        ]));
    }

    $adminUser = AdminUser::where('phone', $request->input('admin_login'))->first();
    if (! $adminUser || ! Hash::check((string) $request->input('admin_password'), $adminUser->password)) {
        return response()->json(['message' => 'Invalid admin login/password.'], 401);
    }

    $request->attributes->set('admin_user', $adminUser);
    $userGroupId = trim((string) $request->input('userGroupId'));
    $jazePlan = JazePlan::query()
        ->where('group_id', $userGroupId)
        ->orWhere('user_group_id', $userGroupId)
        ->first();

    if ($jazePlan) {
        $request->merge(['userGroupId' => $jazePlan->group_id]);
    }

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

Route::view('/{any}', 'app')
    ->where('any', '^(?!api).*$');
