<?php

namespace App\Http\Controllers;

use App\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
class ExampleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        //        print_r(app('redis')->ping());die;
        $cars = Car::all();
//        $cars = app('db')->select("SELECT * FROM cars order by id desc limit 1");
//        $cars = \DB::select("SELECT * FROM cars");
        //Illuminate\Support\Facades\Redis
//        Redis::setex('site_name', 100, 'Lumen的redis');
//        return Redis::get('site_name');
        //Illuminate\Support\Facades\Cache
        Cache::store('redis')->put('site_name', 'Lumen测试', 10);
        $cars = Cache::store('redis')->get('site_name');
//        return Cache::store('redis')->get('site_name');
        return $this->success($cars,'删除成功');
    }

    //
}
