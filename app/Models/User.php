<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, HasRoles, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getChildrenIds(): array
    {
        return User::query()->where('hierarchyList', 'like', '%-' . $this->id . '-%')
            ->where('status', 'Active')
            ->pluck('id')
            ->toArray();
    }
    public function rank(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SettingRank::class, 'display_rank_id', 'id');
    }

    public function ofCountry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class,'country', 'id');
    }

    public function upline(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'upline_id', 'id');
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class, 'upline_id', 'id');
    }

    public function tradingAccounts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TradingAccount::class, 'user_id', 'id');
    }

    public function tradingUser(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TradingUser::class, 'user_id', 'id');
    }

    public function wallets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id', 'id');
    }

    public function top_leader(): \Illuminate\Database\Eloquent\Relations\belongsTo
    {
        return $this->belongsTo(User::class, 'top_leader_id', 'id');
    }

    public function walletLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WalletLog::class);
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function getFirstLeader()
    {
        $first_leader = null;

        $upline = explode("-", substr($this->hierarchyList, 1, -1));
        $count = count($upline) - 1;

        // Check if there are elements in $upline before accessing
        if ($count >= 0) {
            while ($count >= 0) {
                $user = User::find($upline[$count]);
                if (!empty($user->leader_status) && $user->leader_status == 1) {
                    $first_leader = $user;
                    break; // Found the first leader, exit the loop
                }
                $count--;
            }
        }
        return $first_leader;
    }

    public function setReferralId(): void
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = 'LAT';

        $length = 10 - strlen($randomString); // Remaining length after 'LAT'

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        $this->referral_code = $randomString;
        $this->save();
    }

    public function getActivitylogOptions(): LogOptions
    {
        $user = $this->fresh();

        return LogOptions::defaults()
            ->useLogName('user')
            ->logOnly([
                'id',
                'name',
                'username',
                'email_verified_at',
                'email',
                'password',
                'security_pin',
                'dial_code',
                'phone',
                'chinese_name',
                'dob',
                'address_1',
                'address_2',
                'postcode',
                'city',
                'state',
                'country',
                'nationality',
                'register_ip',
                'last_login_ip',
                'cash_wallet_id',
                'cash_wallet',
                'kyc_approval',
                'kyc_approval_date',
                'kyc_approval_description',
                'identification_number',
                'gender',
                'top_leader_id',
                'upline_id',
                'hierarchyList',
                'leader_status',
                'referral_code',
                'role',
                'setting_rank_id',
                'display_rank_id',
                'rank_up_status',
                'status',
                'is_public',
                'remark',
                'remember_token',
                'password_changed_at',
            ])
            ->setDescriptionForEvent(function (string $eventName) use ($user) {
                $actorName = Auth::user() ? Auth::user()->name : 'System';
                return "{$actorName} has {$eventName} user with ID: {$user->id}";
            })
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
