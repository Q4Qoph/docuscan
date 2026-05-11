<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalCase extends Model
{
    protected $table = 'cases';

    protected $fillable = [
        'user_id',
        'reference',
        'debtor_name',
        'debtor_contact',
        'notes',
        'status',
    ];

    // The 14 legal stages in order
    const STAGES = [
        'letter_of_instruction'    => 'Letter of Instruction',
        'demand_letter'            => 'Demand Letter',
        'demand_letter_reply'      => 'Demand Letter Reply',
        'instruction_to_file'      => 'Instruction to File',
        'plaint'                   => 'Plaint',
        'affidavit_of_service'     => 'Affidavit of Service',
        'defence'                  => 'Defence',
        'request_for_judgment'     => 'Request for Judgment',
        'default_judgment'         => 'Default Judgment',
        'hearing_judgment'         => 'Hearing & Judgment',
        'decree'                   => 'Decree',
        'warrants'                 => 'Warrants',
        'proclamation'             => 'Proclamation',
        'evidence_of_payment'      => 'Evidence of Payment',
        'memorandum_of_appeal'     => 'Memorandum of Appeal',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'case_id');
    }

    /**
     * Returns list of stage keys that have at least one document uploaded.
     */
    public function completedStages(): array
    {
        return $this->documents()
            ->whereNotNull('document_type')
            ->pluck('document_type')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Returns the next missing stage key, or null if all complete.
     */
    public function nextMissingStage(): ?string
    {
        $completed = $this->completedStages();
        foreach (array_keys(self::STAGES) as $stage) {
            if (! in_array($stage, $completed)) {
                return $stage;
            }
        }
        return null;
    }

    /**
     * Human-readable label for a stage key.
     */
    public static function stageLabel(string $key): string
    {
        return self::STAGES[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Auto-generate a unique case reference.
     */
    public static function generateReference(): string
    {
        $year  = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('CASE-%s-%03d', $year, $count);
    }
}