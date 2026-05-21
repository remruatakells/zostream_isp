<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $authAdmin = $this->adminUser($request);
        $query = AdminUser::query()->with('branch')->latest();

        if ($authAdmin->role === 'branch_admin') {
            $query->where('branch_id', $authAdmin->branch_id);
        } elseif (! $this->isSuperAdmin($authAdmin)) {
            $query->where('id', $authAdmin->id);
        }

        return response()->json(
            $query->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $authAdmin = $this->adminUser($request);

        if (! in_array($authAdmin->role, ['super_admin', 'branch_admin'], true)) {
            return $this->forbidden();
        }

        $data = $this->validatedData($request);

        if ($authAdmin->role === 'branch_admin') {
            if (! in_array($data['role'] ?? 'staff', ['staff', 'support'], true)) {
                return $this->forbidden();
            }

            $data['branch_id'] = $authAdmin->branch_id;
        }

        $adminUser = AdminUser::create($data);

        return response()->json($adminUser->load('branch'), 201);
    }

    public function show(Request $request, AdminUser $adminUser): JsonResponse
    {
        if (! $this->canAccessAdminUser($this->adminUser($request), $adminUser)) {
            return $this->forbidden();
        }

        return response()->json($adminUser->load('branch'));
    }

    public function update(Request $request, AdminUser $adminUser): JsonResponse
    {
        $authAdmin = $this->adminUser($request);

        if (! $this->canUpdateAdminUser($authAdmin, $adminUser)) {
            return $this->forbidden();
        }

        $data = $this->validatedData($request, $adminUser);

        if ($authAdmin->role === 'branch_admin') {
            unset($data['role']);
            $data['branch_id'] = $authAdmin->branch_id;
        } elseif (! $this->isSuperAdmin($authAdmin)) {
            unset($data['role'], $data['branch_id'], $data['status']);
        }

        $adminUser->update($data);

        return response()->json($adminUser->refresh()->load('branch'));
    }

    public function destroy(Request $request, AdminUser $adminUser): JsonResponse
    {
        if (! $this->isSuperAdmin($this->adminUser($request))) {
            return $this->forbidden();
        }

        $adminUser->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?AdminUser $adminUser = null): array
    {
        $requiredRule = $adminUser ? 'sometimes' : 'required';
        $passwordRules = $adminUser
            ? ['sometimes', 'string', 'min:8']
            : ['required', 'string', 'min:8'];

        return collect($request->validate([
            'admin_login' => ['sometimes', 'string'],
            'admin_password' => ['sometimes', 'string'],
            'name' => [$requiredRule, 'string', 'max:150'],
            'phone' => [
                $requiredRule,
                'string',
                'max:20',
                Rule::unique('admin_users', 'phone')->ignore($adminUser),
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('admin_users', 'email')->ignore($adminUser),
            ],
            'password' => $passwordRules,
            'role' => ['nullable', Rule::in(['super_admin', 'branch_admin', 'staff', 'support'])],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'blocked'])],
            'last_login_at' => ['nullable', 'date'],
        ]))->except(['admin_login', 'admin_password'])->all();
    }

    private function canAccessAdminUser(AdminUser $authAdmin, AdminUser $targetAdmin): bool
    {
        if ($this->isSuperAdmin($authAdmin)) {
            return true;
        }

        if ($authAdmin->role === 'branch_admin') {
            return (int) $authAdmin->branch_id === (int) $targetAdmin->branch_id;
        }

        return (int) $authAdmin->id === (int) $targetAdmin->id;
    }

    private function canUpdateAdminUser(AdminUser $authAdmin, AdminUser $targetAdmin): bool
    {
        return $this->canAccessAdminUser($authAdmin, $targetAdmin);
    }

    private function isSuperAdmin(AdminUser $adminUser): bool
    {
        return $adminUser->role === 'super_admin';
    }

    private function adminUser(Request $request): AdminUser
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->attributes->get('admin_user');

        if (! $adminUser) {
            $login = $request->input('admin_login', $request->header('X-Admin-Login'));
            $password = $request->input('admin_password', $request->header('X-Admin-Password'));

            if ($login && $password) {
                $candidate = AdminUser::query()
                    ->where('phone', $login)
                    ->orWhere('email', $login)
                    ->first();

                if ($candidate && $candidate->status === 'active' && Hash::check($password, $candidate->password)) {
                    return $candidate;
                }
            }

            abort(response()->json([
                'message' => 'Admin authentication is required.',
            ], 401));
        }

        return $adminUser;
    }

    private function forbidden(): JsonResponse
    {
        return response()->json(['message' => 'Forbidden for this admin role or branch.'], 403);
    }
}
