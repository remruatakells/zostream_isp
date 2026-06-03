<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Http\UploadedFile;
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
    public function post(Branch $branch, string $path, array $data = [], array $files = []): array
    {
        return $this->send($branch, 'post', $path, data: $data, files: $files);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $data
     * @return array{status: int, data: mixed, successful: bool}
     */
    private function send(
        Branch $branch,
        string $method,
        string $path,
        array $query = [],
        array $data = [],
        array $files = []
    ): array {
        if (! $this->configured()) {
            throw new RuntimeException(
                'Jaze API is not configured. Set JAZE_BASE_URL, JAZE_BASIC_USER, and JAZE_BASIC_PASSWORD.',
            );
        }

        /** @var Response $response */
        $response = match ($method) {
            'get' => $this->request()->get($path, $query),
            'post' => $this->postRequest($path, $data, $files),
            default => throw new RuntimeException("Unsupported Jaze HTTP method [{$method}]."),
        };

        return [
            'status' => $response->status(),
            'data' => $response->json() ?? ['raw' => $response->body()],
            'successful' => $response->successful(),
        ];
    }


    private function configured(): bool
    {
        return filled(config('services.jaze.base_url'))
            && filled(config('services.jaze.basic_user'))
            && filled(config('services.jaze.basic_password'));
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.jaze.base_url'), '/'))
            ->withBasicAuth(
                (string) config('services.jaze.basic_user'),
                (string) config('services.jaze.basic_password'),
            )
            ->acceptJson()
            ->asMultipart()
            ->timeout((int) config('services.jaze.timeout', 20));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $files
     */
    private function postRequest(string $path, array $data, array $files): Response
    {
        $request = $this->request();

        foreach ($files as $name => $file) {
            $request = $request->attach(
                $name,
                fopen($file->getRealPath(), 'r'),
                $file->getClientOriginalName(),
            );
        }

        return $request->post($path, $data);
    }
}
