<?php

/*
 * sc-admin-api.250 后台管理访问接口
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Bridges\Trade\Admin\HelperManagerBridge;

Route::get('/', function () {
    echo 'admin ok';
});

Route::get('/ok', 'IndexController@index');

Route::namespace('Permission')->group(function () {
    Route::post('/admin/login', 'AdminController@login');       // 登录
    Route::post('/admin/logout', 'AdminController@logout');     // 登录
    Route::get('/admin/info', 'AdminController@info');          // 登录用户信息

    Route::post('/system/list', 'SystemController@getList');    // 系统列表
    Route::get('/system/dic', 'SystemController@getDic');       // 系统字典
    Route::post('/system/add', 'SystemController@add');         // 添加系统
    Route::put('/system/edit', 'SystemController@edit');        // 编辑系统
    Route::delete('/system/del', 'SystemController@del');       // 删除系统

    Route::post('/admin/add', 'AdminController@add');           // 添加管理员
    Route::post('/admin/list', 'AdminController@getList');      // 管理员列表
    Route::put('/admin/edit', 'AdminController@edit');          // 编辑管理员
    Route::put('/admin/frozen', 'AdminController@frozen');      // 冻结管理员
    Route::put('/admin/unFrozen', 'AdminController@unFrozen');  // 解冻管理员
    Route::put('/admin/reset_pwd', 'AdminController@resetPwd'); // 重置密码
    Route::put('/admin/reset_pwd.self', 'AdminController@resetSelfPwd'); // 修改当前帐号密码
    Route::post('/admin/logs', 'AdminController@getLogs');      // 操作日志
    Route::get('/user/searchByPhone', 'AdminController@searchUserByPhone'); // 根据手机号查找用户

    Route::post('/role/list', 'RoleController@getList');                // 角色列表
    Route::get('/role/dic', 'RoleController@getDic');                   // 角色字典
    Route::post('/role/add', 'RoleController@add');                     // 添加角色
    Route::put('/role/edit', 'RoleController@edit');                    // 编辑角色
    Route::put('/role/edit.resource', 'RoleController@editResource');   // 修改权限
    Route::delete('/role/del', 'RoleController@del');                   // 删除角色

    Route::post('/api/list', 'ApiController@getList');                  // API列表
    Route::post('/api/add', 'ApiController@add');                       // 添加API
    Route::put('/api/edit', 'ApiController@edit');                      // 编辑API
    Route::delete('/api/del', 'ApiController@del');                     // 删除API

    Route::post('/api-group/list', 'ApiGroupController@getList');       // API分组列表
    Route::get('/api-group/dic', 'ApiGroupController@getDic');          // API分组字典
    Route::get('/api-group/tree', 'ApiGroupController@getTree');        // API列表
    Route::post('/api-group/add', 'ApiGroupController@add');            // 添加API分组
    Route::put('/api-group/edit', 'ApiGroupController@edit');           // 编辑API分组
    Route::delete('/api-group/del', 'ApiGroupController@del');          // 删除API分组

    Route::post('/resource/list', 'ResourceController@getList');        // 资源列表
    Route::post('/resource/tree', 'ResourceController@getTree');        // 资源树
    Route::post('/resource/add', 'ResourceController@add');             // 添加资源
    Route::put('/resource/edit', 'ResourceController@edit');            // 编辑资源
    Route::delete('/resource/del', 'ResourceController@del');           // 删除资源
});

Route::namespace('Common')->group(function () {
    Route::post('/upload/image', 'UploadController@image');      // 上传图片
});

Route::namespace('User')->group(function () {
    Route::post('/feedback/getList', 'FeedbackController@getList');     // 获取用户反馈列表
    Route::get('/feedback/types', 'FeedbackController@getTypes');       // 获取反馈类型
    Route::delete('/feedback/del', 'FeedbackController@del');           // 删除反馈

    Route::post('/user/getList', 'UserController@getList');                 // 获取用户列表
    Route::get('/user/certification', 'UserController@getCertification');   // 获取用户实名认证信息
    Route::put('/user/frozen', 'UserController@frozen');                    // 冻结用户
    Route::put('/user/unFrozen', 'UserController@unFrozen');                // 解冻用户
    Route::get('/user/detail', 'UserController@getDetail');                 // 用户详情
    Route::get('/user/position.search', 'UserController@positionList');        // 搜索可添加为客服的用户

    Route::post('/customer/getList', 'CustomerController@getList');     // 获取客服列表
    Route::get('/customer/search', 'CustomerController@search');        // 搜索可添加为客服的用户
    Route::post('/customer/add', 'CustomerController@add');             // 添加客服
    Route::put('/customer/cancel', 'CustomerController@cancel');        // 取消客服
    Route::get('/customer/service.user', 'CustomerController@getServiceUserList');  // 接待用户列表
    Route::get('/customer/service.msg', 'CustomerController@getServiceMsgList');    // 接待用户聊天记录
});

Route::namespace('Pool')->group(function () {
    Route::post('/article-category/list', 'ArticleCategoryController@getList');     // 文章分类列表
    Route::get('/article-category/dic', 'ArticleCategoryController@getDic');        // 文章分类字典
    Route::get('/article-category/all', 'ArticleCategoryController@getAll');        // 文章分类(全部)
    Route::get('/article-category/detail', 'ArticleCategoryController@getDetail');  // 文章分类详情
    Route::post('/article-category/add', 'ArticleCategoryController@add');          // 添加文章分类
    Route::put('/article-category/edit', 'ArticleCategoryController@edit');         // 编辑文章分类
    Route::delete('/article-category/del', 'ArticleCategoryController@del');        // 删除文章分类

    Route::post('/article/list', 'ArticleController@getList');      // 文章列表
    Route::get('/article/detail', 'ArticleController@getDetail');   // 文章详情
    Route::post('/article/add', 'ArticleController@add');           // 添加文章
    Route::put('/article/edit', 'ArticleController@edit');          // 编辑文章
    Route::delete('/article/del', 'ArticleController@del');         // 删除文章

    Route::post('/config/list', 'ConfigController@findAll');        // 全局配置项列表
    Route::get('/config/detail', 'ConfigController@find');          // 全局配项单个获取
    Route::post('/config/add', 'ConfigController@add');             // 添加全局配置项
    Route::put('/config/edit', 'ConfigController@update');          // 编辑全局配置项
    Route::put('/config/setValue', 'ConfigController@setValue');    // 修改指定key的值
    Route::delete('/config/del', 'ConfigController@remove');        // 删除配置项

    Route::post('/upgrade/list', 'UpgradeController@getList');      // APP更新列表
    Route::post('/upgrade/add', 'UpgradeController@add');           // 添加更新
    Route::put('/upgrade/edit', 'UpgradeController@edit');          // 编辑更新
    Route::delete('/upgrade/del', 'UpgradeController@del');         // 删除更新
});

Route::namespace('Message')->group(function () {
    Route::get('/message/im/getLoginParams', 'IMController@getLoginParams');
});

Route::namespace('Task')->group(function () {
    Route::post('/task/search', 'EmployerManagerController@search');    // 发单管理
    Route::get('/task/detail', 'EmployerManagerController@detail');     // 任务详情
    Route::post('/receive/search', 'HelperManagerController@search');   // 接单管理

    Route::post('/task.category/list', 'TaskCategoryManagerController@getList');  // 任务分类列表
    Route::post('/task.category/add', 'TaskCategoryManagerController@add');      // 添加分类
    Route::put('/task.category/edit', 'TaskCategoryManagerController@edit');     // 编辑分类
    Route::delete('/task.category/del', 'TaskCategoryManagerController@del');    // 删除分类
    Route::get('/task.category/dic', 'TaskCategoryManagerController@getDic');    // key-value字典
});

Route::namespace('Funds')->group(function () {
    Route::post('/withdraw/search', 'WithDrawManagerController@search');//提现管理
    Route::post('/withdraw/retry', 'WithDrawManagerController@retry');//提现重试
    Route::post('/withdraw/verify', 'WithDrawManagerController@verify');//提现审核
    Route::post('/pay/search', 'PayManagerController@search');//支付搜索
    Route::post('/pay/refundSearch', 'PayManagerController@refundSearch');//退款搜索
    Route::post('/pay/inoutLogSearch', 'PayManagerController@inoutLogSearch');//流水搜索

    Route::post('/account/search', 'AccountManagerController@search');//账户搜索
    Route::post('/account/addBalance', 'AccountManagerController@addBalance');//余额添加
    Route::get('/account/get', 'AccountManagerController@get');//单个用户余额获取
});

Route::namespace('Statistics')->group(function () {
    Route::post('/task/day', 'IndexController@taskDay');//获取指定天的任务统计
    Route::post('/task/day.search', 'IndexController@taskDayList');//任务统计列表
});

Route::namespace('Fee')->group(function () {
    Route::post('feerule/search','FeeManagerController@search');   // 平台扣费选项查询
    Route::put('feerule/edit','FeeManagerController@edit');   // 平台扣费选项查询
});
