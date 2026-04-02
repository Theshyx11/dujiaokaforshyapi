<div class="header-navbar">
    <div class="container header-flex">
        <a href="/" class="topnav-logo shyapi-brand">
            <span class="shyapi-brand-mark">
                @include('common.shyapi-mark', ['class' => 'shyapi-brand-svg'])
            </span>
            <span class="shyapi-brand-copy">
                <span class="logo-title">{{ dujiaoka_config_get('text_logo', 'ShyAPI') }}</span>
                <span class="logo-subtitle">Shop</span>
            </span>
        </a>
        <div class="shyapi-nav-actions">
            <a class="btn btn-shyapi-ghost" href="https://code.shyapi.top" target="_blank" rel="noopener">
                <i class="uil uil-chart-growth search-icon"></i>
                API 控制台
            </a>
            @if(partner_enabled())
                <a class="btn btn-shyapi-ghost" href="{{ url(partner_auth() ? 'partner/dashboard' : 'partner/login') }}">
                    <i class="noti-icon uil-users-alt search-icon"></i>
                    合伙人中心
                </a>
            @endif
            <a class="btn btn-shyapi-primary" href="{{ url('order-search') }}">
                <i class="noti-icon uil-file-search-alt search-icon"></i>
                查询订单
            </a>
        </div>
    </div>
</div>
