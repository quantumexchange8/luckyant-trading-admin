<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicantTransport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_form_id',
        'application_candidate_id',
        'user_id',
        'type',
        'name',
        'gender',
        'country_id',
        'dob',
        'phone_number',
        'identity_number',
        'departure_address',
        'return_address',
    ];

    // Relations
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
}
