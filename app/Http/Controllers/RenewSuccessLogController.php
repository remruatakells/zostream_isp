<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Branch;
use App\Models\RenewSuccessLog;
use App\Services\JazeApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Throwable;

class RenewSuccessLogController extends Controller
{
    public function __construct(private readonly JazeApiClient $jaze) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $adminUser = $this->adminUser($request);
        $query = RenewSuccessLog::query()
            ->with(['branch', 'adminUser'])
            ->latest();

        if ($adminUser->isCustomerRole()) {
            $query->where('admin_user_id', $adminUser->id);
        } elseif ($adminUser->role !== 'super_admin') {
            $query->where('branch_id', $adminUser->branch_id);
        }

        if (isset($filters['month'])) {
            $month = Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth();
            $query->whereBetween('renewed_at', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ]);
        }

        $renewSuccessLogs = $query->paginate(15);
        $renewSuccessLogs->getCollection()->transform(
            fn (RenewSuccessLog $renewSuccessLog): RenewSuccessLog => $this->withJazeUser($renewSuccessLog)
        );

        return response()->json($renewSuccessLogs);
    }

    public function store(Request $request): JsonResponse
    {
        $adminUser = $this->adminUser($request);

        if ($adminUser->isCustomerRole() && ! $this->customerOwnsLogPayload($adminUser, $request)) {
            return response()->json(['message' => 'This user can only store their own renew success data.'], 403);
        }

        $branch = $this->branchForAdmin($adminUser, $request);

        if (! $branch) {
            return response()->json([
                'message' => $adminUser->role === 'super_admin'
                    ? 'branch_id or branch_code is required to store renew success data.'
                    : 'This admin user is not assigned to a branch.',
            ], 422);
        }

        if (! $this->adminCanUseBranch($adminUser, $branch)) {
            return response()->json(['message' => 'This admin user cannot access the requested branch.'], 403);
        }

        $data = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'branch_code' => ['nullable', 'string'],
            'userId' => ['nullable'],
            'user_id' => ['nullable'],
            'userName' => ['nullable', 'string'],
            'username' => ['nullable', 'string'],
            'accountId' => ['nullable'],
            'account_id' => ['nullable'],
            'status' => ['nullable', 'string', 'max:50'],
            'renewed_at' => ['nullable', 'date'],
        ]);

        $renewSuccessLog = RenewSuccessLog::create([
            'branch_id' => $branch->id,
            'admin_user_id' => $adminUser->id,
            'jaze_user_id' => $data['userId'] ?? $data['user_id'] ?? null,
            'jaze_username' => $data['userName'] ?? $data['username'] ?? null,
            'account_id' => $data['accountId'] ?? $data['account_id'] ?? null,
            'status' => $data['status'] ?? 'success',
            'payload' => $this->payload($request),
            'renewed_at' => isset($data['renewed_at']) ? Carbon::parse($data['renewed_at']) : now(),
        ]);

        return response()->json($renewSuccessLog->load(['branch', 'adminUser']), 201);
    }

    public function show(Request $request, RenewSuccessLog $renewSuccessLog): JsonResponse
    {
        if (! $this->canAccessRenewSuccessLog($this->adminUser($request), $renewSuccessLog)) {
            return response()->json(['message' => 'Forbidden for this admin role or branch.'], 403);
        }

        return response()->json($renewSuccessLog->load(['branch', 'adminUser']));
    }

    private function branchForAdmin(AdminUser $adminUser, Request $request): ?Branch
    {
        $requestedBranch = $this->requestedBranch($request);
        $requestedBranchWasProvided = $request->filled('branch_id') || $request->filled('branch_code');

        if ($requestedBranchWasProvided && ! $requestedBranch) {
            return null;
        }

        if ($adminUser->role === 'super_admin') {
            return $requestedBranch;
        }

        return $requestedBranch ?? $adminUser->branch;
    }

    private function requestedBranch(Request $request): ?Branch
    {
        if ($request->filled('branch_id')) {
            return Branch::find($request->input('branch_id'));
        }

        if ($request->filled('branch_code')) {
            return Branch::where('code', $request->input('branch_code'))->first();
        }

        return null;
    }

    private function adminCanUseBranch(AdminUser $adminUser, Branch $branch): bool
    {
        return $adminUser->role === 'super_admin'
            || (int) $adminUser->branch_id === (int) $branch->id;
    }

    private function canAccessRenewSuccessLog(AdminUser $adminUser, RenewSuccessLog $renewSuccessLog): bool
    {
        if ($adminUser->isCustomerRole()) {
            return (int) $adminUser->id === (int) $renewSuccessLog->admin_user_id;
        }

        return $adminUser->role === 'super_admin'
            || (int) $adminUser->branch_id === (int) $renewSuccessLog->branch_id;
    }

    private function customerOwnsLogPayload(AdminUser $adminUser, Request $request): bool
    {
        $jazeUserId = $request->input('userId', $request->input('user_id'));
        $jazeUsername = $request->input('userName', $request->input('username'));

        return $this->sameFilledValue($adminUser->jaze_user_id, $jazeUserId)
            || $this->sameFilledValue($adminUser->jaze_username, $jazeUsername);
    }

    private function sameFilledValue(mixed $expected, mixed $actual): bool
    {
        $expectedValue = strtolower(trim((string) $expected));
        $actualValue = strtolower(trim((string) $actual));

        return $expectedValue !== '' && $actualValue !== '' && $expectedValue === $actualValue;
    }

    private function withJazeUser(RenewSuccessLog $renewSuccessLog): RenewSuccessLog
    {
        if (! $renewSuccessLog->branch || ! $renewSuccessLog->jaze_user_id) {
            $renewSuccessLog->setAttribute('jaze_user', null);

            return $renewSuccessLog;
        }

        try {
            $response = $this->jaze->get(
                $renewSuccessLog->branch,
                'api/v1/get_details/'.rawurlencode((string) $renewSuccessLog->jaze_user_id)
            );
        } catch (Throwable $exception) {
            $renewSuccessLog->setAttribute('jaze_user', null);
            $renewSuccessLog->setAttribute('jaze_user_status', 503);
            $renewSuccessLog->setAttribute('jaze_user_error', $exception->getMessage());

            return $renewSuccessLog;
        }

        $renewSuccessLog->setAttribute('jaze_user', $response['data']);
        $renewSuccessLog->setAttribute('jaze_user_status', $response['status']);

        return $renewSuccessLog;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        return collect($request->all())
            ->except(['admin_login', 'admin_password', 'branch_id', 'branch_code'])
            ->all();
    }

    private function adminUser(Request $request): AdminUser
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->attributes->get('admin_user');

        return $adminUser;
    }
}
