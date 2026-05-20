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
    /**
 * Extract key facts from a legal document and return structured data.
 */
public function extractKeyFacts(string $text): array
{
    $prompt = <<<PROMPT
You are a legal document analyzer for a Kenyan law firm. Extract the following from the text below and return ONLY a JSON object (no other text, no markdown, no backticks):

{
  "parties": {
    "plaintiff": "...",
    "defendant": "..."
  },
  "dates": {
    "document_date": "YYYY-MM-DD",
    "relevant_dates": [{"label": "string", "date": "YYYY-MM-DD"}]
  },
  "claim_amount": 0.00,
  "relief_sought": ["string", ...],
  "key_events": ["string", ...],
  "court": "string or null",
  "case_number": "string or null",
  "judge": "string or null"
}

If a field is not present, use null or empty array. Do not invent information.

Document text:
---
{$text}
---
PROMPT;

    $result = $this->callApi($prompt, 1024);
    $raw = trim($result['summary'] ?? '');

    // Clean possible markdown code fences
    $raw = str_replace(['```json', '```'], '', $raw);
    $raw = trim($raw);

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        // Fallback: try extracting from the raw string
        // If JSON parsing fails, return an empty structure
        return [
            'parties' => ['plaintiff' => null, 'defendant' => null],
            'dates' => ['document_date' => null, 'relevant_dates' => []],
            'claim_amount' => null,
            'relief_sought' => [],
            'key_events' => [],
            'court' => null,
            'case_number' => null,
            'judge' => null,
            'error' => 'AI did not return valid JSON.',
            'raw' => $raw,
        ];
    }

    return $data;
}
/**
 * Ask a question about the provided text, streaming the response chunk by chunk.
 * Calls $onChunk for each piece of the answer.
 */
public function askAboutText(string $question, string $contextText, callable $onChunk): string
{
    $prompt = <<<PROMPT
You are a legal assistant for a Kenyan law firm. Answer the following question based ONLY on the document text provided. Be concise.

Document text:
---
{$contextText}
---

Question: {$question}
Answer:
PROMPT;

    return $this->callStreamingApi($prompt, 1024, $onChunk);
}

/**
 * Streaming API call (works with Groq and Mistral; Gemini needs different handling).
 */
private function callStreamingApi(string $prompt, int $maxTokens, callable $onChunk): string
{
    return match ($this->driver) {
        'groq' => $this->streamGroq($prompt, $maxTokens, $onChunk),
        'mistral' => $this->streamMistral($prompt, $maxTokens, $onChunk),
        'gemini' => $this->streamGeminiFallback($prompt, $maxTokens, $onChunk),
        default => throw new \Exception("Streaming not supported for {$this->driver}"),
    };
}

private function streamGroq(string $prompt, int $maxTokens, callable $onChunk): string
{
    $payload = [
        'model' => $this->config['model'],
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => $maxTokens,
        'temperature' => 0.3,
        'stream' => true,
    ];

    $response = Http::timeout(120)
        ->withToken($this->config['api_key'])
        ->withOptions(['stream' => true])
        ->post('https://api.groq.com/openai/v1/chat/completions', $payload);

    $fullText = '';
    $body = $response->toPsrResponse()->getBody();

    while (!$body->eof()) {
        $line = $this->readLine($body);
        if (empty(trim($line))) continue;

        $line = str_replace('data: ', '', $line);
        if ($line === '[DONE]') break;

        $data = json_decode($line, true);
        $content = $data['choices'][0]['delta']['content'] ?? '';
        if ($content) {
            $fullText .= $content;
            $onChunk($content);
        }
    }

    return $fullText;
}

private function readLine($body): string
{
    $buffer = '';
    while (!$body->eof()) {
        $byte = $body->read(1);
        if ($byte === "\n") return $buffer;
        $buffer .= $byte;
    }
    return $buffer;
}

// Fallback: for Gemini or if streaming fails, call non-streaming and send chunks as one
private function streamGeminiFallback(string $prompt, int $maxTokens, callable $onChunk): string
{
    $result = $this->callGemini($prompt, $maxTokens);
    $text = $result['summary'];
    // Simulate streaming by sending 20 chars at a time
    foreach (str_split($text, 20) as $chunk) {
        $onChunk($chunk);
        usleep(50000); // 50ms delay to simulate
    }
    return $text;
}
// to do - For Mistral, add a similar streamMistral method using their streaming endpoint


public function generateTimeline(array $documents): array
{
    $context = '';
    foreach ($documents as $id => $text) {
        $context .= "Document ID {$id}:\n{$text}\n\n";
    }

    $prompt = <<<PROMPT
You are a legal assistant. Based on the document texts below, generate a chronological timeline of events. Return ONLY a JSON array of objects with keys "date" (YYYY-MM-DD), "title", "description", and "source_document_id" (the document ID that this event appears in). Order by date. Do not include events without a date.

Document texts:
{$context}
PROMPT;

    $result = $this->callApi($prompt, 2048);
    $raw = trim($result['summary'] ?? '');
    $raw = str_replace(['```json', '```'], '', $raw);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
}
