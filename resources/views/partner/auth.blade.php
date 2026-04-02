@extends('partner.layouts.app')

@section('content')
<div class="partner-auth">
    @if($mode === 'login')
        <h1>合伙人登录</h1>
        <p>登录后可以查看邀请链接、订单贡献、佣金台账，并直接把佣金兑换成 ShyAPI 卡券。</p>
        <form method="post" action="{{ url('partner/login') }}">
            {{ csrf_field() }}
            <div class="partner-auth-row">
                <label>邮箱</label>
                <input class="partner-form-control" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="partner-auth-row">
                <label>密码</label>
                <input class="partner-form-control" type="password" name="password" required>
            </div>
            <button class="partner-btn partner-auth-submit" type="submit">登录合伙人中心</button>
        </form>
        <div class="partner-auth-switch">
            还没有合伙人账号？<a href="{{ url('partner/register') }}">立即申请</a>
        </div>
    @else
        <h1>申请成为合伙人</h1>
        <p>注册成功后会自动生成你的专属推广链接。下游订单完成后，佣金将进入你的合伙人台账，并可直接兑换成 ShyAPI 卡券。</p>
        <form method="post" action="{{ url('partner/register') }}">
            {{ csrf_field() }}
            <div class="partner-auth-row">
                <label>显示名称</label>
                <input class="partner-form-control" type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="partner-auth-row">
                <label>邮箱</label>
                <input class="partner-form-control" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="partner-auth-row">
                <label>密码</label>
                <input class="partner-form-control" type="password" name="password" required>
            </div>
            <div class="partner-auth-row">
                <label>确认密码</label>
                <input class="partner-form-control" type="password" name="password_confirmation" required>
            </div>
            <div class="partner-auth-row">
                <label>邀请人代码</label>
                <input class="partner-form-control" type="text" name="inviter_code" value="{{ old('inviter_code', $inviterCode) }}">
            </div>
            <button class="partner-btn partner-auth-submit" type="submit">创建合伙人账号</button>
        </form>
        <div class="partner-auth-switch">
            已经有账号？<a href="{{ url('partner/login') }}">去登录</a>
        </div>
    @endif
</div>
@endsection
