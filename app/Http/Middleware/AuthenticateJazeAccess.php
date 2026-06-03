<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJazeAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $branch = $this->branchFromBasicAuth($request);

        if ($branch) {
            $request->attributes->set('jaze_branch', $branch);
            $request->merge([
                'branch_id' => $branch->id,
                'branch_code' => $branch->code,
                'accountId' => $branch->jaze_account_id ?: $branch->code,
            ]);

            return $next($request);
        }

        $adminUser = $this->adminUserFromBearerToken($request)
            ?? $this->adminUserFromBasicAuth($request)
            ?? $this->adminUserFromCredentials($request);

        if (! $adminUser || $adminUser->status !== 'active') {
            return response()->json([
                'message' => 'Authentication is required. Send branch basic auth or admin credentials.',
            ], 401);
        }

        $request->attributes->set('admin_user', $adminUser);

        return $next($request);
    }

    private function branchFromBasicAuth(Request $request): ?Branch
    {
        $token = $request->getUser();
        $key = $request->getPassword();

        if (! $token || ! $key) {
            return null;
        }

        return Branch::findByJazeCredentials($token, $key);
    }

    private function adminUserFromBearerToken(Request $request): ?AdminUser
    {
        $token = $request->bearerToken();

        if (! $token) {
            return null;
        }

        return AdminUser::where('api_token', hash('sha256', $token))->first();
    }

    private function adminUserFromBasicAuth(Request $request): ?AdminUser
    {
        $login = $request->getUser();
        $password = $request->getPassword();

        if (! $login || ! $password) {
            return null;
        }

        $adminUser = AdminUser::findByLogin($login);

        if (! $adminUser || ! Hash::check($password, $adminUser->password)) {
            return null;
        }

        return $adminUser;
    }

    private function adminUserFromCredentials(Request $request): ?AdminUser
    {
        $login = $request->input('admin_login', $request->header('X-Admin-Login'));
        $password = $request->input('admin_password', $request->header('X-Admin-Password'));

        if (! $login || ! $password) {
            return null;
        }

        $adminUser = AdminUser::findByLogin($login);

        if (! $adminUser || ! Hash::check($password, $adminUser->password)) {
            return null;
        }

        return $adminUser;
    }
}
