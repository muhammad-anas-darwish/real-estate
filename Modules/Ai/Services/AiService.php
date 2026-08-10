<?php

namespace Modules\Ai\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService extends BaseService
{
    public function chat(string $systemPrompt, string $userPrompt, array $options = []): ?string
    {
        $apiKey = config('services.kimi.api_key');
        $baseUrl = config('services.kimi.base_url');
        $model = config('services.kimi.model');
        $timeout = config('services.kimi.timeout', 30);
        $maxRetries = config('services.kimi.max_retries', 2);

        if (! $apiKey) {
            Log::warning('KIMI_API_KEY not set. AI features disabled.');

            return null;
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => $options['temperature'] ?? 0.3,
            'max_tokens' => $options['max_tokens'] ?? 1000,
        ];

        $attempt = 0;
        while ($attempt <= $maxRetries) {
            try {
                $response = Http::timeout($timeout)
                    ->withToken($apiKey)
                    ->post("{$baseUrl}/chat/completions", $payload);

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['choices'][0]['message']['content'] ?? null;
                }

                Log::warning('Kimi API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'attempt' => $attempt + 1,
                ]);

                if ($response->status() === 401 || $response->status() === 400) {
                    return null;
                }
            } catch (\Throwable $e) {
                Log::error('Kimi API exception: '.$e->getMessage());
            }

            $attempt++;
            if ($attempt <= $maxRetries) {
                sleep(pow(2, $attempt));
            }
        }

        return null;
    }

    public function chatJson(string $systemPrompt, string $userPrompt, array $options = []): ?array
    {
        $content = $this->chat($systemPrompt, $userPrompt, $options);

        if ($content === null) {
            return null;
        }

        return $this->extractJson($content);
    }

    protected function extractJson(string $content): ?array
    {
        $content = trim($content);

        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function isAvailable(): bool
    {
        return ! empty(config('services.kimi.api_key'));
    }
}
