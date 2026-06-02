<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminUser
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $adminUser = $this->adminUserFromBearerToken($request)
            ?? $this->adminUserFromCredentials($request);

        if (! $adminUser || $adminUser->status !== 'active') {
            return response()->json([
                'message' => 'Authentication is required. Send a bearer token or login credentials.',
            ], 401);
        }

        $request->attributes->set('admin_user', $adminUser);

        return $next($request);
    }

    private function adminUserFromBearerToken(Request $request): ?AdminUser
    {
        $token = $request->bearerToken();

        if (! $token) {
            return null;
        }

        return AdminUser::where('api_token', hash('sha256', $token))->first();
    }

    private function adminUserFromCredentials(Request $request): ?AdminUser
    {
        $login = $request->input('admin_login', $request->header('X-Admin-Login'));
        $password = $request->input('admin_password', $request->header('X-Admin-Password'));

        if (! $login || ! $password) {
            return null;
        }

        $adminUser = AdminUser::query()
            ->where('phone', $login)
            ->orWhere('email', $login)
            ->orWhere('jaze_username', $login)
            ->first();

        if (! $adminUser || ! Hash::check($password, $adminUser->password)) {
            return null;
        }

        return $adminUser;
    }
}
