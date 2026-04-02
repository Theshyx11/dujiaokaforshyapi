@extends('hyper.layouts.default')
@section('content')
@php
    $storefront = $storefront ?? [];
    $planCollection = collect($storefront['plans'] ?? []);
    $planInsightsById = $planCollection->keyBy('id');
    $goodsCount = $planCollection->count();
    $availableGoods = $planCollection->filter(function ($plan) {
        return (int) ($plan['stock'] ?? 0) > 0;
    })->values();
    $inStockGoodsCount = $availableGoods->count();
    $totalStock = $availableGoods->sum(function ($plan) {
        return (int) ($plan['stock'] ?? 0);
    });
    $fromPrice = $goodsCount > 0 ? number_format((float) $planCollection->min('price'), 2) : '0.00';
    $bestSavingGoods = $planCollection
        ->filter(function ($plan) {
            return (float) ($plan['saving'] ?? 0) > 0;
        })
        ->sortByDesc(function ($plan) {
            return (float) ($plan['saving'] ?? 0);
        })
        ->first();
    $maxSaving = $bestSavingGoods ? ($bestSavingGoods['saving_formatted'] ?? '0.00') : '0.00';
    $featuredPlan = $storefront['featured_plan'] ?? null;
    $guides = $storefront['guides'] ?? [];
    $notes = $storefront['notes'] ?? [];
@endphp
<section class="shyapi-shop-hero">
    <div class="shyapi-shop-hero-grid">
        <div class="shyapi-shop-hero-copy">
            <span class="shyapi-hero-kicker">ShyAPI Shop</span>
            <h1 class="shyapi-hero-title">买套餐、秒发码、回控制台直接充值</h1>
            <p class="shyapi-hero-description">
                这里专门负责 ShyAPI 额度套餐交易。支付完成后自动发放兑换码，你只需要回到
                <strong>code.shyapi.top</strong> 兑换额度、创建 API Key，然后按 OpenAI 兼容方式接入项目。
            </p>
            <div class="shyapi-hero-trust">
                <span>自动发码</span>
                <span>订单可追踪</span>
                <span>OpenAI 兼容接入</span>
                <span>合伙人佣金可站内兑换</span>
            </div>
            <div class="shyapi-hero-actions">
                <a class="btn btn-shyapi-primary" href="#group-all">浏览当前套餐</a>
                <a class="btn btn-shyapi-ghost" href="https://code.shyapi.top" target="_blank" rel="noopener">前往 API 控制台</a>
                <a class="btn btn-shyapi-ghost" href="https://code.shyapi.top/docs/" target="_blank" rel="noopener">查看接入文档</a>
                @if(partner_enabled())
                    <a class="btn btn-shyapi-ghost" href="{{ url(partner_auth() ? 'partner/dashboard' : 'partner/login') }}">进入合伙人中心</a>
                @endif
            </div>
        </div>
        <div class="shyapi-hero-panel">
            <div class="shyapi-hero-stat-grid">
                <div class="shyapi-hero-stat">
                    <span class="shyapi-hero-stat-label">在售套餐</span>
                    <strong>{{ $goodsCount }}</strong>
                </div>
                <div class="shyapi-hero-stat">
                    <span class="shyapi-hero-stat-label">可立即发放</span>
                    <strong>{{ $inStockGoodsCount }}</strong>
                </div>
                <div class="shyapi-hero-stat">
                    <span class="shyapi-hero-stat-label">起售价</span>
                    <strong>¥{{ $fromPrice }}</strong>
                </div>
            </div>
            <div class="shyapi-hero-inline-stats">
                <div>
                    <span>当前可用库存</span>
                    <strong>{{ $totalStock }}</strong>
                </div>
                <div>
                    <span>当前最高立省</span>
                    <strong>¥{{ $maxSaving }}</strong>
                </div>
            </div>
            <div class="shyapi-hero-steps">
                <div class="shyapi-hero-step"><span>01</span><p>下单并完成支付</p></div>
                <div class="shyapi-hero-step"><span>02</span><p>系统自动发放兑换码</p></div>
                <div class="shyapi-hero-step"><span>03</span><p>前往控制台充值并开始使用</p></div>
            </div>
        </div>
    </div>
