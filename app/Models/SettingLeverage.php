<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingLeverage extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'display',
        'value',
        'status',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        $settingLeverage = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('setting_leverage')
            ->logOnly([
                'id',
                'display',
                'value',
                'status',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($settingLeverage) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} setting leverage with ID: {$settingLeverage->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
