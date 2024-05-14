<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'value',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $setting = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('setting')
            ->logOnly([
                'id',
                'name',
                'slug',
                'value',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($setting) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} setting with ID: {$setting->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
