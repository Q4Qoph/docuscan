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
}
