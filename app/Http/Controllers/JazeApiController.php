<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Branch;
use App\Services\JazeApiClient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class JazeApiController extends Controller
{
    public function __construct(private readonly JazeApiClient $jaze) {}

    public function authenticate(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        return $this->post($request, 'api/v1/authenticate_user');
    }

    public function users(Request $request): JsonResponse
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
            'status' => ['nullable', 'string'],
        ]);

        return $this->get(
            $request,
            'api/v1/get_users/{page}/{perPage}/{status}',
            [
                'page' => $request->query('page', 1),
                'perPage' => $request->query('per_page', 50),
                'status' => $request->query('status', ''),
            ]
        );
    }

    public function allUsers(Request $request): JsonResponse
    {
        return $this->get($request, 'api/v1/get_all');
    }

    public function userDetails(Request $request, string $userId): JsonResponse
    {
        return $this->get($request, 'api/v1/get_details/{userId}', ['userId' => $userId]);
    }

    public function userByUsername(Request $request, string $username): JsonResponse
    {
        return $this->get($request, 'api/v1/get_user_by_username/{username}', ['username' => $username]);
    }

    public function userBalance(Request $request, string $userId): JsonResponse
    {
        return $this->get($request, 'api/v1/get_balance/{userId}', ['userId' => $userId]);
    }

    public function groupDetails(Request $request): JsonResponse
    {
        return $this->get($request, 'api/v1/get_group_details');
    }

    public function groupDetailsById(Request $request, string $groupId): JsonResponse
    {
        return $this->get($request, 'api/v1/get_group_details/{groupId}', ['groupId' => $groupId]);
    }

    public function usersCount(Request $request, string $accountId): JsonResponse
    {
        return $this->get($request, 'api/v1/get_users_count/{accountId}', ['accountId' => $accountId]);
    }

    public function addUser(Request $request): JsonResponse
    {
        $request->validate([
            'userGroupId' => ['required'],
            'accountId' => ['required'],
            'userName' => ['required', 'string'],
        ]);

        return $this->post($request, 'api/v1/add_user');
    }

    public function editUser(Request $request, string $userId): JsonResponse
    {
        $request->merge(['userId' => $userId]);

        return $this->post($request, 'api/v1/add_user');
    }

    public function makePayment(Request $request): JsonResponse
    {
        $request->validate([
            'userId' => ['required'],
            'amount' => ['required', 'numeric'],
            'method' => ['required', 'string'],
            'notes' => ['required', 'string'],
        ]);

        return $this->post($request, 'api/v1/make_payment');
    }

    public function paymentDetails(Request $request, string $userId): JsonResponse
    {
        return $this->get($request, 'api/v1/get_payment_details/{userId}', ['userId' => $userId]);
    }

    public function renewDefaultSettings(Request $request): JsonResponse
    {
        $request->validate([
            'userBillingId' => ['required'],
        ]);

        return $this->post($request, 'api/v1/renew_default_settings');
    }

    public function renew(Request $request): JsonResponse
    {
        $request->validate([
            'userId' => ['required'],
            'renewDefaultSettings' => ['required'],
            'isRenewPresentDate' => ['required'],
        ]);

        return $this->post($request, 'api/v1/renew');
    }

    public function raiseTicket(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string'],
            'subject' => ['required', 'string'],
            'userId' => ['required_without:internalTicket'],
            'comments' => ['required', 'string'],
        ]);

        return $this->post($request, 'api/v1/raise_ticket');
    }

    public function tickets(Request $request): JsonResponse
    {
        $request->validate([
            'adminUsername' => ['required', 'string'],
            'perPage' => ['required', 'integer', 'min:1', 'max:500'],
            'page' => ['required', 'integer', 'min:1'],
        ]);

        return $this->post($request, 'api/v1/get_all_tickets');
    }

    public function ticketDetails(Request $request, string $ticketId): JsonResponse
    {
        return $this->get(
            $request,
            'api/v1/get_ticket_details/{ticketId}/{dontShowToUser}',
            [
                'ticketId' => $ticketId,
                'dontShowToUser' => $request->query('dont_show_to_user', 0),
            ]
        );
    }

    public function accountDetails(Request $request, string $accountId): JsonResponse
    {
        return $this->get($request, 'api/v1/get_account_details/{accountId}', ['accountId' => $accountId]);
    }

    public function admins(Request $request): JsonResponse
    {
        return $this->get($request, 'api/v1/get_all_admins');
    }

    public function profileDetails(Request $request, string $profileId): JsonResponse
    {
        return $this->get($request, 'api/v1/get_profile_details/{profileId}', ['profileId' => $profileId]);
    }

    public function bandwidthDetails(Request $request): JsonResponse
    {
        return $this->get($request, 'api/v1/get_bandwidth_details');
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function get(Request $request, string $path, array $parameters = []): JsonResponse
    {
        return $this->send($request, 'get', $path, $parameters);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function post(Request $request, string $path, array $parameters = []): JsonResponse
    {
        return $this->send($request, 'post', $path, $parameters);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function send(Request $request, string $method, string $path, array $parameters = []): JsonResponse
    {
        $adminUser = $this->adminUser($request);
        $branches = $this->branchesForAdmin($adminUser, $request, $method);

        if ($branches === null) {
            return response()->json([
                'message' => $adminUser->role === 'super_admin'
                    ? 'branch_id or branch_code is required for super_admin Jaze API write calls.'
                    : 'This admin user is not assigned to a branch.',
            ], 422);
        }

        if ($branches->isEmpty()) {
            return response()->json(['message' => 'No branches are available for this Jaze API call.'], 422);
        }

        $path = $this->replacePathParameters($path, $parameters);

        if ($branches->count() > 1) {
            return $this->sendToBranches($branches, $method, $path, $request);
        }

        $branch = $branches->first();

        try {
            $response = $method === 'get'
                ? $this->jaze->get($branch, $path, $this->queryPayload($request))
                : $this->jaze->post($branch, $path, $this->bodyPayload($request));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        return response()->json($response['data'], $response['status']);
    }

    private function adminUser(Request $request): ?AdminUser
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->attributes->get('admin_user');

        return $adminUser;
    }

    /**
     * @return Collection<int, Branch>|null
     */
    private function branchesForAdmin(AdminUser $adminUser, Request $request, string $method): ?Collection
    {
        $requestedBranch = $this->requestedBranch($request);
        $requestedBranchWasProvided = $request->filled('branch_id') || $request->filled('branch_code');

        if ($requestedBranchWasProvided && ! $requestedBranch) {
            return new Collection;
        }

        if ($adminUser->role !== 'super_admin') {
            if ($requestedBranch && ! $this->adminCanUseBranch($adminUser, $requestedBranch)) {
                abort(response()->json(['message' => 'This admin user cannot access the requested branch.'], 403));
            }

            return $adminUser->branch
                ? new Collection([$adminUser->branch])
                : null;
        }

        if ($requestedBranch) {
            return new Collection([$requestedBranch]);
        }

        if ($method !== 'get') {
            return null;
        }

        return Branch::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, Branch>  $branches
     */
    private function sendToBranches(Collection $branches, string $method, string $path, Request $request): JsonResponse
    {
        $results = [];
        $status = 200;

        foreach ($branches as $branch) {
            try {
                $response = $method === 'get'
                    ? $this->jaze->get($branch, $path, $this->queryPayload($request))
                    : $this->jaze->post($branch, $path, $this->bodyPayload($request));
            } catch (Throwable $exception) {
                $status = 207;
                $results[] = $this->branchResult($branch, [
                    'status' => 503,
                    'data' => ['message' => $exception->getMessage()],
                    'successful' => false,
                ]);

                continue;
            }

            if (! $response['successful']) {
                $status = 207;
            }

            $results[] = $this->branchResult($branch, $response);
        }

        return response()->json(['branches' => $results], $status);
    }

    /**
     * @param  array{status: int, data: mixed, successful: bool}  $response
     * @return array<string, mixed>
     */
    private function branchResult(Branch $branch, array $response): array
    {
        return [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
            ],
            'status' => $response['status'],
            'successful' => $response['successful'],
            'data' => $response['data'],
        ];
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

    /**
     * @return array<string, mixed>
     */
    private function queryPayload(Request $request): array
    {
        return collect($request->query())
            ->except(['admin_login', 'admin_password', 'branch_id', 'branch_code'])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function bodyPayload(Request $request): array
    {
        return collect($request->all())
            ->except(['admin_login', 'admin_password', 'branch_id', 'branch_code'])
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function replacePathParameters(string $path, array $parameters): string
    {
        foreach ($parameters as $key => $value) {
            $path = str_replace('{'.$key.'}', rawurlencode((string) $value), $path);
        }

        return $path;
    }
}
