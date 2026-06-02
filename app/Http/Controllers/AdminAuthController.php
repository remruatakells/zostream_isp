<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $adminUser = AdminUser::query()
            ->where('phone', $credentials['login'])
            ->orWhere('email', $credentials['login'])
            ->orWhere('jaze_username', $credentials['login'])
            ->first();

        if (! $adminUser || ! Hash::check($credentials['password'], $adminUser->password)) {
            return response()->json(['message' => 'Invalid login or password.'], 401);
        }

        if ($adminUser->status !== 'active') {
            return response()->json(['message' => 'User is not active.'], 403);
        }

        $token = Str::random(80);
        $adminUser->forceFill([
            'api_token' => hash('sha256', $token),
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'admin_user' => $adminUser->load('branch'),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->adminUser($request)->load('branch'));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->adminUser($request)->forceFill(['api_token' => null])->save();

        return response()->json(['message' => 'Logged out.']);
    }

    private function adminUser(Request $request): AdminUser
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->attributes->get('admin_user');

        return $adminUser;
    }
}
