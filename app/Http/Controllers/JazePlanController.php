<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\JazePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JazePlanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            JazePlan::query()
                ->orderBy('group_name')
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->canManagePlans($request)) {
            return $this->forbidden();
        }

        $jazePlan = JazePlan::create($this->validatedData($request));

        return response()->json($jazePlan, 201);
    }

    public function show(JazePlan $jazePlan): JsonResponse
    {
        return response()->json($jazePlan);
    }

    public function update(Request $request, JazePlan $jazePlan): JsonResponse
    {
        if (! $this->canManagePlans($request)) {
            return $this->forbidden();
        }

        $jazePlan->update($this->validatedData($request, $jazePlan));

        return response()->json($jazePlan->refresh());
    }

    public function destroy(Request $request, JazePlan $jazePlan): JsonResponse
    {
        if (! $this->canManagePlans($request)) {
            return $this->forbidden();
        }

        $jazePlan->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?JazePlan $jazePlan = null): array
    {
        $requiredRule = $jazePlan ? 'sometimes' : 'required';

        return $request->validate([
            'group_id' => [
                $requiredRule,
                'string',
                'max:100',
                Rule::unique('jaze_plans', 'group_id')->ignore($jazePlan),
            ],
            'group_name' => [$requiredRule, 'string', 'max:150'],
            'profile_id' => ['nullable', 'string', 'max:100'],
            'profile_name' => [$requiredRule, 'string', 'max:150'],
            'amount' => [$requiredRule, 'numeric', 'min:0', 'max:99999999.99'],
        ]);
    }

    private function canManagePlans(Request $request): bool
    {
        return in_array($this->adminUser($request)->role, ['super_admin', 'branch_admin'], true);
    }

    private function adminUser(Request $request): AdminUser
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->attributes->get('admin_user');

        return $adminUser;
    }

    private function forbidden(): JsonResponse
    {
        return response()->json(['message' => 'Forbidden for this admin role or branch.'], 403);
    }
}
