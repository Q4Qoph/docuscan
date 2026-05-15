<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    protected $fillable = ['user_id', 'case_id', 'description', 'started_at', 'ended_at', 'hours', 'rate', 'billable'];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'billable'   => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    // Automatically calculate hours from start/end if not set
    protected static function booted(): void
    {
        static::saving(function (TimeEntry $entry) {
            if ($entry->started_at && $entry->ended_at && !$entry->hours) {
                $entry->hours = round($entry->started_at->diffInMinutes($entry->ended_at) / 60, 2);
            }
        });
    }
}