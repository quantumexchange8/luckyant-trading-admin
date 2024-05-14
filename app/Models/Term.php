<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Term extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'type',
        'title',
        'contents',
        'user_id',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        $term = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('term')
            ->logOnly([
                'id',
                'type',
                'title',
                'contents',
                'user_id',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($term) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} term with ID: {$term->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
