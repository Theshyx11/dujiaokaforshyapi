@php
    $isAvailable = (int) ($goods['in_stock'] ?? 0) > 0;
    $deliveryLabel = ((int) ($goods['delivery_source'] ?? 0) === \App\Models\Goods::DELIVERY_SOURCE_SHYAPI)
        ? 'ShyAPI 自动发码'
        : '本地卡密发货';
@endphp
@if($isAvailable)
    <a href="{{ url("/buy/{$goods['id']}") }}" class="home-card category">
@else
    <a href="javascript:void(0);" onclick="sell_out_tip()" class="home-card category ribbon-box">
        <div class="ribbon-two ribbon-two-danger">
            <span>{{ __('hyper.home_out_of_stock') }}</span>
        </div>
@endif
    <img class="home-img" src="/assets/hyper/images/loading.gif" data-src="{{ picture_ulr($goods['picture']) }}" alt="{{ $goods['gd_name'] }}">
    <div class="shyapi-card-body">
        <div class="shyapi-card-meta">
            <span>{{ $deliveryLabel }}</span>
            <span>库存 {{ max(0, (int) ($goods['in_stock'] ?? 0)) }}</span>
        </div>
        <div class="flex">
            <p class="name">{{ $goods['gd_name'] }}</p>
            <div class="price">
                {{ __('hyper.global_currency') }}<b>{{ $goods['actual_price'] }}</b>
            </div>
        </div>
    </div>
</a>
