<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MasterTerms extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'master_id',
        'term_contents',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $master_terms = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('master_terms')
            ->logOnly([
                'id',
                'master_id',
                'term_contents',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($master_terms) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} master tnc with ID: {$master_terms->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
