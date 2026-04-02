<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerWalletTransaction extends Model
{
    protected $table = 'partner_wallet_transactions';

    const STATUS_PENDING = 1;
    const STATUS_AVAILABLE = 2;
    const STATUS_LOCKED = 3;
    const STATUS_CANCELLED = 4;

    const TYPE_COMMISSION = 'commission';
    const TYPE_REDEEM = 'redeem';
    const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'partner_id',
        'order_id',
        'source_partner_id',
        'goods_id',
        'redemption_id',
        'type',
        'level',
        'rate',
        'amount',
        'status',
        'available_at',
        'description',
    ];

    protected $casts = [
        'available_at' => 'datetime',
    ];

    public static function getStatusMap(): array
    {
        return [
            self::STATUS_PENDING => '待结算',
            self::STATUS_AVAILABLE => '可用',
            self::STATUS_LOCKED => '处理中',
            self::STATUS_CANCELLED => '已取消',
        ];
    }

    public static function getTypeMap(): array
    {
        return [
            self::TYPE_COMMISSION => '佣金',
            self::TYPE_REDEEM => '卡券兑换',
            self::TYPE_MANUAL => '人工调账',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function sourcePartner()
    {
        return $this->belongsTo(Partner::class, 'source_partner_id');
    }

    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id');
    }

    public function redemption()
    {
        return $this->belongsTo(PartnerRedemption::class, 'redemption_id');
    }
}
