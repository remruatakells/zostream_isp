<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\JazePlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JazePlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->scopedQuery($request)
                ->with('branch:id,name,code')
                ->orderBy('group_name')
                ->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->canManagePlans($request)) {
            return $this->forbidden();
        }

        $branchId = $this->branchIdForWrite($request);
        if (! $branchId) {
            return response()->json(['message' => 'branch_id is required for this admin.'], 422);
        }

        $jazePlan = JazePlan::create($this->validatedData($request, branchId: $branchId));

        return response()->json($jazePlan->load('branch:id,name,code'), 201);
    }

    public function show(Request $request, string $jazePlan): JsonResponse
    {
        return response()->json($this->findPlan($request, $jazePlan)->load('branch:id,name,code'));
    }

    public function update(Request $request, string $jazePlan): JsonResponse
    {
        if (! $this->canManagePlans($request)) {
            return $this->forbidden();
        }

        $jazePlan = $this->findPlan($request, $jazePlan);
        $branchId = $this->branchIdForWrite($request, $jazePlan);
        if (! $branchId) {
            return response()->json(['message' => 'branch_id is required for this admin.'], 422);
        }

        $jazePlan->update($this->validatedData($request, $jazePlan, $branchId));

        return response()->json($jazePlan->refresh()->load('branch:id,name,code'));
    }

    public function destroy(Request $request, string $jazePlan): JsonResponse
    {
        if (! $this->canManagePlans($request)) {
            return $this->forbidden();
        }

        $jazePlan = $this->findPlan($request, $jazePlan);
        $jazePlan->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?JazePlan $jazePlan = null, ?int $branchId = null): array
    {
        $requiredRule = $jazePlan ? 'sometimes' : 'required';
        $branchId ??= $this->branchIdForWrite($request, $jazePlan);

        $data = $request->validate([
            'branch_id' => [
                $this->adminUser($request)->role === 'super_admin' && ! $jazePlan ? 'required' : 'sometimes',
                'integer',
                Rule::exists('branches', 'id'),
            ],
            'group_id' => [
                $requiredRule,
                'string',
                'max:100',
                Rule::unique('jaze_plans', 'group_id')
                    ->where(fn ($query) => $query->where('branch_id', $branchId))
                    ->ignore($jazePlan?->id),
            ],
            'user_group_id' => ['nullable', 'string', 'max:100'],
            'group_name' => [$requiredRule, 'string', 'max:150'],
            'profile_id' => ['nullable', 'string', 'max:100'],
            'profile_name' => [$requiredRule, 'string', 'max:150'],
            'amount' => [$requiredRule, 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        if ($branchId) {
            $data['branch_id'] = $branchId;
        }

        return $data;
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

    private function scopedQuery(Request $request): Builder
    {
        $query = JazePlan::query();
        $adminUser = $this->adminUser($request);

        if ($adminUser->role === 'branch_admin') {
            return $query->where('branch_id', $adminUser->branch_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        return $query;
    }

    private function findPlan(Request $request, string $value): JazePlan
    {
        $query = $this->scopedQuery($request);

        $query->where(function (Builder $query) use ($value): void {
            if (ctype_digit($value)) {
                $query->where('id', (int) $value)
                    ->orWhere('group_id', $value)
                    ->orWhere('user_group_id', $value);

                return;
            }

            $query->where('group_id', $value)
                ->orWhere('user_group_id', $value);
        });

        return $query->firstOrFail();
    }

    private function branchIdForWrite(Request $request, ?JazePlan $jazePlan = null): ?int
    {
        $adminUser = $this->adminUser($request);

        if ($adminUser->role === 'branch_admin') {
            return $adminUser->branch_id ? (int) $adminUser->branch_id : null;
        }

        if ($request->filled('branch_id')) {
            return $request->integer('branch_id');
        }

        return $jazePlan?->branch_id ? (int) $jazePlan->branch_id : null;
    }
}
