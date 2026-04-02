<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'partner',
    'middleware' => ['dujiaoka.boot', 'partner.referral'],
    'namespace' => 'Home',
], function () {
    Route::get('/', 'PartnerController@index');
    Route::get('login', 'PartnerController@loginPage');
    Route::post('login', 'PartnerController@login');
    Route::get('register', 'PartnerController@registerPage');
    Route::post('register', 'PartnerController@register');

    Route::group(['middleware' => ['partner.auth']], function () {
        Route::get('dashboard', 'PartnerController@dashboard');
        Route::post('redeem', 'PartnerController@redeem');
        Route::post('logout', 'PartnerController@logout');
    });
});
