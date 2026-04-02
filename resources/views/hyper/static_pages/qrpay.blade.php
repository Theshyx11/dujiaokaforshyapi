@extends('hyper.layouts.default')
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="page-title-box">
            {{-- 扫码支付 --}}
            <h4 class="page-title">{{ __('hyper.qrpay_title') }}</h4>
        </div>
    </div>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="shyapi-flow-card mb-3">
            <span class="shyapi-flow-kicker">扫码支付中</span>
            <h5>支付成功后页面会自动跳转</h5>
            <p>
                当前订单号为 <strong>{{ $orderid }}</strong>。支付成功后系统会自动跳到订单详情页，你可以直接复制兑换码并前往 <strong>code.shyapi.top</strong> 充值。
            </p>
            <div class="shyapi-inline-actions">
                <a class="btn btn-shyapi-ghost btn-sm" href="https://code.shyapi.top/console/topup" target="_blank" rel="noopener">打开充值页</a>
                <a class="btn btn-shyapi-ghost btn-sm" href="{{ url('order-search') }}">查询订单</a>
            </div>
        </div>
        <div class="card border-primary border">
            <div class="card-body">
                <h5 class="card-title text-primary text-center">{{ __('hyper.qrpay_order_expiration_date') }} {{ dujiaoka_config_get('order_expire_time', 5) }} {{ __('hyper.qrpay_expiration_date') }}</h5>
                <div class="text-center">
                    <img src="data:image/png;base64,{!! base64_encode(QrCode::format('png')->size(200)->generate($qr_code)) !!}">
                </div>
                {{-- 订单金额 --}}
                <p class="card-text text-center">{{ __('hyper.qrpay_actual_payment') }}: {{ $actual_price }}</p>
                <div class="shyapi-step-list">
                    <div><span>01</span><p>使用当前支付方式完成付款</p></div>
                    <div><span>02</span><p>系统每 5 秒检测一次订单状态</p></div>
                    <div><span>03</span><p>支付成功后跳转详情页复制兑换码</p></div>
                </div>
                @if(Agent::isMobile() && isset($jump_payuri))
                    <p class="errpanl" style="text-align: center"><a href="{{ $jump_payuri }}" class="">{{ __('hyper.qrpay_open_app_to_pay') }}</a></p>
                @endif
            </div> <!-- end card-body-->
        </div>
    </div>
</div>
@stop
@section('js')
    <script>
        var getting = {
            url:'{{ url('check-order-status', ['orderSN' => $orderid]) }}',
            dataType:'json',
            success:function(res) {
                if (res.code == 400001) {
                    window.clearTimeout(timer);
                    $.NotificationApp.send("{{ __('hyper.qrpay_notice') }}","{{ __('hyper.order_pay_timeout') }}","top-center","rgba(0,0,0,0.2)","warning");
                    setTimeout("window.location.href ='/'",3000);
                }
                if (res.code == 200) {
                    window.clearTimeout(timer);
                    $.NotificationApp.send("{{ __('hyper.qrpay_notice') }}","{{ __('hyper.payment_successful') }}","top-center","rgba(0,0,0,0.2)","success");
                    setTimeout("window.location.href ='{{ url('detail-order-sn', ['orderSN' => $orderid]) }}'",3000);
                }
            }
        };
        var timer = window.setInterval(function(){$.ajax(getting)},5000);
    </script>
@stop