</section>
<section class="shyapi-shortcut-grid">
    <a class="shyapi-shortcut-card" href="https://code.shyapi.top" target="_blank" rel="noopener">
        <span>控制台</span>
        <strong>管理额度、令牌和调用日志</strong>
        <p>充值到账后统一在控制台创建 API Key、查看余额和消费情况。</p>
    </a>
    <a class="shyapi-shortcut-card" href="https://code.shyapi.top/docs/" target="_blank" rel="noopener">
        <span>接入文档</span>
        <strong>从兑换到账到 SDK 配置一次看全</strong>
        <p>文档页已经整理好地址、示例代码、客户端配置和常见报错排查。</p>
    </a>
    @if(partner_enabled())
        <a class="shyapi-shortcut-card" href="{{ url(partner_auth() ? 'partner/dashboard' : 'partner/login') }}">
            <span>合伙人中心</span>
            <strong>推广、裂变、佣金兑换全部集中处理</strong>
            <p>不用走提现链路，佣金可直接兑换商城卡券，再回控制台完成充值。</p>
        </a>
    @else
        <a class="shyapi-shortcut-card" href="{{ url('order-search') }}">
            <span>订单查询</span>
            <strong>按订单号或邮箱快速查单</strong>
            <p>适合在支付后补拿兑换码、核对发货状态和查看历史订单详情。</p>
        </a>
    @endif
</section>
<section class="shyapi-proof-grid">
    @foreach($notes as $note)
        <article class="shyapi-proof-card">
            <span class="shyapi-proof-kicker">运营说明</span>
            <h3>{{ $note['title'] }}</h3>
            <p>{{ $note['description'] }}</p>
        </article>
    @endforeach
</section>
@if($planCollection->count() > 0)
<section class="shyapi-plan-section">
    <div class="shyapi-section-heading">
        <div>
            <span class="shyapi-hero-kicker">套餐体系</span>
            <h2>按使用阶段选，不用靠感觉下单</h2>
            <p>下面这些推荐和对比都由当前在售商品自动生成。以后你新增更多额度包，这里会一起更新。</p>
        </div>
        @if($featuredPlan)
            <div class="shyapi-section-callout">
                <span>当前主推</span>
                <strong>{{ $featuredPlan['name'] }}</strong>
                <p>{{ $featuredPlan['scenario_description'] }}</p>
            </div>
        @endif
    </div>
    <div class="shyapi-plan-grid">
        @foreach($storefront['plans'] as $plan)
            <article class="shyapi-plan-card @if($plan['is_featured']) is-featured @endif">
                <div class="shyapi-plan-card-top">
                    <div>
                        @if(!empty($plan['badge']))
                            <span class="shyapi-plan-badge">{{ $plan['badge'] }}</span>
                        @endif
                        <p class="shyapi-plan-kicker">{{ $plan['scenario_title'] }}</p>
                        <h3>{{ $plan['name'] }}</h3>
                    </div>
                    <div class="shyapi-plan-stock">{{ $plan['stock_label'] }}</div>
                </div>
                <p class="shyapi-plan-summary">{{ $plan['summary'] }}</p>
                <div class="shyapi-plan-price-line">
                    <strong>¥{{ $plan['price_formatted'] }}</strong>
                    <div class="shyapi-plan-price-meta">
                        @if(($plan['retail_price'] ?? 0) > ($plan['price'] ?? 0))
                            <span>原价 ¥{{ $plan['retail_price_formatted'] }}</span>
                        @endif
                        @if(($plan['saving'] ?? 0) > 0)
                            <span>立省 ¥{{ $plan['saving_formatted'] }}</span>
                        @endif
                    </div>
                </div>
                <div class="shyapi-plan-metric-grid">
                    <div>
                        <span>到手额度</span>
                        <strong>{{ $plan['quota_label'] }}</strong>
                    </div>
                    <div>
                        <span>单刀成本</span>
                        <strong>
                            @if($plan['unit_price_formatted'])
                                ¥{{ $plan['unit_price_formatted'] }}/刀
                            @else
                                按控制台计价
                            @endif
                        </strong>
                    </div>
                    <div>
                        <span>适合场景</span>
                        <strong>{{ $plan['scenario_title'] }}</strong>
                    </div>
                </div>
                <div class="shyapi-plan-bullet-list">
                    @foreach($plan['bullets'] as $bullet)
                        <div>{{ $bullet }}</div>
                    @endforeach
                </div>
                <a class="btn btn-shyapi-primary shyapi-plan-buy" href="{{ url($plan['path']) }}">直接购买</a>
            </article>
        @endforeach
    </div>
