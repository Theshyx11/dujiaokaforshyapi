@extends('hyper.layouts.default')
@section('content')
@php
    $goodsCollection = collect($data)->flatMap(function ($group) {
        return collect($group['goods'] ?? []);
    });
    $goodsCount = $goodsCollection->count();
    $inStockGoodsCount = $goodsCollection->filter(function ($goods) {
        return (int) ($goods['in_stock'] ?? 0) > 0;
    })->count();
    $fromPrice = $goodsCount > 0 ? number_format((float) $goodsCollection->min('actual_price'), 2) : '0.00';
@endphp
<section class="shyapi-shop-hero">
    <div class="shyapi-shop-hero-grid">
        <div class="shyapi-shop-hero-copy">
            <span class="shyapi-hero-kicker">ShyAPI Shop</span>
            <h1 class="shyapi-hero-title">黑金风格的自动发卡商城</h1>
            <p class="shyapi-hero-description">
                这里负责销售兑换码套餐。付款成功后会自动发放兑换码，你可以直接前往 <strong>code.shyapi.top</strong> 充值使用。
                如果你要做分销推广，也可以进入合伙人中心生成专属邀请链接，把佣金直接兑换成站内卡券。
            </p>
            <div class="shyapi-hero-actions">
                <a class="btn btn-shyapi-primary" href="#group-all">浏览当前套餐</a>
                <a class="btn btn-shyapi-ghost" href="https://code.shyapi.top" target="_blank" rel="noopener">前往 API 控制台</a>
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
            <div class="shyapi-hero-steps">
                <div class="shyapi-hero-step"><span>01</span><p>下单并完成支付</p></div>
                <div class="shyapi-hero-step"><span>02</span><p>系统自动发放兑换码</p></div>
                <div class="shyapi-hero-step"><span>03</span><p>前往控制台充值并开始使用</p></div>
            </div>
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
                    @include('hyper.components.goods-card', ['goods' => $goods])
                @endforeach
            @endforeach
        </div>
    </div>
    @foreach($data as  $index => $group)
        <div class="tab-pane" id="group-{{ $group['id'] }}">
            <div class="hyper-wrapper">
                @foreach($group['goods'] as $goods)
                    @include('hyper.components.goods-card', ['goods' => $goods])
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
