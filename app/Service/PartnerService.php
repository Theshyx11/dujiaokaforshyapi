<?php

namespace App\Service;

use App\Exceptions\RuleValidationException;
use App\Models\Partner;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PartnerService
{
    const SESSION_KEY = 'shyapi_partner_id';
    const REFERRAL_COOKIE = 'shyapi_partner_ref';
    const REFERRAL_COOKIE_MINUTES = 43200;

    public function current(): ?Partner
    {
        $partnerId = (int) session(self::SESSION_KEY, 0);
        if ($partnerId <= 0) {
            return null;
        }

        $partner = Partner::query()->find($partnerId);
        if (!$partner || (int) $partner->status !== Partner::STATUS_ENABLED) {
            $this->logout();
            return null;
        }

        return $partner;
    }

    public function requireCurrent(): Partner
    {
        $partner = $this->current();
        if (!$partner) {
            throw new RuleValidationException('请先登录合伙人中心');
        }

        return $partner;
    }

    public function check(): bool
    {
        return $this->current() !== null;
    }

    public function login(string $email, string $password): Partner
    {
        $partner = Partner::query()
            ->where('email', strtolower(trim($email)))
            ->first();

        if (!$partner || !Hash::check($password, (string) $partner->password)) {
            throw new RuleValidationException('邮箱或密码错误');
        }

        if ((int) $partner->status !== Partner::STATUS_ENABLED) {
            throw new RuleValidationException('当前合伙人账号已停用');
        }

        $this->loginPartner($partner);

        return $partner;
    }

    public function register(array $data, ?Partner $inviter = null): Partner
    {
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $name = trim((string) ($data['name'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $name === '' || $password === '') {
            throw new RuleValidationException('请完整填写合伙人注册信息');
        }
        if (strlen($password) < 8) {
            throw new RuleValidationException('合伙人密码至少 8 位');
        }
        if (Partner::query()->where('email', $email)->exists()) {
            throw new RuleValidationException('该邮箱已注册合伙人账号');
        }
        if ($inviter && $inviter->email === $email) {
            throw new RuleValidationException('不能绑定自己作为邀请人');
        }

        $partner = Partner::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'referral_code' => $this->generateReferralCode(),
            'inviter_id' => $inviter ? $inviter->id : null,
            'status' => Partner::STATUS_ENABLED,
            'last_login_at' => now(),
        ]);

        $this->loginPartner($partner);

        return $partner;
    }

    public function loginPartner(Partner $partner): void
    {
        session()->put(self::SESSION_KEY, $partner->id);
        session()->regenerate();

        $partner->last_login_at = now();
        $partner->save();
    }

    public function logout(): void
    {
        session()->forget(self::SESSION_KEY);
        session()->regenerate();
        session()->regenerateToken();
    }

    public function resolveByReferralCode(?string $referralCode): ?Partner
    {
        $referralCode = strtoupper(trim((string) $referralCode));
        if ($referralCode === '') {
            return null;
        }

        return Partner::query()
            ->where('referral_code', $referralCode)
            ->where('status', Partner::STATUS_ENABLED)
            ->first();
    }

    public function rememberReferral(Partner $partner): void
    {
        Cookie::queue(self::REFERRAL_COOKIE, $partner->referral_code, self::REFERRAL_COOKIE_MINUTES);
    }

    public function currentReferralPartner(): ?Partner
    {
        return $this->resolveByReferralCode(Cookie::get(self::REFERRAL_COOKIE));
    }

    public function resolveAttributionPartner(string $buyerEmail): ?Partner
    {
        if (!partner_enabled()) {
            return null;
        }

        $referralPartner = $this->currentReferralPartner();
        if (!$referralPartner) {
            return null;
        }

        $buyerEmail = strtolower(trim($buyerEmail));
        if ($buyerEmail !== '' && $buyerEmail === strtolower((string) $referralPartner->email)) {
            return null;
        }

        $currentPartner = $this->current();
        if ($currentPartner && $currentPartner->id === $referralPartner->id) {
            return null;
        }

        return $referralPartner;
    }

    public function buyerInviteLink(Partner $partner): string
    {
        return rtrim(url('/'), '/') . '/?ref=' . $partner->referral_code;
    }

    public function partnerInviteLink(Partner $partner): string
    {
        return url('partner/register') . '?ref=' . $partner->referral_code;
    }

    private function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Partner::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
