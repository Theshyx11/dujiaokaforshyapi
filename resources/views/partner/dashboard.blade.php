@extends('partner.layouts.app')

@section('content')
<div class="partner-hero">
    <div>
        <h1 class="partner-hero-title">合伙人中心</h1>
        <p class="partner-hero-subtitle">你的推广链接、订单贡献、佣金台账和兑换记录都集中在这里。佣金不会直接提现，而是可在商城内直接兑换 ShyAPI 卡券。</p>
    </div>
    <form method="post" action="{{ url('partner/logout') }}">
        {{ csrf_field() }}
        <button class="partner-logout" type="submit">退出登录</button>
    </form>
</div>

<div class="partner-grid">
    <div class="partner-card">
        <div class="partner-card-label">可用佣金</div>
        <div class="partner-card-value">¥ {{ number_format($summary['available_amount'], 2) }}</div>
        <div class="partner-card-note">可直接兑换 ShyAPI 卡券</div>
    </div>
    <div class="partner-card">
        <div class="partner-card-label">待结算佣金</div>
        <div class="partner-card-value">¥ {{ number_format($summary['pending_amount'], 2) }}</div>
        <div class="partner-card-note">冻结期结束后自动转为可用</div>
    </div>
    <div class="partner-card">
        <div class="partner-card-label">已兑换总额</div>
        <div class="partner-card-value">¥ {{ number_format($summary['redeemed_amount'], 2) }}</div>
        <div class="partner-card-note">已转换为卡券额度</div>
    </div>
    <div class="partner-card">
        <div class="partner-card-label">推广订单总额</div>
        <div class="partner-card-value">¥ {{ number_format($summary['completed_order_amount'], 2) }}</div>
        <div class="partner-card-note">{{ $summary['completed_order_count'] }} 笔已完成订单</div>
    </div>
</div>

