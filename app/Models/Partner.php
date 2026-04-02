<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $table = 'partners';

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $fillable = [
        'name',
        'email',
        'password',
        'referral_code',
        'inviter_id',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    public static function getStatusMap(): array
    {
        return [
            self::STATUS_ENABLED => '启用',
            self::STATUS_DISABLED => '停用',
        ];
    }

    public function inviter()
    {
        return $this->belongsTo(self::class, 'inviter_id');
    }

    public function invitees()
    {
        return $this->hasMany(self::class, 'inviter_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(PartnerWalletTransaction::class, 'partner_id');
    }

    public function redemptions()
    {
        return $this->hasMany(PartnerRedemption::class, 'partner_id');
    }

    public function attributedOrders()
    {
        return $this->hasMany(Order::class, 'partner_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) $this->name);
        if ($name !== '') {
            return $name;
        }

        return (string) $this->email;
    }
}
