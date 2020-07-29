<?php

use Illuminate\Http\Request;

/*
 *
 * sc-api.250.cn app应用调用业务接口
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get("/",function (){
    echo '403 forbidden';
});

Route::namespace('User')->group(function (){
    //账号相关
    Route::post('user/verify/sendCode','UserController@sendCode');
    Route::post('user/account/register','UserController@register');
    Route::post('user/account/login','UserController@loginWithPassword');
    Route::post('user/account/loginOut','UserController@loginOut');
    Route::get('user/account/get','UserController@get');
    Route::post('user/account/resetPassword','UserController@resetPassword');
    Route::post('user/account/login.code','UserController@loginWithPhoneCode');
    Route::post('user/account/login.quick','UserController@quickRegisterAndLogin');
    Route::post('user/account/login.thirdParty','UserController@thirdPartyAppLogin');
    Route::post('user/account/update.base','UserController@updateUserBaseInfo');
    Route::post('user/account/update.phone','UserController@updateUserPhone');
    Route::post('user/account/update.accept','UserController@updateUserAccept');

    //地址相关
    Route::post('user/address/add','AddressController@add');
    Route::put('user/address/update','AddressController@update');
    Route::put('user/address/update.default','AddressController@setDefault');
    Route::delete('user/address/delete','AddressController@remove');
    Route::get('user/address/all','AddressController@findAll');
    Route::get('user/address/get','AddressController@find');

    //技能相关
    Route::post('user/certificate/add','CertificateController@add');
    Route::post('user/certificate/update','CertificateController@update');
    Route::get('user/certificate/all','CertificateController@findAll');
    Route::get('user/certificate/get','CertificateController@find');
    Route::delete('user/certificate/delete','CertificateController@remove');
    Route::get('user/certificate/type.list','CertificateController@typeList');

    //银行卡相关
    Route::post('user/bank/add','BankController@add');
    Route::get('user/bank/all','BankController@findAll');
    Route::get('user/bank/get','BankController@find');
    Route::delete('user/bank/delete','BankController@remove');

    //用户反馈
    Route::post('user/feedback/add','FeedbackController@add');
    Route::get('user/feedback/all','FeedbackController@findAll');
    Route::get('user/feedback/get','FeedbackController@find');
    Route::delete('user/feedback/delete','FeedbackController@remove');
    Route::get('user/feedback/type.list','FeedbackController@typeList');

    // 活体人脸核身
    Route::get('user/certification/ocr.init','CertificationController@ocrInit');
    Route::get('user/certification/ocr.result','CertificationController@getOcrResult');
    Route::post('user/certification/saveInfo','CertificationController@saveInfo');
    Route::get('user/certification/verify.init','CertificationController@verifyInit');
    Route::get('user/certification/verify.result','CertificationController@getVerifyResult');
    Route::get('user/certification/info','CertificationController@getInfo');

    //支付相关
    Route::post('user/account/resetPayPassword','UserController@resetPayPassword');

    //标签相关
    Route::get('user/label/list.available','LabelController@getLabels');
    Route::get('user/label/list.hot','LabelController@getUserHotLabels');

    //黑名单相关
    Route::get('user/black/add','BlackController@add');
    Route::get('user/black/get','BlackController@get');
    Route::delete('user/black/remove','BlackController@remove');

    Route::get('user/alipay/grant','AlipayController@grant');

});


Route::namespace('Pool')->group(function (){
    //基础地址库管理
    Route::get('pool/address/provinces','AddressController@provinces');
    Route::get('pool/address/associateNextLevel','AddressController@associateNextLevel');
    Route::get('pool/address/allParent','AddressController@allParent');
    Route::get('pool/address/altitude','AddressController@calcAltitude');
    Route::get('pool/address/get.code','AddressController@getAreaByCode');
    Route::get('pool/address/get.name','AddressController@getByName');

    // 文章
    Route::get('pool/content/articles','ArticleController@getList');
    Route::get('pool/content/article/detail','ArticleController@getDetail');

    //全局配置
    Route::get('pool/config/get','ConfigController@get');
    Route::get('pool/config/gets','ConfigController@gets');

    Route::get('pool/index/map','IndexController@map');

});


Route::namespace('Common')->group(function (){
    //文件通用上传
    Route::post('common/upload/index','UploadController@index');
    // 第三方平台上传签名
    Route::get('common/upload/signature', 'UploadController@signature');

    //app调用的全局配置
    Route::get('common/config/index','ConfigController@index');

    // 录音文件转码及分片
    Route::post('common/record/transform','RecordController@transform');

    Route::get('common/upgrade/index','UpgradeController@index');
});

//用户发单与接单相关
Route::namespace('Trade')->group(function (){
    //雇主相关
    Route::post('trade/employer/order/add','Employer\OrderController@add');
    Route::post('trade/employer/order/update','Employer\OrderController@update');
    Route::post('trade/employer/order/confirm','Employer\OrderController@confirm');
    Route::post('trade/employer/order/complete','Employer\OrderController@complete');
    Route::post('trade/employer/order/cancel','Employer\OrderController@cancel');
    Route::post('trade/employer/order/delivery.refuse','Employer\OrderController@refuseDelivery');
    Route::post('trade/employer/order/comment','Employer\OrderController@comment');
    Route::get('trade/employer/order/list','Employer\OrderController@list');
    Route::get('trade/employer/order/compensate.cancel','Employer\OrderController@checkCancelCompensate');
    Route::post('trade/employer/order/defer.agree','Employer\OrderController@agreeDefer');

    //帮手相关
    Route::post('trade/helper/order/receive','Helper\OrderController@receive');
    Route::get('trade/helper/order/receive.checkFace','Helper\OrderController@receiveCheckFace');
    Route::post('trade/helper/order/delivery','Helper\OrderController@delivery');
    Route::post('trade/helper/order/cancel','Helper\OrderController@cancel');
    Route::post('trade/helper/order/cancel.quoted','Helper\OrderController@cancelQuoted');
    Route::post('trade/helper/order/position','Helper\OrderController@position');
    Route::post('trade/helper/order/comment','Helper\OrderController@comment');
    Route::get('trade/helper/order/list','Helper\OrderController@list');
    Route::get('trade/helper/order/fee','Helper\OrderController@getFee');
    Route::get('trade/helper/order/fee.price','Helper\OrderController@getPriceFee');
    Route::get('trade/helper/order/compensate.overtime','Helper\OrderController@checkOverTimeCompensate');
    Route::get('trade/helper/order/compensate.cancel','Helper\OrderController@checkCancelCompensate');
    Route::post('trade/helper/order/defer','Helper\OrderController@defer');

    //任务相关
    Route::post('trade/pay/pay/confirm','Pay\PayController@pay');
    Route::get('trade/task/detail','Order\IndexController@detail');
    Route::get('trade/task/quoted.list','Order\IndexController@getOrderQuotedList');
    Route::get('trade/task/receiver','Order\IndexController@getOrderReceiver');
    Route::get('trade/task/category','Order\IndexController@getCategoryList');
    //app发单的配置项
    Route::get('trade/task/config','Order\IndexController@config');
    Route::get('trade/task/search.options','Order\SearchController@options');
    Route::post('trade/task/search','Order\SearchController@search');

    //账户与收支相关
    Route::get('trade/income/list.options','Income\IndexController@options');
    Route::get('trade/income/list','Income\IndexController@list');
    Route::get('trade/income/account','Income\IndexController@getAccount');

    //提现相关
    Route::post('trade/withdraw/apply','Income\WithDrawController@apply');
    Route::get('trade/withdraw/list','Income\WithDrawController@list');
    Route::get('trade/withdraw/grant.list','Income\WithDrawController@grantList');

    //首页地图相关
    Route::post('trade/task/nearby.task','Order\SearchController@nearByTask');
    Route::post('trade/task/nearby.helper','Order\SearchController@nearByHelper');
});

//消息库
Route::namespace('Message')->group(function (){
    //im聊天
    Route::get('message/im/getLoginParams','IMController@getLoginParams');
    Route::get('message/im/customerService','IMController@customerService');
    Route::get('message/im/getUserOnlineState','IMController@getUserOnlineState');
    Route::get('message/im/getCustomerService','IMController@getCustomerService');

    Route::get('message/unread','MessageController@getUnreadNum');
    Route::get('message/list','MessageController@getList');
    Route::get('message/detail','MessageController@getDetail');

    //消息推送
    Route::put('message/push/bind','PushController@bind');

    //通知
    Route::get('notice/list','NoticeController@getReceiveNotices');
    Route::get('notice/list.order','OrderController@getReceiveNotices');
    Route::delete('notice/delete.single','NoticeController@deleteMsgByRid');

    Route::get('order/list','OrderController@getReceiveNotices');
    Route::delete('order/delete.single','OrderController@deleteMsgByRid');

    //评论
    Route::get('comment/task/list.helper','CommentController@getHelperComments');
    Route::get('comment/task/list.employer','CommentController@getEmployerComments');

});
