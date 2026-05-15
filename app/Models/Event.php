<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'user_id',
        'case_id',
        'title',
        'description',
        'date',
        'type',
        'completed',
    ];

    protected $casts = [
        'date' => 'datetime',
        'completed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    // Scope for upcoming events
    public function scopeUpcoming($query)
    {
        return $query->where('completed', false)
                     ->where('date', '>=', now());
    }
}