<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model  = config('services.gemini.model');
    }

    /**
     * Summarize text based on optional user criteria.
     *
     * @param string $text      The extracted PDF text
     * @param string|null $criteria  User's summarization instructions
     * @return array{summary: string, tokens_used: int, model: string}
     * @throws \Exception
     */
    public function summarize(string $text, ?string $criteria = null): array
    {
        $prompt = $this->buildPrompt($text, $criteria);

        $response = Http::timeout(60)
            ->withHeader('x-goog-api-key', $this->apiKey)
            ->post("{$this->baseUrl}/{$this->model}:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => 0.3,
                    'maxOutputTokens' => 2048,
                ],
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown Gemini API error');
            Log::error('Gemini API request failed', [
                'status' => $response->status(),
                'error'  => $error,
            ]);
            throw new \Exception("Gemini API error: {$error}");
        }

        $data = $response->json();

        // Extract the text from Gemini's response structure
        $summary = $data['candidates'][0]['content']['parts'][0]['text']
            ?? throw new \Exception('Unexpected Gemini response structure.');

        $tokensUsed = $data['usageMetadata']['totalTokenCount'] ?? 0;

        return [
            'summary'     => trim($summary),
            'tokens_used' => $tokensUsed,
            'model'       => $this->model,
        ];
    }

    /**
     * Build the prompt sent to Gemini.
     * This is where criteria gets injected.
     */
    private function buildPrompt(string $text, ?string $criteria): string
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
     * Classify a legal document into one of the known stage types.
     *
     * @param string $text Extracted PDF text
     * @return string|null Stage key or null if unrecognised
     */
    public function classifyLegalDocument(string $text): ?string
    {
        $stages = implode("\n", array_map(
            fn($k, $v) => "- {$k}: {$v}",
            array_keys(\App\Models\LegalCase::STAGES),
            \App\Models\LegalCase::STAGES
        ));

        $prompt = <<<PROMPT
You are a legal document classifier for a Kenyan law firm.

Classify the document below into EXACTLY ONE of these types (return only the key, nothing else):
{$stages}

If the document does not match any type, return: unknown

Document text:
---
{$text}
---

Return only the key (e.g. "plaint" or "demand_letter"). No explanation.
PROMPT;

        $response = Http::timeout(30)
            ->withHeader('x-goog-api-key', $this->apiKey)
            ->post("{$this->baseUrl}/{$this->model}:generateContent", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature'     => 0.1,
                    'maxOutputTokens' => 20,
                ],
            ]);

        if ($response->failed()) {
            return null;
        }

        $result = trim($response->json('candidates.0.content.parts.0.text') ?? '');
        $validKeys = array_keys(\App\Models\LegalCase::STAGES);

        return in_array($result, $validKeys) ? $result : null;
    }

    
}