</section>
<section class="shyapi-compare-section">
    <div class="shyapi-section-heading shyapi-section-heading-compact">
        <div>
            <span class="shyapi-hero-kicker">套餐对比</span>
            <h2>核心差异一眼看完</h2>
            <p>如果你只想快速判断买哪一档，直接看这里就够了。</p>
        </div>
    </div>
    <div class="shyapi-compare-wrap">
        <table class="shyapi-compare-table">
            <thead>
                <tr>
                    <th>套餐</th>
                    <th>适合场景</th>
                    <th>到手额度</th>
                    <th>实付</th>
                    <th>单刀成本</th>
                    <th>库存</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($storefront['plans'] as $plan)
                    <tr>
                        <td>
                            <strong>{{ $plan['name'] }}</strong>
                            @if(!empty($plan['badge']))
                                <span class="shyapi-compare-badge">{{ $plan['badge'] }}</span>
                            @endif
                        </td>
                        <td>{{ $plan['scenario_description'] }}</td>
                        <td>{{ $plan['quota_label'] }}</td>
                        <td>¥{{ $plan['price_formatted'] }}</td>
                        <td>
                            @if($plan['unit_price_formatted'])
                                ¥{{ $plan['unit_price_formatted'] }}/刀
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $plan['stock'] }}</td>
                        <td><a href="{{ url($plan['path']) }}">购买</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
<section class="shyapi-buy-guide-grid">
    @foreach($guides as $guide)
        <article class="shyapi-buy-guide-card">
            <span>{{ $guide['label'] }}</span>
            <h3>{{ $guide['title'] }}</h3>
            <p>{{ $guide['description'] }}</p>
        </article>
    @endforeach
</section>
@endif
<section class="shyapi-collection-head">
    <div>
        <span class="shyapi-hero-kicker">套餐列表</span>
        <h2>当前在售套餐</h2>
        <p>对比区看完之后，就可以直接在这里下单。支付完成后回控制台兑换，再创建 API Key 开始使用。</p>
    </div>
    <div class="shyapi-collection-stat-grid">
        <div class="shyapi-collection-stat">
            <span>可发放库存</span>
            <strong>{{ $totalStock }}</strong>
        </div>
        <div class="shyapi-collection-stat">
            <span>推荐入口</span>
            <strong>先文档 后购买</strong>
        </div>
    </div>
</section>
<div class="row">
    <div class="col-12">
        <div class="page-title-box">
            <div class="page-title-right">
                <div class="app-search">
                    <div class="position-relative">
                        <input type="text" class="form-control" id="search" placeholder="{{ __('hyper.home_search_box') }}">
                        <span class="uil-search"></span>
                    </div>
                </div>
            </div>
            <h4 class="page-title">
                <button type="button" class="btn btn-outline-primary ml-1" id="notice-open">
                    <i class="uil-comment-alt-notes me-1"></i>
                    {{-- 公告 --}}
                    {{ __('hyper.notice_announcement') }}
                </button>
            </h4>
        </div>
    </div>
</div>
<div class="nav nav-list">
    <a href="#group-all" class="tab-link active" data-bs-toggle="tab" aria-expanded="false" role="tab" data-toggle="tab">
        <span class="tab-title">
        {{-- 全部 --}}
        {{ __('hyper.home_whole') }}
        </span>
        <div class="img-checkmark">
            <img src="/assets/hyper/images/check.png">
        </div>
    </a>
    @foreach($data as  $index => $group)
    <a href="#group-{{ $group['id'] }}" class="tab-link" data-bs-toggle="tab" aria-expanded="false" role="tab" data-toggle="tab">
        <span class="tab-title">
            {{ $group['gp_name'] }}
        </span>
        <div class="img-checkmark">
            <img src="/assets/hyper/images/check.png">
        </div>
    </a>
    @endforeach
</div>
<div class="tab-content">
    <div class="tab-pane active" id="group-all">
        <div class="hyper-wrapper">
            @foreach($data as $group)
                @foreach($group['goods'] as $goods)
                    @include('hyper.components.goods-card', ['goods' => $goods, 'planInsight' => $planInsightsById->get($goods['id'])])
                @endforeach
            @endforeach
        </div>
    </div>
    @foreach($data as  $index => $group)
        <div class="tab-pane" id="group-{{ $group['id'] }}">
            <div class="hyper-wrapper">
                @foreach($group['goods'] as $goods)
                    @include('hyper.components.goods-card', ['goods' => $goods, 'planInsight' => $planInsightsById->get($goods['id'])])
                @endforeach
            </div>
        </div>
    @endforeach
</div>
<div class="modal fade" id="notice-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myCenterModalLabel">{{ __('hyper.notice_announcement') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                {!! dujiaoka_config_get('notice') !!}
            </div>
        </div>
    </div>
</div>
@stop 
@section('js')
<script>
    $('#notice-open').click(function() {
        $('#notice-modal').modal();
    });
    $("#search").on("input",function(e){
        var txt = $("#search").val();
        if($.trim(txt)!="") {
            $(".category").hide().filter(":contains('"+txt+"')").show();
        } else {
            $(".category").show();
        }
    });
    function sell_out_tip() {
        $.NotificationApp.send("{{ __('hyper.home_tip') }}","{{ __('hyper.home_sell_out_tip') }}","top-center","rgba(0,0,0,0.2)","info");
    }
</script>
@stop
