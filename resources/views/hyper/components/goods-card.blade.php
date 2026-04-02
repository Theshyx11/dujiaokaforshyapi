@php
    $isAvailable = (int) ($goods['in_stock'] ?? 0) > 0;
    $retailPrice = (float) ($goods['retail_price'] ?? 0);
    $actualPrice = (float) ($goods['actual_price'] ?? 0);
    $savingAmount = max(0, $retailPrice - $actualPrice);
    $deliveryLabel = ((int) ($goods['delivery_source'] ?? 0) === \App\Models\Goods::DELIVERY_SOURCE_SHYAPI)
        ? 'ShyAPI 自动发码'
        : '本地卡密发货';
    $summary = trim(strip_tags($goods['gd_description'] ?? ''));
    if ($summary === '') {
        $summary = trim(strip_tags($goods['description'] ?? ''));
    }
    $summary = \Illuminate\Support\Str::limit($summary, 56, '...');
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
        <div class="shyapi-card-tag-row">
            <span class="shyapi-card-tag">{{ $deliveryLabel }}</span>
            @if($savingAmount > 0)
                <span class="shyapi-card-tag shyapi-card-tag-strong">立省 ¥{{ number_format($savingAmount, 2) }}</span>
            @elseif($isAvailable)
                <span class="shyapi-card-tag">现货</span>
            @endif
        </div>
        <div class="shyapi-card-meta">
            <span>{{ $isAvailable ? '可立即发放' : '等待补货' }}</span>
            <span>库存 {{ max(0, (int) ($goods['in_stock'] ?? 0)) }}</span>
        </div>
        <div class="shyapi-card-copy">
            <p class="name">{{ $goods['gd_name'] }}</p>
            <p class="shyapi-card-description">{{ $summary }}</p>
            <div class="shyapi-card-price-row">
                <div class="price">
                    {{ __('hyper.global_currency') }}<b>{{ $goods['actual_price'] }}</b>
                </div>
                @if($retailPrice > $actualPrice)
                    <div class="shyapi-card-retail">原价 ¥{{ number_format($retailPrice, 2) }}</div>
                @endif
            </div>
        </div>
        <div class="shyapi-card-footer">
            <span class="shyapi-card-cta">{{ $isAvailable ? '立即购买' : '暂时售罄' }}</span>
            <span class="shyapi-card-cta-icon">{{ $isAvailable ? '→' : '·' }}</span>
        </div>
    </div>
</a>
