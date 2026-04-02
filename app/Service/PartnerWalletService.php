<?php

namespace App\Service;

use App\Exceptions\RuleValidationException;
use App\Models\Goods;
use App\Models\Order;
use App\Models\Partner;
use App\Models\PartnerRedemption;
use App\Models\PartnerWalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartnerWalletService
{
    /**
     * @var \App\Service\GoodsService
     */
    private $goodsService;

    /**
     * @var \App\Service\ShyApiRedemptionService
     */
    private $shyApiRedemptionService;

    public function __construct()
    {
        $this->goodsService = app('Service\GoodsService');
        $this->shyApiRedemptionService = app('Service\ShyApiRedemptionService');
    }

    public function settleDueTransactions(?Partner $partner = null): int
    {
        $query = PartnerWalletTransaction::query()
            ->where('type', PartnerWalletTransaction::TYPE_COMMISSION)
            ->where('status', PartnerWalletTransaction::STATUS_PENDING)
            ->whereNotNull('available_at')
            ->where('available_at', '<=', now());

        if ($partner) {
            $query->where('partner_id', $partner->id);
        }

        return $query->update([
            'status' => PartnerWalletTransaction::STATUS_AVAILABLE,
            'updated_at' => now(),
        ]);
    }

    public function buildSummary(Partner $partner): array
    {
        $this->settleDueTransactions($partner);

        $baseQuery = PartnerWalletTransaction::query()->where('partner_id', $partner->id);

        $availableAmount = (float) (clone $baseQuery)
            ->where('status', PartnerWalletTransaction::STATUS_AVAILABLE)
            ->sum('amount');

        $pendingAmount = (float) (clone $baseQuery)
            ->where('type', PartnerWalletTransaction::TYPE_COMMISSION)
            ->where('status', PartnerWalletTransaction::STATUS_PENDING)
            ->sum('amount');

        $lockedAmount = abs((float) (clone $baseQuery)
            ->where('status', PartnerWalletTransaction::STATUS_LOCKED)
            ->sum('amount'));

        $earnedAmount = (float) (clone $baseQuery)
            ->where('type', PartnerWalletTransaction::TYPE_COMMISSION)
            ->whereIn('status', [
                PartnerWalletTransaction::STATUS_PENDING,
                PartnerWalletTransaction::STATUS_AVAILABLE,
                PartnerWalletTransaction::STATUS_LOCKED,
            ])
            ->sum('amount');

        $redeemedAmount = abs((float) (clone $baseQuery)
            ->where('type', PartnerWalletTransaction::TYPE_REDEEM)
            ->where('status', PartnerWalletTransaction::STATUS_AVAILABLE)
            ->sum('amount'));

        $directInviteCount = $partner->invitees()->count();
        $teamInviteCount = Partner::query()->whereIn('inviter_id', $partner->invitees()->pluck('id'))->count();

        $completedOrders = Order::query()
            ->where('partner_id', $partner->id)
            ->where('status', Order::STATUS_COMPLETED);

        return [
            'available_amount' => round($availableAmount, 2),
            'pending_amount' => round($pendingAmount, 2),
            'locked_amount' => round($lockedAmount, 2),
            'earned_amount' => round($earnedAmount, 2),
            'redeemed_amount' => round($redeemedAmount, 2),
            'direct_invite_count' => $directInviteCount,
            'team_invite_count' => $teamInviteCount,
            'completed_order_count' => (clone $completedOrders)->count(),
            'completed_order_amount' => round((float) (clone $completedOrders)->sum('actual_price'), 2),
        ];
    }

    public function recentTransactions(Partner $partner, int $limit = 20)
    {
        $this->settleDueTransactions($partner);

        return PartnerWalletTransaction::query()
            ->with(['order', 'goods', 'sourcePartner'])
            ->where('partner_id', $partner->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function recentRedemptions(Partner $partner, int $limit = 10)
    {
        return PartnerRedemption::query()
            ->with('goods')
            ->where('partner_id', $partner->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function createCommissionForCompletedOrder(Order $order): void
    {
        if (!partner_enabled() || (int) $order->status !== Order::STATUS_COMPLETED) {
            return;
        }

        $partner = $order->partner;
        if (!$partner || (int) $partner->status !== Partner::STATUS_ENABLED) {
            return;
        }

        if (bccomp((string) $order->actual_price, '0', 2) <= 0) {
            return;
        }

        $orderEmail = strtolower(trim((string) $order->email));
        if ($orderEmail !== '' && $orderEmail === strtolower((string) $partner->email)) {
            return;
        }

        $freezeDays = max(0, (int) dujiaoka_config_get('partner_commission_freeze_days', 0));
        $status = $freezeDays > 0
            ? PartnerWalletTransaction::STATUS_PENDING
            : PartnerWalletTransaction::STATUS_AVAILABLE;
        $availableAt = $freezeDays > 0 ? now()->addDays($freezeDays) : now();

        $levelOneRate = (float) dujiaoka_config_get('partner_level_one_rate', 20);
        $levelTwoRate = (float) dujiaoka_config_get('partner_level_two_rate', 10);

        $this->createCommissionTransaction($partner, $order, $partner, 1, $levelOneRate, $status, $availableAt);

        $inviter = $partner->inviter;
        if ($inviter && (int) $inviter->status === Partner::STATUS_ENABLED) {
            $this->createCommissionTransaction($inviter, $order, $partner, 2, $levelTwoRate, $status, $availableAt);
        }
    }

    public function redeemGoods(Partner $partner, Goods $goods, int $quantity): PartnerRedemption
    {
        if ($quantity <= 0) {
            throw new RuleValidationException('兑换数量必须大于 0');
        }

        $goods = $this->goodsService->detail((int) $goods->id);
        $this->goodsService->validatorGoodsStatus($goods);
        if (!$goods->isShyApiDelivery()) {
            throw new RuleValidationException('当前商品暂不支持合伙人直接兑换');
        }

        $goods = $this->goodsService->refreshDynamicStock($goods);
        if ($quantity > (int) $goods->in_stock) {
            throw new RuleValidationException('当前可兑换库存不足');
        }

        $totalAmount = round((float) $goods->actual_price * $quantity, 2);
        if (bccomp((string) $totalAmount, '0', 2) <= 0) {
            throw new RuleValidationException('当前商品兑换金额异常');
        }

        $reserveData = DB::transaction(function () use ($partner, $goods, $quantity, $totalAmount) {
            $lockedPartner = Partner::query()->whereKey($partner->id)->lockForUpdate()->first();
            if (!$lockedPartner || (int) $lockedPartner->status !== Partner::STATUS_ENABLED) {
                throw new RuleValidationException('当前合伙人账号不可用');
            }

            $this->settleDueTransactions($lockedPartner);

            $availableBalance = $this->availableBalance($lockedPartner->id);
            if (bccomp((string) $availableBalance, (string) $totalAmount, 2) < 0) {
                throw new RuleValidationException('可用佣金不足，暂时无法兑换该卡券');
            }

            $redemption = PartnerRedemption::query()->create([
                'redemption_no' => 'PR' . now()->format('YmdHis') . Str::upper(Str::random(6)),
                'partner_id' => $lockedPartner->id,
                'goods_id' => $goods->id,
                'quantity' => $quantity,
                'unit_price' => $goods->actual_price,
                'total_amount' => $totalAmount,
                'status' => PartnerRedemption::STATUS_PROCESSING,
                'codes' => [],
                'error_message' => '',
            ]);

            $walletTransaction = PartnerWalletTransaction::query()->create([
                'partner_id' => $lockedPartner->id,
                'goods_id' => $goods->id,
                'redemption_id' => $redemption->id,
                'type' => PartnerWalletTransaction::TYPE_REDEEM,
                'level' => null,
                'rate' => 0,
                'amount' => -$totalAmount,
                'status' => PartnerWalletTransaction::STATUS_LOCKED,
                'available_at' => null,
                'description' => '兑换商品：' . $goods->gd_name . ' x ' . $quantity,
            ]);

            return [$redemption->id, $walletTransaction->id];
        });

        [$redemptionId, $walletTransactionId] = $reserveData;

        try {
            $codes = $this->shyApiRedemptionService->issueRedemptions(
                $goods,
                $quantity,
                PartnerRedemption::query()->whereKey($redemptionId)->value('redemption_no')
            );
        } catch (\Throwable $exception) {
            DB::transaction(function () use ($redemptionId, $walletTransactionId, $exception) {
                PartnerRedemption::query()
                    ->whereKey($redemptionId)
                    ->update([
                        'status' => PartnerRedemption::STATUS_FAILED,
                        'error_message' => $exception->getMessage(),
                        'updated_at' => now(),
                    ]);

                PartnerWalletTransaction::query()
                    ->whereKey($walletTransactionId)
                    ->update([
                        'status' => PartnerWalletTransaction::STATUS_CANCELLED,
                        'updated_at' => now(),
                    ]);
            });

            throw new RuleValidationException($exception->getMessage());
        }

        DB::transaction(function () use ($redemptionId, $walletTransactionId, $codes) {
            PartnerRedemption::query()
                ->whereKey($redemptionId)
                ->update([
                    'status' => PartnerRedemption::STATUS_COMPLETED,
                    'codes' => json_encode($codes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'error_message' => '',
                    'updated_at' => now(),
                ]);

            PartnerWalletTransaction::query()
                ->whereKey($walletTransactionId)
                ->update([
                    'status' => PartnerWalletTransaction::STATUS_AVAILABLE,
                    'available_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        return PartnerRedemption::query()->with('goods')->findOrFail($redemptionId);
    }

    private function createCommissionTransaction(
        Partner $receiver,
        Order $order,
        Partner $sourcePartner,
        int $level,
        float $rate,
        int $status,
        $availableAt
    ): void {
        if ($rate <= 0) {
            return;
        }

        $amount = round((float) $order->actual_price * $rate / 100, 2);
        if (bccomp((string) $amount, '0', 2) <= 0) {
            return;
        }

        $exists = PartnerWalletTransaction::query()
            ->where('partner_id', $receiver->id)
            ->where('order_id', $order->id)
            ->where('type', PartnerWalletTransaction::TYPE_COMMISSION)
            ->where('level', $level)
            ->exists();

        if ($exists) {
            return;
        }

        PartnerWalletTransaction::query()->create([
            'partner_id' => $receiver->id,
            'order_id' => $order->id,
            'source_partner_id' => $sourcePartner->id,
            'goods_id' => $order->goods_id,
            'redemption_id' => null,
            'type' => PartnerWalletTransaction::TYPE_COMMISSION,
            'level' => $level,
            'rate' => $rate,
            'amount' => $amount,
            'status' => $status,
            'available_at' => $availableAt,
            'description' => sprintf('订单 %s 的 %d 级分销佣金', $order->order_sn, $level),
        ]);
    }

    private function availableBalance(int $partnerId): float
    {
        return (float) PartnerWalletTransaction::query()
            ->where('partner_id', $partnerId)
            ->where('status', PartnerWalletTransaction::STATUS_AVAILABLE)
            ->sum('amount');
    }
}
