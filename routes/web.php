<?php

/*
 * sc.250.cn h5域名访问接口
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/





Route::get('/', function () {


});


Route::get('/ok', 'RedirectController@redirectHorizon');
Route::get('/redirect/horizon', 'RedirectController@redirectHorizon');
Route::get('/redirect/logs', 'RedirectController@redirectLogView');

//全国地址库爬虫
Route::namespace('Util')->group(function (){
    Route::get('util/spider/address/street','SpiderAddressController@street');
    Route::get('util/spider/address/city','SpiderAddressController@city');
    Route::get('util/spider/address/village','SpiderAddressController@village');

});

//cms内容管理
Route::namespace('Content')->group(function () {
    Route::get('content/article','ArticleController@index');
    Route::get('content/article/detail','ArticleController@detail');
});

//第三方支付与登录回调
Route::namespace('Callback')->group(function () {
    Route::post('callback/alipay/notify','AlipayController@notify');
    Route::post('callback/alipay/return','AlipayController@return');
    Route::get('callback/alipay/login','AlipayController@login');

    Route::post('callback/weixin/notify','WeixinController@notify');
    Route::post('callback/weixin/refund','WeixinController@refundNotify');

    Route::post('callback/im/notify','IMController@notify');
    Route::post('callback/phone/notify.record','PhoneProtectController@recordNotify');
    Route::post('callback/phone/notify.status','PhoneProtectController@statusNotify');
    Route::post('callback/phone/notify.hangup','PhoneProtectController@hangupNotify');
});

//微信开者者验证
Route::namespace('Weixin')->group(function () {
    Route::get('weixin/index/token','IndexController@token');
    Route::get('weixin/index','IndexController@index');
});
