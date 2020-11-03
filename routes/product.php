<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/*微信授权*/
$router->group([
    'prefix' => '/wechat'
], function ($router) {
    $router->get('/','WeChatController@auth');
    $router->get('getuserinfo','WeChatController@getuserinfo');
});
$router->group([
    'prefix' => '/product'
], function ($router) {
    $router->post('UploadFile', 'ProductController@UploadFile'); //上传资源
    $router->post('ProductUpload', 'ProductController@ProductUpload'); //上传作品信息
    $router->post('UpLabel', 'ProductController@UpLabel'); //添加标签
    $router->post('getlist', 'ApiController@getlist'); //首页列表
    $router->post('getDetail', 'ApiController@getDetail'); //首页列表
    $router->post('detailZanState', 'ApiController@detailZanState'); //点赞高亮状态
    $router->post('zan', 'ApiController@zan'); //点赞
    $router->post('owninfo', 'ApiController@owninfo'); //我的信息
    $router->post('myProducts', 'ApiController@myProducts'); //我的作品
    $router->post('orderInfo', 'ApiController@myBuySaleProducts'); //我的购买/我已卖出
    $router->post('myZanProducts', 'ApiController@myZanProducts'); //我的点赞
    $router->post('myBag', 'ApiController@myBag'); //我的钱包
});
/*账户管理*/
$router->group([
    'prefix' => '/account'
], function ($router) {
    $router->post('create','AccountController@create');
    $router->post('update','AccountController@update');
    $router->post('delete','AccountController@delete');
    $router->post('index','AccountController@index');
    $router->post('login','AdminLoginController@login');
});
/*banner管理*/
$router->group([
    'prefix' => '/banner'
], function ($router) {
    $router->post('create','BannerController@create');
    $router->post('update','BannerController@update');
    $router->post('delete','BannerController@delete');
    $router->post('index','BannerController@index');
});
/*提现记录*/
$router->group([
    'prefix' => '/cash'
], function ($router) {
    $router->post('index','AmountLogController@index');
});
/*订单管理*/
$router->group([
    'prefix' => '/order'
], function ($router) {
    $router->post('create','OrderController@create');
    $router->post('update','OrderController@update');
    $router->post('delete','OrderController@delete');
    $router->post('index','OrderController@index');
});
/*标签管理*/
$router->group([
    'prefix' => '/productLabel'
], function ($router) {
    $router->post('create','ProductLabelController@create');
    $router->post('update','ProductLabelController@update');
    $router->post('delete','ProductLabelController@delete');
    $router->post('status','ProductLabelController@status');
    $router->post('index','ProductLabelController@index');
});
/*分类管理*/
$router->group([
    'prefix' => '/productType'
], function ($router) {
    $router->post('create','productTypeController@create');
    $router->post('update','productTypeController@update');
    $router->post('delete','productTypeController@delete');
    $router->post('index','productTypeController@index');
});
/*权限（角色）管理*/
$router->group([
    'prefix' => '/rule'
], function ($router) {
    $router->post('create','RuleController@create');
    $router->post('update','RuleController@update');
    $router->post('delete','RuleController@delete');
    $router->post('index','RuleController@index');
});
/*用户管理*/
$router->group([
    'prefix' => '/user'
], function ($router) {
    $router->post('create','UserController@create');
    $router->post('update','UserController@update');
    $router->post('delete','UserController@delete');
    $router->post('index','UserController@index');
    $router->post('userinfo','UserController@userinfo');
    $router->post('productInfos','UserController@productInfos');
});
/*作品管理*/
$router->group([
    'prefix' => '/userProduct'
], function ($router) {
    $router->post('create','UserProductController@create');
    $router->post('update','UserProductController@update');
    $router->post('delete','UserProductController@delete');
    $router->post('index','UserProductController@index');
    $router->post('status','UserProductController@status');
});
