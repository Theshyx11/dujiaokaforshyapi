<?php

namespace App\Http\Middleware;

use Closure;

class TrackPartnerReferral
{
    public function handle($request, Closure $next)
    {
        if (partner_enabled()) {
            $referralCode = $request->query('ref', '');
            if ($referralCode !== '') {
                $partner = app('Service\PartnerService')->resolveByReferralCode($referralCode);
                if ($partner) {
                    app('Service\PartnerService')->rememberReferral($partner);
                }
            }
        }

        return $next($request);
    }
}
