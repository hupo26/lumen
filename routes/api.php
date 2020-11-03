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


$router->group([
    'middleware' => 'api',  //这个本来用来实现api接口的处理，暂未用到
    'prefix' => 'auth'
], function ($app) {
    $app->post('register', 'AuthController@register');  //注册
    $app->post('login', 'AuthController@login');  //登录
    $app->post('logout', 'AuthController@logout'); //登出
    $app->post('refresh', 'AuthController@refresh'); //刷新token
    $app->post('me', 'AuthController@me'); //获取个人信息
});
$router->group(['middleware'=>'refresh.token'],function($app){
    $app->get('profile','UserController@profile');  //个人中心
});
