<?php

namespace App\Providers;

use App\Service\CarmisService;
use App\Service\CouponService;
use App\Service\EmailtplService;
use App\Service\GoodsService;
use App\Service\OrderProcessService;
use App\Service\OrderService;
use App\Service\PartnerService;
use App\Service\PartnerWalletService;
use App\Service\PayService;
use App\Service\ShyApiRedemptionService;
use App\Service\ZPayService;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\Agent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Service\GoodsService', function () {
            return $this->app->make(GoodsService::class);
        });
        $this->app->singleton('Service\PayService', function () {
            return $this->app->make(PayService::class);
        });
        $this->app->singleton('Service\CarmisService', function () {
            return $this->app->make(CarmisService::class);
        });
        $this->app->singleton('Service\OrderService', function () {
            return $this->app->make(OrderService::class);
        });
        $this->app->singleton('Service\CouponService', function () {
            return $this->app->make(CouponService::class);
        });
        $this->app->singleton('Service\OrderProcessService', function () {
            return $this->app->make(OrderProcessService::class);
        });
        $this->app->singleton('Service\PartnerService', function () {
            return $this->app->make(PartnerService::class);
        });
        $this->app->singleton('Service\PartnerWalletService', function () {
            return $this->app->make(PartnerWalletService::class);
        });
        $this->app->singleton('Service\EmailtplService', function () {
            return $this->app->make(EmailtplService::class);
        });
        $this->app->singleton('Service\ShyApiRedemptionService', function () {
            return $this->app->make(ShyApiRedemptionService::class);
        });
        $this->app->singleton('Service\ZPayService', function () {
            return $this->app->make(ZPayService::class);
        });
        $this->app->singleton('Jenssegers\Agent', function () {
            return $this->app->make(Agent::class);
        });

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
