<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'status',
        'handle_by',
    ];

    // Relations
    public function leaders(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, ApplicationFormToLeader::class, 'application_form_id', 'id', 'id', 'user_id');
    }

    public function approved_applicants(): HasMany
    {
        return $this->hasMany(Applicant::class, 'application_form_id')->where('status', 'approved');
    }
}
