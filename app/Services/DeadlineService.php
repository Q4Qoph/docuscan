<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Document;

class DeadlineService
{
    /**
     * Map a document type to the next deadline event.
     * Returns [title, description, days_from_now] or null.
     */
    public static function getDeadlineForStage(string $documentType): ?array
    {
        return match ($documentType) {
            'affidavit_of_service' => [
                'title'       => 'Defence due',
                'description' => 'The defendant must file a defence within 15 days of service.',
                'days'        => 15,
            ],
            'request_for_judgment' => [
                'title'       => 'Default judgment available',
                'description' => 'If no defence is filed, default judgment may be entered after 15 days.',
                'days'        => 15,
            ],
            'judgment' => [
                'title'       => 'Appeal deadline',
                'description' => 'Notice of appeal must be filed within 30 days of the judgment.',
                'days'        => 30,
            ],
            // Add more rules as needed
            default => null,
        };
    }

    /**
     * Create a deadline event for a newly classified document.
     */
    public function createDeadlineFromDocument(Document $document): void
    {
        if (!$document->document_type || !$document->case_id) {
            return;
        }

        $deadline = self::getDeadlineForStage($document->document_type);
        if (!$deadline) {
            return;
        }

        $dueDate = $document->created_at->addDays($deadline['days']);

        // Avoid duplicates for the same stage
        $existing = Event::where('case_id', $document->case_id)
            ->where('title', $deadline['title'])
            ->where('completed', false)
            ->exists();

        if ($existing) {
            return;
        }

        Event::create([
            'user_id'     => $document->user_id,
            'case_id'     => $document->case_id,
            'title'       => $deadline['title'],
            'description' => $deadline['description'],
            'date'        => $dueDate,
            'type'        => 'deadline',
        ]);
    }

    /**
     * Mark a deadline as completed when a corresponding document is uploaded.
     * For example, when a Defence is uploaded, mark the 'Defence due' event as completed.
     */
    public function completeDeadlineIfExists(Document $document): void
    {
        $map = [
            'defence'              => 'Defence due',
            'default_judgment'     => 'Default judgment available',
            'memorandum_of_appeal' => 'Appeal deadline',
        ];

        $titleToComplete = $map[$document->document_type] ?? null;
        if (!$titleToComplete || !$document->case_id) {
            return;
        }

        Event::where('case_id', $document->case_id)
            ->where('title', $titleToComplete)
            ->where('completed', false)
            ->update(['completed' => true]);
    }
}