<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use League\CommonMark\CommonMarkConverter;

class Summary extends Model
{
    protected $fillable = [
        'document_id',
        'criteria',
        'extracted_text',
        'summary',
        'ai_model_used',
        'tokens_used',
        'status',
        'error_message',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
    public function summaryAsHtml(): string
    {
        if (empty($this->summary)) {
            return '';
        }

        $converter = new CommonMarkConverter([
            'html_input'         => 'strip',   // strip any HTML Gemini might include
            'allow_unsafe_links' => false,      // no javascript: links
        ]);

        return (string) $converter->convert($this->summary);
    }
}