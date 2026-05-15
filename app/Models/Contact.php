<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    protected $fillable = ['user_id', 'type', 'name', 'email', 'phone', 'id_number', 'address', 'notes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cases(): BelongsToMany
{
    return $this->belongsToMany(LegalCase::class, 'case_contact', 'contact_id', 'case_id')
        ->withPivot('role')
        ->withTimestamps();
}
}