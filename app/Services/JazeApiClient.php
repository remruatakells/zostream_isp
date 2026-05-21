<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JazeApiClient
{
    /**
     * @param  array<string, mixed>  $query
     * @return array{status: int, data: mixed, successful: bool}
     */
    public function get(Branch $branch, string $path, array $query = []): array
    {
        return $this->send($branch, 'get', $path, query: $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{status: int, data: mixed, successful: bool}
     */
    public function post(Branch $branch, string $path, array $data = []): array
    {
        return $this->send($branch, 'post', $path, data: $data);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $data
     * @return array{status: int, data: mixed, successful: bool}
     */
    private function send(Branch $branch, string $method, string $path, array $query = [], array $data = []): array
    {
        if (! $this->configured($branch)) {
            throw new RuntimeException("Jaze API is not configured for branch [{$branch->code}]. Set JAZE_BASE_URL plus branch jaze_api_token and jaze_api_key.");
        }

        /** @var Response $response */
        $response = match ($method) {
            'get' => $this->request($branch)->get($path, $query),
            'post' => $this->request($branch)->post($path, $data),
            default => throw new RuntimeException("Unsupported Jaze HTTP method [{$method}]."),
        };

        return [
            'status' => $response->status(),
            'data' => $response->json() ?? ['raw' => $response->body()],
            'successful' => $response->successful(),
        ];
    }

    private function configured(Branch $branch): bool
    {
        return filled(config('services.jaze.base_url'))
            && filled($branch->jaze_api_token)
            && filled($branch->jaze_api_key);
    }

    private function request(Branch $branch): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.jaze.base_url'), '/'))
            ->withBasicAuth((string) $branch->jaze_api_token, (string) $branch->jaze_api_key)
            ->acceptJson()
            ->asForm()
            ->timeout((int) config('services.jaze.timeout', 20));
    }
}
