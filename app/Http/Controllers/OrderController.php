<?php

namespace App\Http\Controllers;

use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct();
        $this->auth_manager($request->input('admin_id'),$request->input('admin_token'),8);
    }

    /**
     * 查询
     * @param $id
     * @return mixed
     */
    public function index(Request $request)
    {
//        $no = date("Ymd").random_int(10000000, 99999999).substr(microtime(true),-4);
        $pagenum = $request->input('pagenum');
        $pagenum = !empty($pagenum)?$pagenum:10;
        $keyword = $request->input('keyword');
        $keyword = empty($keyword)?'':explode(',',$keyword);
        if($keyword){
            $start_time = $keyword[0];
            $end_time = $keyword[1];
            $res = Order::select('order.id','order.order_no','c.title','a.nickname as up_nickname','b.nickname as pay_nickname','order.pay_type','order.amount','order.create_time')
                ->leftjoin('user as a',function($join){
                    $join->on('a.id','=','order.up_id');
                })->leftjoin('user as b',function($join){
                    $join->on('b.id','=','order.pay_id');
                })->leftjoin('user_products as c',function($join){
                    $join->on('c.id','=','order.product_id');
                })
                ->WhereBetween('order.create_time', [$start_time, $end_time])
                ->paginate($pagenum);
        }else{
            $res = Order::select('order.id','order.order_no','c.title','a.nickname as up_nickname','b.nickname as pay_nickname','order.pay_type','order.amount','order.create_time')
                ->leftjoin('user as a',function($join){
                    $join->on('a.id','=','order.up_id');
                })->leftjoin('user as b',function($join){
                    $join->on('b.id','=','order.pay_id');
                })->leftjoin('user_products as c',function($join){
                    $join->on('c.id','=','order.product_id');
                })
                ->paginate($pagenum);
        }
        if($res){
            $on_money = 0;
            $orderinfo = Order::select('amount','create_time')->get()->toArray();
            $all_money = array_sum(array_map(function($val){return $val['amount'];}, $orderinfo));
            $all_count = count($orderinfo);
            $arr = [];
            foreach ($orderinfo as $k=>$v){
                if($v['create_time']<date("Y-m-d 23:59:59") && $v['create_time']>date("Y-m-d")){
                    $on_money += $v['amount'];
                    $arr[$k] = $v;
                }
            }
            $on_count = count($arr);
            return $this->success(['all_money'=>$all_money,'on_money'=>$on_money,'all_count'=>$all_count,'on_count'=>$on_count,'info'=>$res]);
        }
        return $this->error();
    }

}
