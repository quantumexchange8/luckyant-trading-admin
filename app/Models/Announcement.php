<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'subject',
        'details',
        'type',
        'status'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $announcement = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('announcement')
            ->logOnly([
                'id',
                'subject',
                'details',
                'type',
                'status'
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($announcement) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} announcement with ID: {$announcement->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
