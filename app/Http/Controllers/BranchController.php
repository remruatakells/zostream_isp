<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $adminUser = $this->adminUser($request);

        if ($adminUser->isCustomerRole()) {
            return $this->forbidden();
        }

        $query = Branch::query()->withCount('adminUsers')->latest();

        if ($adminUser->role !== 'super_admin') {
            $query->where('id', $adminUser->branch_id);
        }

        return response()->json(
            $query->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->isSuperAdmin($request)) {
            return $this->forbidden();
        }

        $branch = Branch::create($this->validatedData($request));

        return response()->json($branch, 201);
    }

    public function show(Request $request, Branch $branch): JsonResponse
    {
        if ($this->adminUser($request)->isCustomerRole()) {
            return $this->forbidden();
        }

        if (! $this->canAccessBranch($request, $branch)) {
            return $this->forbidden();
        }

        return response()->json($branch->load('adminUsers'));
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        if (! $this->canUpdateBranch($request, $branch)) {
            return $this->forbidden();
        }

        $branch->update($this->validatedData($request, $branch));

        return response()->json($branch->refresh());
    }

    public function destroy(Request $request, Branch $branch): JsonResponse
    {
        if (! $this->isSuperAdmin($request)) {
            return $this->forbidden();
        }

        $branch->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Branch $branch = null): array
    {
        $requiredRule = $branch ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$requiredRule, 'string', 'max:100'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches', 'code')->ignore($branch),
            ],
            'location' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'jaze_api_token' => ['nullable', 'string'],
            'jaze_api_key' => ['nullable', 'string'],
        ]);
    }

    private function canAccessBranch(Request $request, Branch $branch): bool
    {
        $adminUser = $this->adminUser($request);

        return $adminUser->role === 'super_admin'
            || (int) $adminUser->branch_id === (int) $branch->id;
    }

    private function canUpdateBranch(Request $request, Branch $branch): bool
    {
        $adminUser = $this->adminUser($request);

        return $adminUser->role === 'super_admin'
            || ($adminUser->role === 'branch_admin' && (int) $adminUser->branch_id === (int) $branch->id);
    }

    private function isSuperAdmin(Request $request): bool
    {
        return $this->adminUser($request)->role === 'super_admin';
    }

    private function adminUser(Request $request): AdminUser
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->attributes->get('admin_user');

        return $adminUser;
    }

    private function forbidden(): JsonResponse
    {
        return response()->json(['message' => 'Forbidden for this role or branch.'], 403);
    }
}
