@extends('hyper.layouts.default')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="page-title-box">
            {{-- 确认订单 --}}
            <h4 class="page-title">{{ __('hyper.bill_title') }}</h4>
        </div>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card card-body">
            <div class="shyapi-flow-card mb-4">
                <span class="shyapi-flow-kicker">支付前确认</span>
                <h5>确认订单后再继续付款</h5>
                <p>
                    完成支付后系统会发放兑换码。随后前往 <strong>code.shyapi.top</strong> 的充值页输入兑换码，额度会直接进入你的 ShyAPI 账户。
                </p>
                <div class="shyapi-inline-actions">
                    <a class="btn btn-shyapi-ghost btn-sm" href="https://code.shyapi.top/console/topup" target="_blank" rel="noopener">充值页</a>
                    <a class="btn btn-shyapi-ghost btn-sm" href="{{ url('order-search') }}">查询订单</a>
                </div>
            </div>
        	<div class="mx-auto">
        	    {{-- 订单编号 --}}
                <div class="mb-1"><label>{{ __('hyper.bill_order_number') }}：</label><span>{{ $order_sn }}</span></div>
                {{-- 商品名称 --}}
                <div class="mb-1"><label>{{ __('hyper.bill_product_name') }}：</label><span>{{ $title }}</span></div>
                {{-- 商品单价 --}}
                <div class="mb-1"><label>{{ __('hyper.bill_commodity_price') }}：</label><span>{{ $goods_price }}</span></div>
                {{-- 购买数量 --}}
                <div class="mb-1"><label>{{ __('hyper.bill_purchase_quantity') }}：</label><span>x {{ $buy_amount }}</span></div>
                @if(!empty($coupon))
                {{-- 优惠码 --}}
                <div class="mb-1"><label>{{ __('hyper.bill_promo_code') }}：</label><span>{{ $coupon['coupon'] }}</span></div>
                {{-- 优惠金额 --}}
                <div class="mb-1"><label>{{ __('hyper.bill_discounted_price') }}：</label><span>{{ $coupon_discount_price }}</span></div>
                @endif
                {{-- 商品总价 --}}
                <div class="mb-1"><label>{{ __('hyper.bill_actual_payment') }}：</label><span>{{ $actual_price }}</span></div>
                {{-- 电子邮箱 --}}
                <div class="mb-1"><label>{{ __('hyper.bill_email') }}：</label><span>{{ $email }}</span></div>
                @if(!empty($info))
                {{-- 订单资料 --}}
                <div class="mb-1"><label>{{ __('hyper.bill_order_information') }}：</label><span>{{ $info }}</span></div>
                @endif
                {{-- 支付方式 --}}
                <div class="mb-1"><label>{{ __('hyper.bill_payment_method') }}：</label><span>{{ $pay['pay_name'] }}</span></div>
            </div>
            <div class="text-center shyapi-inline-actions justify-content-center">
                {{-- 立即支付 --}}
            	<a href="{{ url('pay-gateway', ['payway' => $pay['pay_check'], 'orderSN' => $order_sn]) }}"
                   class="btn btn-shyapi-primary">
                    {{ __('hyper.bill_pay_immediately') }}
                </a>
                <a href="{{ url('order-search') }}" class="btn btn-shyapi-ghost">
                    查询订单
                </a>
            </div>
        </div>
    </div>
</div>
@stop
@section('js')
@stop
