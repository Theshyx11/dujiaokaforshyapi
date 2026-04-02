<?php

namespace App\Http\Controllers\Home;

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\BaseController;
use App\Models\Goods;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartnerController extends BaseController
{
    /**
     * @var \App\Service\PartnerService
     */
    private $partnerService;

    /**
     * @var \App\Service\PartnerWalletService
     */
    private $partnerWalletService;

    /**
     * @var \App\Service\GoodsService
     */
    private $goodsService;

    public function __construct()
    {
        $this->partnerService = app('Service\PartnerService');
        $this->partnerWalletService = app('Service\PartnerWalletService');
        $this->goodsService = app('Service\GoodsService');
    }

    public function index()
    {
        return redirect(url($this->partnerService->check() ? 'partner/dashboard' : 'partner/login'));
    }

    public function loginPage()
    {
        if (!partner_enabled()) {
            return $this->err('合伙人中心暂未开放');
        }

        if ($this->partnerService->check()) {
            return redirect(url('partner/dashboard'));
        }

        return view('partner.auth', [
            'mode' => 'login',
            'inviterCode' => '',
            'page_title' => '合伙人登录',
        ]);
    }

    public function login(Request $request)
    {
        if (!partner_enabled()) {
            return redirect(url('partner/login'))->with('partner_error', '合伙人中心暂未开放');
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return back()->withInput()->with('partner_error', $validator->errors()->first());
        }

        try {
            $this->partnerService->login($request->input('email'), $request->input('password'));
            return redirect(url('partner/dashboard'))->with('partner_success', '合伙人登录成功');
        } catch (RuleValidationException $exception) {
            return back()->withInput()->with('partner_error', $exception->getMessage());
        }
    }

    public function registerPage(Request $request)
    {
        if (!partner_enabled()) {
            return $this->err('合伙人中心暂未开放');
        }

        if ($this->partnerService->check()) {
            return redirect(url('partner/dashboard'));
        }

        $inviterCode = strtoupper(trim((string) $request->query('ref', request()->cookie(\App\Service\PartnerService::REFERRAL_COOKIE, ''))));

        return view('partner.auth', [
            'mode' => 'register',
            'inviterCode' => $inviterCode,
            'page_title' => '申请成为合伙人',
        ]);
    }

    public function register(Request $request)
    {
        if (!partner_enabled()) {
            return redirect(url('partner/login'))->with('partner_error', '合伙人中心暂未开放');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'inviter_code' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return back()->withInput()->with('partner_error', $validator->errors()->first());
        }

        $inviter = $this->partnerService->resolveByReferralCode($request->input('inviter_code'));

        try {
            $this->partnerService->register($request->all(), $inviter);
            return redirect(url('partner/dashboard'))->with('partner_success', '合伙人账号创建成功');
        } catch (RuleValidationException $exception) {
            return back()->withInput()->with('partner_error', $exception->getMessage());
        }
    }

    public function dashboard()
    {
        $partner = $this->partnerService->requireCurrent();

        $summary = $this->partnerWalletService->buildSummary($partner);
        $transactions = $this->partnerWalletService->recentTransactions($partner);
        $redemptions = $this->partnerWalletService->recentRedemptions($partner);
        $redeemableGoods = $this->goodsService->redeemableShyApiGoods();
        $directInvitees = $partner->invitees()->latest('id')->limit(10)->get();

        return view('partner.dashboard', [
            'partner' => $partner,
            'summary' => $summary,
            'transactions' => $transactions,
            'redemptions' => $redemptions,
            'redeemableGoods' => $redeemableGoods,
            'directInvitees' => $directInvitees,
            'buyerInviteLink' => $this->partnerService->buyerInviteLink($partner),
            'partnerInviteLink' => $this->partnerService->partnerInviteLink($partner),
            'page_title' => '合伙人中心',
        ]);
    }

    public function redeem(Request $request)
    {
        $partner = $this->partnerService->requireCurrent();

        $validator = Validator::make($request->all(), [
            'goods_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        if ($validator->fails()) {
            return back()->with('partner_error', $validator->errors()->first());
        }

        $goods = Goods::query()->find((int) $request->input('goods_id'));
        if (!$goods) {
            return back()->with('partner_error', '兑换商品不存在');
        }

        try {
            $redemption = $this->partnerWalletService->redeemGoods($partner, $goods, (int) $request->input('quantity'));
            return redirect(url('partner/dashboard'))
                ->with('partner_success', '兑换成功，兑换码已生成：' . $redemption->redemption_no);
        } catch (RuleValidationException $exception) {
            return back()->with('partner_error', $exception->getMessage());
        }
    }

    public function logout()
    {
        $this->partnerService->logout();

        return redirect(url('partner/login'))->with('partner_success', '已退出合伙人中心');
    }
}
