<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\PdfService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillExtractedText extends Command
{
    protected $signature = 'docs:backfill-text';
    protected $description = 'Extract and save text for all documents that lack it';

    public function handle(PdfService $pdfService): int
    {
        $documents = Document::whereNull('extracted_text')->get();
        $this->info("Found {$documents->count} documents to process.");

        foreach ($documents as $doc) {
            $this->info("Processing document {$doc->id} ...");
            try {
                $absolutePath = Storage::disk('local')->path($doc->storage_path);
                $text = $pdfService->extractText($absolutePath);
                $doc->update(['extracted_text' => $text]);
                $this->info("  Done.");
            } catch (\Exception $e) {
                $this->error("  Failed: {$e->getMessage()}");
            }
        }

        $this->info('Backfill complete.');
        return self::SUCCESS;
    }
}