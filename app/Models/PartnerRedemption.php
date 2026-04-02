<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerRedemption extends Model
{
    protected $table = 'partner_redemptions';

    const STATUS_PROCESSING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_FAILED = 3;

    protected $fillable = [
        'redemption_no',
        'partner_id',
        'goods_id',
        'quantity',
        'unit_price',
        'total_amount',
        'status',
        'codes',
        'error_message',
    ];

    protected $casts = [
        'codes' => 'array',
    ];

    public static function getStatusMap(): array
    {
        return [
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_FAILED => '失败',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(PartnerWalletTransaction::class, 'redemption_id');
    }
}
