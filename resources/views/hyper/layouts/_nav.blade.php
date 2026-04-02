<div class="header-navbar">
    <div class="container header-flex">
        <!-- LOGO -->
        <a href="/" class="topnav-logo" style="float: none;">
            <img src="{{ picture_ulr(dujiaoka_config_get('img_logo')) }}" height="36">
            <div class="logo-title">{{ dujiaoka_config_get('text_logo') }}</div>
        </a>
        <div style="display:flex; gap:10px; align-items:center;">
            @if(partner_enabled())
                <a class="btn btn-outline-warning" href="{{ url(partner_auth() ? 'partner/dashboard' : 'partner/login') }}">
                    <i class="noti-icon uil-users-alt search-icon"></i>
                    合伙人中心
                </a>
            @endif
            <a class="btn btn-outline-primary" href="{{ url('order-search') }}">
                <i class="noti-icon uil-file-search-alt search-icon"></i>
                查询订单
            </a>
        </div>
    </div>
</div>
