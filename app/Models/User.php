<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, HasRoles;

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
        return $this->belongsTo(SettingRank::class, 'setting_rank_id', 'id');
    }

    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
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

    public function getFirstLeader()
    {
        $first_leader = null;

        $upline = explode("-", substr($this->hierarchyList, 1, -1));
        $count = count($upline) - 1;
        if ($count > 0) {
            while ($count > -1) {
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
}
