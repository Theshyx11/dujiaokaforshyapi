<?php

namespace App\Http\Middleware;

use Closure;

class RequirePartnerAuth
{
    public function handle($request, Closure $next)
    {
        if (!partner_enabled()) {
            return redirect(url('partner/login'))->with('partner_error', '合伙人中心暂未开放');
        }

        if (!app('Service\PartnerService')->check()) {
            return redirect(url('partner/login'))->with('partner_error', '请先登录合伙人中心');
        }

        return $next($request);
    }
}
