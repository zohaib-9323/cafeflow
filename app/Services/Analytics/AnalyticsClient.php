<?php

namespace App\Services\Analytics;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for the Python analytics service. Server-to-server only: the shared
 * key lives in Laravel's config and is never sent to the browser.
 */
class AnalyticsClient
{
    public function post(string $endpoint, array $payload): array
    {
        return $this->send('post', "/api/v1/analytics/{$endpoint}", $payload);
    }

    public function health(): array
    {
        return $this->send('get', '/health');
    }

    private function send(string $method, string $path, array $payload = []): array
    {
        $config = config('cafeflow.analytics');
        $started = microtime(true);

        if (! $config['key']) {
            throw new AnalyticsUnavailable('ANALYTICS_SERVICE_KEY is not configured.');
        }

        try {
            /** @var Response $response */
            $response = Http::baseUrl($config['url'])
                ->withHeaders(['X-Service-Key' => $config['key']])
                ->acceptJson()
                ->connectTimeout(2)
                ->timeout($config['timeout'])
                // Retry only transient failures (network errors, 5xx); a 4xx is our bug, so retrying won't help.
                ->retry($config['retries'], 250, function (\Throwable $e) {
                    return $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response->serverError());
                }, throw: false)
                ->{$method}($path, $payload);
        } catch (ConnectionException|RequestException $e) {
            Log::warning('Analytics service unreachable', ['path' => $path, 'error' => $e->getMessage()]);
            throw new AnalyticsUnavailable('The analytics service is not reachable.', previous: $e);
        }

        $ms = (int) ((microtime(true) - $started) * 1000);

        if ($response->failed() || $response->json('success') !== true) {
            Log::error('Analytics service error', [
                'path' => $path,
                'status' => $response->status(),
                'message' => $response->json('message'),
                'errors' => array_slice((array) $response->json('errors'), 0, 5),
            ]);
            throw new AnalyticsUnavailable("Analytics request failed ({$response->status()}).");
        }

        Log::debug('Analytics request', ['path' => $path, 'status' => $response->status(), 'ms' => $ms]);

        return (array) $response->json('data');
    }
}
