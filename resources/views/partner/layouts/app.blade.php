@php($template = dujiaoka_config_get('template', 'hyper'))
<!DOCTYPE html>
<html lang="{{ str_replace('_','-',strtolower(app()->getLocale())) }}">
@include($template . '.layouts._header')
<body class="@if($template === 'hyper') shyapi-hyper @endif shyapi-partner-page" @if($template === 'hyper') data-layout="topnav" @endif>
@if($template === 'hyper')
<div class="wrapper">
    <div class="content-page">
        <div class="content">
@endif
@include($template . '.layouts._nav')
<style>
    .partner-page { padding: 28px 16px 56px; background: radial-gradient(circle at top, rgba(212, 166, 74, 0.12), transparent 38%), #050505; min-height: calc(100vh - 120px); }
    .partner-shell { max-width: 1180px; margin: 0 auto; color: #f5e7bf; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .partner-hero { display: flex; justify-content: space-between; gap: 20px; align-items: flex-start; margin-bottom: 24px; }
    .partner-hero-title { font-size: 34px; font-weight: 700; color: #f5d27a; margin: 0 0 8px; }
    .partner-hero-subtitle { color: rgba(245, 231, 191, 0.72); margin: 0; max-width: 720px; line-height: 1.7; }
    .partner-logout { background: transparent; color: #f5d27a; border: 1px solid rgba(245, 210, 122, 0.45); border-radius: 999px; padding: 10px 16px; cursor: pointer; }
    .partner-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
    .partner-card, .partner-panel { background: rgba(14, 14, 14, 0.92); border: 1px solid rgba(245, 210, 122, 0.18); border-radius: 18px; box-shadow: 0 18px 40px rgba(0, 0, 0, 0.26); }
    .partner-card { padding: 18px; }
    .partner-card-label { font-size: 13px; color: rgba(245, 231, 191, 0.66); margin-bottom: 10px; }
    .partner-card-value { font-size: 28px; font-weight: 700; color: #fff5cf; }
    .partner-card-note { margin-top: 10px; font-size: 12px; color: rgba(245, 231, 191, 0.5); }
    .partner-panels { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px; margin-bottom: 16px; }
    .partner-panel { padding: 20px; }
    .partner-panel-title { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; }
    .partner-panel-title h3 { margin: 0; color: #f6d98c; font-size: 18px; }
    .partner-hint { color: rgba(245, 231, 191, 0.64); font-size: 13px; }
    .partner-linkbox { display: flex; gap: 10px; margin-bottom: 12px; }
    .partner-linkbox input, .partner-form-control, .partner-table textarea { flex: 1; width: 100%; background: #121212; border: 1px solid rgba(245, 210, 122, 0.2); color: #fff2ca; border-radius: 12px; padding: 12px 14px; }
    .partner-linkbox button, .partner-btn { background: linear-gradient(135deg, #f0c15b, #be8b25); border: none; color: #201504; border-radius: 12px; padding: 12px 16px; font-weight: 700; cursor: pointer; }
    .partner-form-grid { display: grid; grid-template-columns: 1fr 140px 120px; gap: 10px; margin-top: 14px; }
    .partner-table { width: 100%; border-collapse: collapse; }
    .partner-table th, .partner-table td { border-bottom: 1px solid rgba(245, 210, 122, 0.1); padding: 12px 8px; text-align: left; vertical-align: top; color: #f8efcf; font-size: 14px; }
    .partner-table th { color: rgba(245, 231, 191, 0.62); font-weight: 600; }
    .partner-tag { display: inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 12px; }
    .partner-tag-positive { background: rgba(92, 201, 140, 0.14); color: #7be8a7; }
    .partner-tag-negative { background: rgba(255, 121, 121, 0.14); color: #ff9d9d; }
    .partner-tag-muted { background: rgba(245, 210, 122, 0.12); color: #f5d27a; }
    .partner-alert { padding: 14px 16px; border-radius: 14px; margin-bottom: 16px; }
    .partner-alert-success { background: rgba(92, 201, 140, 0.12); border: 1px solid rgba(92, 201, 140, 0.24); color: #96efb8; }
    .partner-alert-error { background: rgba(235, 87, 87, 0.12); border: 1px solid rgba(235, 87, 87, 0.24); color: #ffadad; }
    .partner-auth { max-width: 540px; margin: 48px auto 0; padding: 24px; background: rgba(14, 14, 14, 0.94); border: 1px solid rgba(245, 210, 122, 0.18); border-radius: 24px; }
    .partner-auth h1 { margin: 0 0 10px; color: #f8d47d; }
    .partner-auth p { color: rgba(245, 231, 191, 0.72); margin-bottom: 24px; line-height: 1.7; }
    .partner-auth-row { margin-bottom: 14px; }
    .partner-auth-row label { display: block; margin-bottom: 8px; color: #f5dba1; font-size: 14px; }
    .partner-auth-submit { width: 100%; margin-top: 8px; }
    .partner-auth-switch { margin-top: 16px; color: rgba(245, 231, 191, 0.7); }
    .partner-auth-switch a { color: #f5d27a; }
    .partner-empty { color: rgba(245, 231, 191, 0.6); padding: 16px 0; }
    @media (max-width: 960px) {
        .partner-grid, .partner-panels { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 720px) {
        .partner-page { padding: 18px 12px 40px; }
        .partner-hero, .partner-grid, .partner-panels, .partner-form-grid { grid-template-columns: 1fr; display: grid; }
        .partner-linkbox { flex-direction: column; }
        .partner-card-value { font-size: 24px; }
    }
</style>
<div class="partner-page">
    <div class="partner-shell">
        @if(session('partner_success'))
            <div class="partner-alert partner-alert-success">{{ session('partner_success') }}</div>
        @endif
        @if(session('partner_error'))
            <div class="partner-alert partner-alert-error">{{ session('partner_error') }}</div>
        @endif
        @yield('content')
    </div>
</div>
@if($template === 'hyper')
        </div>
        @include($template . '.layouts._footer')
    </div>
</div>
@else
    @include($template . '.layouts._footer')
@endif
@include($template . '.layouts._script')
@yield('js')
</body>
</html>
