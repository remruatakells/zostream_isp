<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Models\Branch;
use App\Models\RenewSuccessLog;
use App\Services\JazeApiClient;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class JazeApiController extends Controller
{
    public function __construct(private readonly JazeApiClient $jaze)
    {
    }

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

        $request->query->remove('page');
        $request->query->remove('per_page');
        $request->query->remove('status');

        return $this->get($request, 'api/v1/get_all');
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

    public function userLogoffTimeOnlineStatus(Request $request, string $userId): JsonResponse
    {
        return $this->get($request, 'api/v1/get_logofftime_onlinestatus/{userId}', ['userId' => $userId]);
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

        return $this->post(
            $request,
            'api/v1/add_user',
            afterSuccessfulResponse: function (Branch $branch, AdminUser $adminUser, array $response) use ($request): ?JsonResponse {
                $this->storeLocalUserAfterJazeAdd($request, $branch, $response);

                if (!$this->shouldSubscribeZostreamForAddUser($request)) {
                    return null;
                }

                $subscriptionResult = $this->subscribeZostreamIsp($request);

                return $subscriptionResult instanceof JsonResponse ? $subscriptionResult : null;
            }
        );
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
            'phone_no' => ['required_without_all:phone,phoneNumber', 'nullable', 'string'],
            'phone' => ['nullable', 'string'],
            'phoneNumber' => ['nullable', 'string'],
        ]);

        return $this->post(
            $request,
            'api/v1/renew',
            afterSuccessfulResponse: fn(Branch $branch, ?AdminUser $adminUser, array $response): ?JsonResponse => $this->handleSuccessfulRenew(
                $request,
                $branch,
                $adminUser,
                $response
            )
        );
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
     * @param  (callable(Branch, ?AdminUser, array{status: int, data: mixed, successful: bool}): mixed)|null  $afterSuccessfulResponse
     */
    private function post(
        Request $request,
        string $path,
        array $parameters = [],
        ?callable $afterSuccessfulResponse = null
    ): JsonResponse {
        return $this->send($request, 'post', $path, $parameters, $afterSuccessfulResponse);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  (callable(Branch, ?AdminUser, array{status: int, data: mixed, successful: bool}): mixed)|null  $afterSuccessfulResponse
     */
    private function send(
        Request $request,
        string $method,
        string $path,
        array $parameters = [],
        ?callable $afterSuccessfulResponse = null
    ): JsonResponse {
        $authenticatedBranch = $this->authenticatedBranch($request);
        $adminUser = $this->adminUser($request);

        if ($authenticatedBranch) {
            $path = $this->replacePathParameters($path, $parameters);
            $queryPayload = $this->queryPayload($request);
            $bodyPayload = $this->payloadForJazePath($request, $path);
            $filePayload = $this->filePayloadForJazePath($path, $request);

            try {
                $this->logOutgoingJazeRequest(
                    $method,
                    $path,
                    $authenticatedBranch,
                    $queryPayload,
                    $bodyPayload,
                    $filePayload,
                    'authenticated_branch'
                );

                $response = $method === 'get'
                    ? $this->jaze->get($authenticatedBranch, $path, $queryPayload)
                    : $this->jaze->post($authenticatedBranch, $path, $bodyPayload, $filePayload);

                $response = $this->fallbackEmptyGetAllResponse($authenticatedBranch, $method, $path, $response);
            } catch (Throwable $exception) {
                return response()->json(['message' => $exception->getMessage()], 503);
            }

            if ($response['successful'] && $afterSuccessfulResponse) {
                $callbackResponse = $afterSuccessfulResponse($authenticatedBranch, $adminUser, $response);

                if ($callbackResponse instanceof JsonResponse) {
                    return $callbackResponse;
                }
            }

            return response()->json($response['data'], $response['status']);
        }

        if (!$adminUser) {
            return response()->json([
                'message' => 'Authentication is required. Send branch basic auth or admin credentials.',
            ], 401);
        }

        if ($adminUser->isCustomerRole() && !$this->customerCanCall($adminUser, $method, $path, $parameters, $request)) {
            return response()->json(['message' => 'This user can only access their own data.'], 403);
        }

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
        $queryPayload = $this->queryPayload($request);
        $bodyPayload = $this->payloadForJazePath($request, $path);
        $filePayload = $this->filePayloadForJazePath($path, $request);

        if ($branches->count() > 1) {
            return $this->sendToBranches($branches, $method, $path, $queryPayload, $bodyPayload, $filePayload);
        }

        $branch = $branches->first();

        try {
            $this->logOutgoingJazeRequest(
                $method,
                $path,
                $branch,
                $queryPayload,
                $bodyPayload,
                $filePayload,
                'admin_branch'
            );

            $response = $method === 'get'
                ? $this->jaze->get($branch, $path, $queryPayload)
                : $this->jaze->post($branch, $path, $bodyPayload, $filePayload);

            $response = $this->fallbackEmptyGetAllResponse($branch, $method, $path, $response);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        if ($response['successful'] && $adminUser->isCustomerRole()) {
            $response['data'] = $this->customerScopedResponseData($adminUser, $path, $response['data']);
        }

        if ($response['successful'] && $afterSuccessfulResponse) {
            $callbackResponse = $afterSuccessfulResponse($branch, $adminUser, $response);

            if ($callbackResponse instanceof JsonResponse) {
                return $callbackResponse;
            }
        }

        return response()->json($response['data'], $response['status']);
    }

    private function adminUser(Request $request): ?AdminUser
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->attributes->get('admin_user');

        return $adminUser;
    }

    private function authenticatedBranch(Request $request): ?Branch
    {
        /** @var Branch $branch */
        $branch = $request->attributes->get('jaze_branch');

        return $branch;
    }

    /**
     * @return Collection<int, Branch>|null
     */
    private function branchesForAdmin(AdminUser $adminUser, Request $request, string $method): ?Collection
    {
        $requestedBranch = $this->requestedBranch($request);
        $requestedBranchWasProvided = $request->filled('branch_id') || $request->filled('branch_code');

        if ($requestedBranchWasProvided && !$requestedBranch) {
            return new Collection;
        }

        if ($adminUser->role !== 'super_admin') {
            if ($requestedBranch && !$this->adminCanUseBranch($adminUser, $requestedBranch)) {
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
     * @param  array<string, mixed>  $queryPayload
     * @param  array<string, mixed>  $bodyPayload
     * @param  array<string, UploadedFile>  $filePayload
     */
    private function sendToBranches(
        Collection $branches,
        string $method,
        string $path,
        array $queryPayload,
        array $bodyPayload,
        array $filePayload
    ): JsonResponse {
        $results = [];
        $status = 200;

        foreach ($branches as $branch) {
            try {
                $this->logOutgoingJazeRequest(
                    $method,
                    $path,
                    $branch,
                    $queryPayload,
                    $bodyPayload,
                    $filePayload,
                    'admin_multi_branch'
                );

                $response = $method === 'get'
                    ? $this->jaze->get($branch, $path, $queryPayload)
                    : $this->postToJaze($branch, $path, $bodyPayload, $filePayload);

                $response = $this->fallbackEmptyGetAllResponse($branch, $method, $path, $response);
            } catch (Throwable $exception) {
                $status = 207;
                $results[] = $this->branchResult($branch, [
                    'status' => 503,
                    'data' => ['message' => $exception->getMessage()],
                    'successful' => false,
                ]);

                continue;
            }

            if (!$response['successful']) {
                $status = 207;
            }

            $results[] = $this->branchResult($branch, $response);
        }

        return response()->json(['branches' => $results], $status);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $files
     */
    private function logOutgoingJazeRequest(
        string $method,
        string $path,
        Branch $branch,
        array $query = [],
        array $data = [],
        array $files = [],
        string $context = 'direct'
    ): void {
        Log::info('Sending Jaze API request', [
            'context' => $context,
            'method' => strtoupper($method),
            'path' => $path,
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
            ],
            'query' => $query,
            'data' => $data,
            'files' => array_map(
                fn(UploadedFile $file): array => [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ],
                $files
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function payloadForJazePath(Request $request, string $path): array
    {
        $payload = $this->bodyPayload($request);

        if ($path === 'api/v1/add_user') {
            $payload = collect($payload)
                ->only([
                    'userId',
                    'userGroupId',
                    'accountId',
                    'userName',
                    'firstName',
                    'first_name',
                    'lastName',
                    'last_name',
                    'password',
                    'userState',
                    'activationDate',
                    'expirationDate',
                    'customActivationDate',
                    'customExpirationDate',
                    'phoneNumber',
                    'emailId',
                ])
                ->all();

            if (array_key_exists('userGroupId', $payload) && is_numeric($payload['userGroupId'])) {
                $payload['userGroupId'] = (int) $payload['userGroupId'];
            }

            return $payload;
        }

        return $payload;
    }

    /**
     * @param  array<string, UploadedFile>  $files
     * @return array<string, UploadedFile>
     */
    private function filePayloadForJazePath(string $path, Request $request): array
    {
        return $this->filePayload($request);
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

    /**
     * @param  array{status: int, data: mixed, successful: bool}  $response
     * @return array{status: int, data: mixed, successful: bool}
     */
    private function fallbackEmptyGetAllResponse(Branch $branch, string $method, string $path, array $response): array
    {
        if (
            $method !== 'get' ||
            $path !== 'api/v1/get_all' ||
            ! $response['successful'] ||
            ! $this->isBlankJazeData($response['data'])
        ) {
            return $response;
        }

        $fallbackPath = 'api/v1/get_users/1/500/';

        Log::warning('Jaze get_all returned blank data; falling back to paged users endpoint', [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
            ],
            'path' => $path,
            'fallback_path' => $fallbackPath,
        ]);

        return $this->jaze->get($branch, $fallbackPath, []);
    }

    private function isBlankJazeData(mixed $data): bool
    {
        if ($data === null) {
            return true;
        }

        if (is_string($data)) {
            return trim($data) === '';
        }

        if (is_array($data) && array_key_exists('raw', $data) && is_string($data['raw'])) {
            return trim($data['raw']) === '';
        }

        return false;
    }

    private function requestedBranch(Request $request): ?Branch
    {
        if ($request->filled('branch_id')) {
            return Branch::find($request->input('branch_id'));
        }

        if ($request->filled('branch_code')) {
            return Branch::where('code', $request->input('branch_code'))->first();
        }

        if ($request->filled('accountId')) {
            return Branch::query()
                ->where('jaze_account_id', $request->input('accountId'))
                ->orWhere('code', $request->input('accountId'))
                ->first();
        }

        return null;
    }

    private function adminCanUseBranch(AdminUser $adminUser, Branch $branch): bool
    {
        return $adminUser->role === 'super_admin'
            || (int) $adminUser->branch_id === (int) $branch->id;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function customerCanCall(
        AdminUser $adminUser,
        string $method,
        string $path,
        array $parameters,
        Request $request
    ): bool {
        if ($method === 'get') {
            if (
                in_array($path, [
                    'api/v1/get_users/{page}/{perPage}/{status}',
                    'api/v1/get_all',
                    'api/v1/get_group_details',
                    'api/v1/get_group_details/{groupId}',
                ], true)
            ) {
                return true;
            }

            if (
                in_array($path, [
                    'api/v1/get_details/{userId}',
                    'api/v1/get_balance/{userId}',
                    'api/v1/get_logofftime_onlinestatus/{userId}',
                    'api/v1/get_payment_details/{userId}',
                ], true)
            ) {
                return $this->customerOwnsJazeUserId($adminUser, $parameters['userId'] ?? null);
            }

            if ($path === 'api/v1/get_user_by_username/{username}') {
                return $this->customerOwnsJazeUsername($adminUser, $parameters['username'] ?? null);
            }

            return false;
        }

        if ($path === 'api/v1/renew') {
            return $this->customerOwnsJazeUserId($adminUser, $request->input('userId'));
        }

        if ($path === 'api/v1/make_payment') {
            return $this->customerOwnsJazeUserId($adminUser, $request->input('userId'));
        }

        if ($path === 'api/v1/raise_ticket') {
            return $this->customerOwnsJazeUserId($adminUser, $request->input('userId'));
        }

        return false;
    }

    private function customerOwnsJazeUserId(AdminUser $adminUser, mixed $userId): bool
    {
        $linkedUserId = trim((string) $adminUser->jaze_user_id);

        return $linkedUserId !== '' && $linkedUserId === trim((string) $userId);
    }

    private function customerOwnsJazeUsername(AdminUser $adminUser, mixed $username): bool
    {
        return $this->sameFilledValue($adminUser->jaze_username, $username);
    }

    private function customerScopedResponseData(AdminUser $adminUser, string $path, mixed $data): mixed
    {
        if ($path !== 'api/v1/get_all' && !str_starts_with($path, 'api/v1/get_users/')) {
            return $data;
        }

        if (!is_array($data)) {
            return $data;
        }

        $users = data_get($data, 'data');

        if (is_array($users)) {
            $filteredUsers = $this->filterCustomerUsers($adminUser, $users);

            data_set($data, 'data', $filteredUsers);
            data_set($data, 'totalRecords', count($filteredUsers));

            return $data;
        }

        if ($this->isListOfUsers($data)) {
            return $this->filterCustomerUsers($adminUser, $data);
        }

        return $this->customerUserMatches($adminUser, $data) ? $data : [];
    }

    /**
     * @param  array<int, mixed>  $users
     */
    private function filterCustomerUsers(AdminUser $adminUser, array $users): array
    {
        return array_values(array_filter(
            $users,
            fn(mixed $user): bool => is_array($user) && $this->customerUserMatches($adminUser, $user)
        ));
    }

    private function isListOfUsers(array $data): bool
    {
        foreach ($data as $item) {
            if (!is_array($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $user
     */
    private function customerUserMatches(AdminUser $adminUser, array $user): bool
    {
        $matchers = [
            [$adminUser->jaze_user_id, data_get($user, 'id')],
            [$adminUser->jaze_user_id, data_get($user, 'userId')],
            [$adminUser->jaze_username, data_get($user, 'username')],
            [$adminUser->phone, data_get($user, 'phone')],
            [$adminUser->phone, data_get($user, 'phoneNumber')],
            [$adminUser->email, data_get($user, 'email')],
            [$adminUser->email, data_get($user, 'emailId')],
        ];

        foreach ($matchers as [$expected, $actual]) {
            if ($this->sameFilledValue($expected, $actual)) {
                return true;
            }
        }

        return false;
    }

    private function sameFilledValue(mixed $expected, mixed $actual): bool
    {
        $expectedValue = strtolower(trim((string) $expected));
        $actualValue = strtolower(trim((string) $actual));

        return $expectedValue !== '' && $actualValue !== '' && $expectedValue === $actualValue;
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
            ->filter(fn(mixed $value): bool => $value !== null && $value !== '' && !$value instanceof UploadedFile)
            ->all();
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function filePayload(Request $request): array
    {
        return collect($request->allFiles())
            ->filter(fn(mixed $value): bool => $value instanceof UploadedFile)
            ->all();
    }

    /**
     * @param  array{status: int, data: mixed, successful: bool}  $response
     */
    private function handleSuccessfulRenew(
        Request $request,
        Branch $branch,
        ?AdminUser $adminUser,
        array $response
    ): ?JsonResponse {
        $subscriptionResult = $this->subscribeZostreamIsp($request);

        if ($subscriptionResult instanceof JsonResponse) {
            return $subscriptionResult;
        }

        $this->storeRenewSuccessLog($request, $branch, $adminUser, $response, $subscriptionResult);

        return null;
    }

    /**
     * @param  array{status: int, data: mixed, successful: bool}  $response
     */
    private function storeLocalUserAfterJazeAdd(Request $request, Branch $branch, array $response): void
    {
        $jazeUserId = $this->firstFilledScalar([
            data_get($response, 'data.userId'),
            data_get($response, 'data.user_id'),
            data_get($response, 'data.id'),
            data_get($response, 'data.data.userId'),
            data_get($response, 'data.data.user_id'),
            data_get($response, 'data.data.id'),
            $request->input('userId'),
            $request->input('user_id'),
        ]);
        $jazeUsername = $this->firstFilledScalar([
            data_get($response, 'data.username'),
            data_get($response, 'data.userName'),
            data_get($response, 'data.user_name'),
            data_get($response, 'data.data.username'),
            data_get($response, 'data.data.userName'),
            data_get($response, 'data.data.user_name'),
            $request->input('userName'),
            $request->input('username'),
        ]);
        $phone = $this->firstFilledScalar([
            $request->input('phoneNumber'),
            $request->input('phone'),
            $request->input('phone_no'),
            data_get($response, 'data.phoneNumber'),
            data_get($response, 'data.phone'),
            data_get($response, 'data.phone_no'),
            data_get($response, 'data.data.phoneNumber'),
            data_get($response, 'data.data.phone'),
            data_get($response, 'data.data.phone_no'),
        ]);
        $email = $this->firstFilledScalar([
            $request->input('emailId'),
            $request->input('email'),
            data_get($response, 'data.emailId'),
            data_get($response, 'data.email'),
            data_get($response, 'data.data.emailId'),
            data_get($response, 'data.data.email'),
        ]);

        if (!$jazeUserId && !$jazeUsername && !$phone && !$email) {
            return;
        }

        $localUser = AdminUser::query()
            ->where(function ($query) use ($jazeUserId, $jazeUsername, $phone, $email): void {
                if ($jazeUserId) {
                    $query->orWhere('jaze_user_id', $jazeUserId);
                }

                if ($jazeUsername && Schema::hasColumn('admin_users', 'jaze_username')) {
                    $query->orWhere('jaze_username', $jazeUsername);
                }

                if ($phone) {
                    $query->orWhere('phone', $phone);
                }

                if ($email) {
                    $query->orWhere('email', $email);
                }
            })
            ->first();

        if ($localUser && !$localUser->isCustomerRole()) {
            return;
        }

        if (!$localUser && !$phone) {
            return;
        }

        $password = $this->firstFilledScalar([$request->input('password')]);
        $name = $this->localUserName($request, $jazeUsername);
        $data = [
            'name' => $name,
            'phone' => $phone ?: $localUser?->phone,
            'email' => $email,
            'role' => 'user',
            'branch_id' => $branch->id,
            'jaze_user_id' => $jazeUserId,
            'jaze_username' => $jazeUsername,
            'status' => 'active',
        ];

        if ($password || !$localUser) {
            $data['password'] = $password ?: Str::random(32);
        }

        if ($localUser) {
            $localUser->update(collect($data)
                ->filter(fn(mixed $value): bool => $value !== null && $value !== '')
                ->all());

            return;
        }

        AdminUser::create(collect($data)
            ->filter(fn(mixed $value): bool => $value !== null && $value !== '')
            ->all());
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function firstFilledScalar(array $values): ?string
    {
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $trimmedValue = trim((string) $value);

            if ($trimmedValue !== '') {
                return $trimmedValue;
            }
        }

        return null;
    }

    private function localUserName(Request $request, ?string $fallback): string
    {
        $firstName = $this->firstFilledScalar([$request->input('firstName'), $request->input('first_name')]);
        $lastName = $this->firstFilledScalar([$request->input('lastName'), $request->input('last_name')]);
        $fullName = trim(implode(' ', array_filter([$firstName, $lastName])));

        return $fullName !== ''
            ? $fullName
            : ($this->firstFilledScalar([$request->input('name')]) ?: ($fallback ?: 'User'));
    }

    /**
     * @return JsonResponse|array<string, mixed>
     */
    private function subscribeZostreamIsp(Request $request): JsonResponse|array
    {
        try {
            $subscriptionResponse = Http::timeout((int) config('services.zostream_isp.timeout', 20))
                ->acceptJson()
                ->asJson()
                ->post((string) config('services.zostream_isp.subscribe_url'), [
                    'phone_no' => $this->phoneNumber($request),
                ]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Zostream ISP subscription failed.',
                'error' => $exception->getMessage(),
            ], 503);
        }

        $subscriptionData = $this->zostreamSubscriptionData(
            $subscriptionResponse->json() ?? ['raw' => $subscriptionResponse->body()]
        );

        if (!$this->zostreamSubscriptionSucceeded($subscriptionResponse->successful(), $subscriptionData)) {
            return response()->json([
                'message' => 'Zostream ISP subscription failed.',
                'zostream_isp_response' => $subscriptionData,
            ], $subscriptionResponse->status() >= 400 ? $subscriptionResponse->status() : 502);
        }

        return $subscriptionData;
    }

    /**
     * @param  array<string, mixed>  $subscriptionData
     */
    private function zostreamSubscriptionSucceeded(bool $httpSuccessful, array $subscriptionData): bool
    {
        if (!$httpSuccessful) {
            return false;
        }

        $status = data_get($subscriptionData, 'status');

        if (!is_string($status)) {
            return true;
        }

        return strtolower($status) === 'success';
    }

    /**
     * @param  array<string, mixed>  $subscriptionData
     * @return array<string, mixed>
     */
    private function zostreamSubscriptionData(array $subscriptionData): array
    {
        $wrappedResponse = data_get($subscriptionData, 'response');

        if (is_array($wrappedResponse) && is_string(data_get($wrappedResponse, 'status'))) {
            return $wrappedResponse;
        }

        $wrappedOriginal = data_get($subscriptionData, 'response.original');

        if (is_array($wrappedOriginal) && is_string(data_get($wrappedOriginal, 'status'))) {
            return $wrappedOriginal;
        }

        return $subscriptionData;
    }

    private function shouldSubscribeZostreamForAddUser(Request $request): bool
    {
        $activationDate = $this->requestDate($request->input('activationDate'), treatNowAsToday: true);
        $expirationValue = $request->input('expirationDate');
        $expirationDate = $this->requestDate($expirationValue, treatNeverAsOpenEnded: true);
        $expirationNeverEnds = $this->isDateKeyword($expirationValue, 'never');

        if (!$activationDate || (!$expirationNeverEnds && !$expirationDate)) {
            return false;
        }

        $today = now()->startOfDay();

        if ($activationDate->startOfDay()->greaterThan($today)) {
            return false;
        }

        return !$expirationDate || $expirationDate->endOfDay()->greaterThanOrEqualTo($today);
    }

    private function isDateKeyword(mixed $value, string $keyword): bool
    {
        return is_scalar($value) && $this->normalizedDateKeyword((string) $value) === $keyword;
    }

    private function normalizedDateKeyword(string $value): string
    {
        return strtolower(str_replace([' ', '_', '-'], '', trim($value)));
    }

    private function requestDate(
        mixed $value,
        bool $treatNowAsToday = false,
        bool $treatNeverAsOpenEnded = false
    ): ?CarbonInterface {
        if (!is_scalar($value)) {
            return null;
        }

        $date = trim((string) $value);

        if ($date === '') {
            return null;
        }

        $normalized = $this->normalizedDateKeyword($date);

        if ($treatNowAsToday && in_array($normalized, ['now', 'setnow'], true)) {
            return now();
        }

        if ($treatNeverAsOpenEnded && $normalized === 'never') {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (Throwable) {
            return null;
        }
    }

    private function phoneNumber(Request $request): string
    {
        return (string) ($request->input('phone_no') ?? $request->input('phone') ?? $request->input('phoneNumber'));
    }

    /**
     * @param  array{status: int, data: mixed, successful: bool}  $response
     * @param  array<string, mixed>  $subscriptionData
     */
    private function storeRenewSuccessLog(
        Request $request,
        Branch $branch,
        ?AdminUser $adminUser,
        array $response,
        array $subscriptionData
    ): RenewSuccessLog {
        $payload = $this->bodyPayload($request);

        return RenewSuccessLog::create([
            'branch_id' => $branch->id,
            'admin_user_id' => $adminUser?->id,
            'jaze_user_id' => $payload['userId'] ?? $payload['user_id'] ?? null,
            'jaze_username' => $payload['userName'] ?? $payload['username'] ?? null,
            'account_id' => $payload['accountId'] ?? $payload['account_id'] ?? null,
            'status' => 'success',
            'payload' => [
                'request' => $payload,
                'response' => $response['data'],
                'response_status' => $response['status'],
                'zostream_isp_subscription' => $subscriptionData,
            ],
            'renewed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function replacePathParameters(string $path, array $parameters): string
    {
        foreach ($parameters as $key => $value) {
            $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
        }

        return $path;
    }
}
