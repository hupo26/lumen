<?php

namespace App\Http\Controllers;

use App\AmountLog;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AmountLogController extends Controller
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
            $res = AmountLog::select('amount_log.id','amount_log.amount_number','user.nickname','user.avatar','amount_log.amount','amount_log.status','amount_log.amount_time')
                ->join('user',function($join){
                    $join->on('user.id','=','amount_log.uid');
                })
                ->WhereBetween('amount_time', [$start_time, $end_time])
                ->paginate($pagenum);
        }else{
            $res = AmountLog::select('amount_log.id','amount_log.amount_number','user.nickname','user.avatar','amount_log.amount','amount_log.status','amount_log.amount_time')
                ->join('user',function($join){
                    $join->on('user.id','=','amount_log.uid');
                })
                ->paginate($pagenum);
        }
        if($res)
            return $this->success($res);
        return $this->error();
    }
}
