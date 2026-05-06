<?php

namespace App\Services;

use Spatie\PdfToText\Pdf;
use Illuminate\Support\Facades\Log;

class PdfService
{
    /**
     * Extract plain text from a PDF file.
     *
     * @param string $absolutePath Full path to the PDF file on disk
     * @return string The extracted text
     * @throws \Exception If extraction fails
     */
    public function extractText(string $absolutePath): string
    {
        try {
            $text = Pdf::getText($absolutePath);

            if (empty(trim($text))) {
                throw new \Exception(
                    'No text could be extracted from this PDF. ' .
                    'It may be a scanned document or image-based PDF.'
                );
            }

            return $text;

        } catch (\Exception $e) {
            Log::error('PDF text extraction failed', [
                'path'  => $absolutePath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Chunk long text into smaller pieces for AI processing.
     * Gemini Flash supports 1M tokens but we keep chunks manageable.
     *
     * @param string $text
     * @param int $chunkSize Max characters per chunk
     * @return array<string>
     */
    public function chunkText(string $text, int $chunkSize = 12000): array
    {
        if (strlen($text) <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $words  = explode(' ', $text);
        $chunk  = '';

        foreach ($words as $word) {
            if (strlen($chunk) + strlen($word) + 1 > $chunkSize) {
                $chunks[] = trim($chunk);
                $chunk    = '';
            }
            $chunk .= $word . ' ';
        }

        if (!empty(trim($chunk))) {
            $chunks[] = trim($chunk);
        }

        return $chunks;
    }
}