<div class="partner-panels">
    <div class="partner-panel">
        <div class="partner-panel-title">
            <h3>推广链接</h3>
            <span class="partner-hint">邀请码：{{ $partner->referral_code }}</span>
        </div>
        <div class="partner-linkbox">
            <input type="text" id="buyerInviteLink" readonly value="{{ $buyerInviteLink }}">
            <button type="button" onclick="copyPartnerValue('buyerInviteLink')">复制买家推广链接</button>
        </div>
        <div class="partner-linkbox">
            <input type="text" id="partnerInviteLink" readonly value="{{ $partnerInviteLink }}">
            <button type="button" onclick="copyPartnerValue('partnerInviteLink')">复制合伙人邀请链接</button>
        </div>
        <div class="partner-hint">直营推广会把订单归因到你的账号；邀请新合伙人注册后，其成交订单还会继续给你产生二级裂变佣金。</div>
    </div>

    <div class="partner-panel">
        <div class="partner-panel-title">
            <h3>团队概览</h3>
            <span class="partner-hint">直接邀请 {{ $summary['direct_invite_count'] }} 人，二级团队 {{ $summary['team_invite_count'] }} 人</span>
        </div>
        @if($directInvitees->count())
            <table class="partner-table">
                <thead>
                <tr>
                    <th>名称</th>
                    <th>邮箱</th>
                    <th>加入时间</th>
                </tr>
                </thead>
                <tbody>
                @foreach($directInvitees as $invitee)
                    <tr>
                        <td>{{ $invitee->display_name }}</td>
                        <td>{{ $invitee->email }}</td>
                        <td>{{ $invitee->created_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="partner-empty">还没有直接邀请到新的合伙人。</div>
        @endif
    </div>
</div>

<div class="partner-panels">
    <div class="partner-panel">
        <div class="partner-panel-title">
            <h3>佣金兑换卡券</h3>
            <span class="partner-hint">兑换金额按当前商品售价扣减</span>
        </div>
        @if($redeemableGoods->count())
            <form method="post" action="{{ url('partner/redeem') }}">
                {{ csrf_field() }}
                <div class="partner-form-grid">
                    <select class="partner-form-control" name="goods_id" required>
                        @foreach($redeemableGoods as $goods)
                            <option value="{{ $goods->id }}">{{ $goods->gd_name }} | ¥{{ number_format($goods->actual_price, 2) }} | 库存 {{ $goods->in_stock }}</option>
                        @endforeach
                    </select>
                    <input class="partner-form-control" type="number" min="1" max="50" name="quantity" value="1" required>
                    <button class="partner-btn" type="submit">立即兑换</button>
                </div>
            </form>
            <div class="partner-hint" style="margin-top: 12px;">兑换成功后，ShyAPI 卡券会直接显示在下方“兑换记录”中，可立即拿去充值。</div>
        @else
            <div class="partner-empty">当前还没有可供兑换的 ShyAPI 商品。</div>
        @endif
    </div>

    <div class="partner-panel">
        <div class="partner-panel-title">
            <h3>资金状态</h3>
            <span class="partner-hint">避免提现链路，直接走站内兑换</span>
        </div>
        <table class="partner-table">
            <tbody>
            <tr>
                <th>累计获得佣金</th>
                <td>¥ {{ number_format($summary['earned_amount'], 2) }}</td>
            </tr>
            <tr>
                <th>处理中占用金额</th>
                <td>¥ {{ number_format($summary['locked_amount'], 2) }}</td>
            </tr>
            <tr>
                <th>当前可用佣金</th>
                <td>¥ {{ number_format($summary['available_amount'], 2) }}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="partner-panels">
    <div class="partner-panel">
        <div class="partner-panel-title">
            <h3>佣金台账</h3>
            <span class="partner-hint">最近 {{ $transactions->count() }} 条</span>
        </div>
        @if($transactions->count())
            <table class="partner-table">
                <thead>
                <tr>
                    <th>时间</th>
                    <th>类型</th>
                    <th>金额</th>
                    <th>状态</th>
                    <th>说明</th>
                </tr>
                </thead>
                <tbody>
                @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->created_at }}</td>
                        <td>{{ \App\Models\PartnerWalletTransaction::getTypeMap()[$transaction->type] ?? $transaction->type }}</td>
                        <td>
                            <span class="partner-tag {{ $transaction->amount >= 0 ? 'partner-tag-positive' : 'partner-tag-negative' }}">
                                {{ $transaction->amount >= 0 ? '+' : '' }}{{ number_format($transaction->amount, 2) }}
                            </span>
                        </td>
                        <td><span class="partner-tag partner-tag-muted">{{ \App\Models\PartnerWalletTransaction::getStatusMap()[$transaction->status] ?? $transaction->status }}</span></td>
                        <td>{{ $transaction->description }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="partner-empty">暂时还没有佣金台账记录。</div>
        @endif
    </div>

    <div class="partner-panel">
        <div class="partner-panel-title">
            <h3>兑换记录</h3>
            <span class="partner-hint">生成的卡券可直接复制使用</span>
        </div>
        @if($redemptions->count())
            <table class="partner-table">
                <thead>
                <tr>
                    <th>编号</th>
                    <th>商品</th>
                    <th>状态</th>
                    <th>兑换码</th>
                </tr>
                </thead>
                <tbody>
                @foreach($redemptions as $redemption)
                    <tr>
                        <td>{{ $redemption->redemption_no }}</td>
                        <td>{{ $redemption->goods->gd_name ?? '-' }} x {{ $redemption->quantity }}</td>
                        <td><span class="partner-tag partner-tag-muted">{{ \App\Models\PartnerRedemption::getStatusMap()[$redemption->status] ?? $redemption->status }}</span></td>
                        <td>
                            @if($redemption->status === \App\Models\PartnerRedemption::STATUS_COMPLETED)
                                <textarea rows="5" readonly>{{ implode(PHP_EOL, $redemption->codes ?? []) }}</textarea>
                            @else
                                {{ $redemption->error_message ?: '处理中，请稍后刷新。' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="partner-empty">还没有兑换记录。</div>
        @endif
    </div>
</div>
@endsection

@section('js')
<script>
    function copyPartnerValue(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.select();
        el.setSelectionRange(0, 99999);
        document.execCommand('copy');
    }
</script>
@endsection
