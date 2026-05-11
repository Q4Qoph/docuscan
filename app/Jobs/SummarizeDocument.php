<?php

namespace App\Jobs;

use App\Models\Summary;
use App\Services\GeminiService;
use App\Services\PdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SummarizeDocument implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Number of times to attempt this job before marking it failed.
     */
    public int $tries = 3;

    /**
     * Wait 60 seconds before retrying a failed attempt.
     */
    public int $backoff = 60;

    /**
     * Maximum seconds this job can run before being killed.
     */
    public int $timeout = 120;

    public function __construct(public Summary $summary)
    {
    }

    public function handle(PdfService $pdfService, GeminiService $geminiService): void
    {   
        // If already completed, skip — prevents duplicate processing
if ($this->summary->isCompleted()) {
    Log::info('SummarizeDocument skipped — already completed', [
        'summary_id' => $this->summary->id,
    ]);
    return;
}
        Log::info('SummarizeDocument job started', ['summary_id' => $this->summary->id]);

        // Mark as processing so the UI updates
        $this->summary->update(['status' => 'processing']);

        try {
            // Step 1: Get the absolute path to the stored PDF
            $absolutePath = Storage::disk('local')->path(
                $this->summary->document->storage_path
            );

            // Step 2: Extract text from the PDF
            $extractedText = $pdfService->extractText($absolutePath);

            // Classify the document type automatically
            $documentType = $geminiService->classifyLegalDocument($extractedText);
            if ($documentType) {
                $this->summary->document->update(['document_type' => $documentType]);
            }

            // Step 3: If the document is very long, summarize in chunks
            $chunks = $pdfService->chunkText($extractedText);

            if (count($chunks) === 1) {
                // Short document — summarize directly
                $result = $geminiService->summarize($chunks[0], $this->summary->criteria);
                $finalSummary = $result['summary'];
                $tokensUsed   = $result['tokens_used'];
                $modelUsed    = $result['model'];
            } else {
                // Long document — map-reduce approach
                // Step 1: Summarize each chunk
                $chunkSummaries = [];
                $totalTokens    = 0;
                $modelUsed      = '';

                foreach ($chunks as $i => $chunk) {
                    Log::info("Summarizing chunk " . ($i + 1) . " of " . count($chunks));
                    $result           = $geminiService->summarize($chunk, null);
                    $chunkSummaries[] = $result['summary'];
                    $totalTokens     += $result['tokens_used'];
                    $modelUsed        = $result['model'];
                }

                // Step 2: Summarize the chunk summaries into one final summary
                $combinedText = implode("\n\n---\n\n", $chunkSummaries);
                $finalResult  = $geminiService->summarize(
                    $combinedText,
                    $this->summary->criteria ?? 'Combine these partial summaries into one cohesive final summary.'
                );

                $finalSummary = $finalResult['summary'];
                $tokensUsed   = $totalTokens + $finalResult['tokens_used'];
            }

            // Step 4: Save everything to the database
            $this->summary->update([
                'extracted_text' => $extractedText,
                'summary'        => $finalSummary,
                'ai_model_used'  => $modelUsed,
                'tokens_used'    => $tokensUsed,
                'status'         => 'completed',
            ]);

            Log::info('SummarizeDocument job completed', [
                'summary_id'  => $this->summary->id,
                'tokens_used' => $tokensUsed,
            ]);

        } catch (\Exception $e) {
            Log::error('SummarizeDocument job failed', [
                'summary_id' => $this->summary->id,
                'error'      => $e->getMessage(),
            ]);

            // 503 = temporary server overload — worth retrying automatically
            $isRetryable = str_contains($e->getMessage(), '503')
                        || str_contains($e->getMessage(), 'high demand')
                        || str_contains($e->getMessage(), 'temporarily');

            if ($isRetryable && $this->attempts() < $this->tries) {
                // Put status back to pending and let the queue retry
                $this->summary->update(['status' => 'pending']);
                $this->release($this->backoff); // re-queue after backoff seconds
                return;
            }

            // Permanent failure — record it
            $this->summary->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}