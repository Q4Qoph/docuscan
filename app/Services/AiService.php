<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AiService
{
    private string $driver;
    private array $config;

    public function __construct()
    {
        $this->driver = config('ai.default', 'gemini');

        $providers = config('ai.providers');

        if (!is_array($providers) || !isset($providers[$this->driver])) {
            // Fallback to Gemini using the old config
            $this->driver = 'gemini';
            $this->config = [
                'api_key' => config('services.gemini.key'),
                'model'   => config('services.gemini.model', 'gemini-2.0-flash'),
            ];
            return;
        }

        $this->config = $providers[$this->driver];
    }

    public function summarize(string $text, ?string $criteria = null): array
    {
        $this->checkRateLimit('summarize');
        $prompt = $this->buildSummarizePrompt($text, $criteria);
        return $this->callApi($prompt, 2048);
    }

    public function classifyLegalDocument(string $text): ?string
    {
        $this->checkRateLimit('classify');

        $stages = implode("\n", array_map(
            fn($k, $v) => "- {$k}: {$v}",
            array_keys(\App\Models\LegalCase::STAGES),
            \App\Models\LegalCase::STAGES
        ));

        $prompt = <<<PROMPT
You are a legal document classifier for a Kenyan law firm.

Classify the document below into EXACTLY ONE of these types (return only the key):
{$stages}

If the document does not match any type, return: unknown

Document text:
---
{$text}
---
PROMPT;

        $result = $this->callApi($prompt, 20);
        $type = strtolower(trim($result['summary'] ?? ''));
        return in_array($type, array_keys(\App\Models\LegalCase::STAGES)) ? $type : null;
    }

    private function callApi(string $prompt, int $maxTokens): array
    {
        return match ($this->driver) {
            'groq'    => $this->callGroq($prompt, $maxTokens),
            'mistral' => $this->callMistral($prompt, $maxTokens),
            'gemini'  => $this->callGemini($prompt, $maxTokens),
            default   => throw new \Exception("Unsupported AI driver: {$this->driver}"),
        };
    }

    private function callGroq(string $prompt, int $maxTokens): array
    {
        $response = Http::timeout(60)
            ->withToken($this->config['api_key'])
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'       => $this->config['model'],
                'messages'    => [['role' => 'user', 'content' => $prompt]],
                'max_tokens'  => $maxTokens,
                'temperature' => 0.3,
            ]);

        if ($response->failed()) {
            $this->handleApiFailure($response);
        }

        $data = $response->json();
        return [
            'summary'     => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'model'       => $this->config['model'],
        ];
    }

    private function callMistral(string $prompt, int $maxTokens): array
    {
        $response = Http::timeout(60)
            ->withToken($this->config['api_key'])
            ->post('https://api.mistral.ai/v1/chat/completions', [
                'model'       => $this->config['model'],
                'messages'    => [['role' => 'user', 'content' => $prompt]],
                'max_tokens'  => $maxTokens,
                'temperature' => 0.3,
            ]);

        if ($response->failed()) {
            $this->handleApiFailure($response);
        }

        $data = $response->json();
        return [
            'summary'     => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'model'       => $this->config['model'],
        ];
    }

    private function callGemini(string $prompt, int $maxTokens): array
    {
        // Use your existing GeminiService for now
        return (new GeminiService())->summarize($prompt, null);
    }

    private function handleApiFailure($response): void
    {
        $status = $response->status();
        if ($status === 429) {
            throw new \Exception('Rate limit exceeded on AI provider.');
        } elseif ($status >= 500) {
            throw new \Exception('AI provider is temporarily unavailable.');
        }
        throw new \Exception('AI request failed: ' . $response->body());
    }

    private function checkRateLimit(string $action): void
    {
        $key = "ai:{$action}:" . (auth()->id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 30)) {
            $seconds = RateLimiter::availableIn($key);
            throw new \Exception("Too many AI requests. Please wait {$seconds} seconds.");
        }
        RateLimiter::hit($key, 60);
    }

    private function buildSummarizePrompt(string $text, ?string $criteria): string
    {
        $criteriaSection = $criteria
            ? "Follow these specific instructions for the summary:\n{$criteria}\n\n"
            : "Provide a clear, well-structured general summary.\n\n";

        return <<<PROMPT
You are a professional document summarizer. Analyze the document text below and produce a summary.

{$criteriaSection}Guidelines:
- Be accurate and only summarize what is actually in the document
- Do not invent or infer information not present in the text
- Use clear, professional language
- If the document is too short or has no meaningful content, say so

Document text:
---
{$text}
---

Summary:
PROMPT;
    }
}