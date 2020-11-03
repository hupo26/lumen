<?php

namespace App\Http\Controllers;

use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    private $UPLOAD_URL;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->UPLOAD_URL = env('UPLOAD_URL');
        parent::__construct();
        $this->auth_manager($request->input('admin_id'),$request->input('admin_token'),7);
    }
    /**
     * 查询
     * @param $id
     * @return mixed
     */
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $pagenum = $request->input('pagenum');
        $pagenum = !empty($pagenum)?$pagenum:10;
        if($keyword){
            $res = DB::table('user')
                ->select('id','nickname','mobile','create_time')
                ->where('status','=',1)
                ->where('id','like','%'.$keyword.'%')
                ->orWhere('nickname','like','%'.$keyword.'%')
                ->orWhere('mobile','like','%'.$keyword.'%')
                ->orderby('id','desc')
                ->paginate($pagenum);
        }else{
            $res = DB::table('user')
                ->select('id','nickname','mobile','create_time')
                ->orderby('id','desc')
                ->paginate($pagenum);
        }
        if($res)
            return $this->success($res);
        return $this->error();
    }
    /**
     * 用户信息展示
     * @param $id
     * @return mixed
     */
    public function userinfo(Request $request)
    {
        $uid = $request->input('uid');
        $type = $request->input('type');
        $type = isset($type) && !empty($type)?$type:1;
        switch ($type){
            /*他的上传*/
            case 1:
                $product_info = DB::table('user_products as a')
                    ->leftjoin('order as b',function($join){
                        $join->on('a.id','=','b.product_id');
                    })
                    ->select(DB::raw("ifnull(sum(b.amount),0) as sum_amount"),'a.*')
                    ->where('a.uid','=',$uid)
                    ->groupBy('a.id')
                    ->orderby('a.id','desc')
                    ->get();
                break;
            /*他的购买*/
            case 2:
                $product_info = DB::table('order as a')
                    ->leftjoin('user_products as b',function($join){
                        $join->on('a.product_id','=','b.id');
                    })
                    ->select(DB::raw("ifnull(sum(a.amount),0) as sum_amount"),'b.*')
                    ->where('a.pay_id','=',$uid)
                    ->groupBy('a.id')
                    ->orderby('a.id','desc')
                    ->get();
                break;

        }
        $product_info = json_decode($product_info,true);
        $ProductLabels = app('db')->select("SELECT id,content FROM product_label where status = '1'");
        $ProductLabels = array_column($ProductLabels,'content','id');
        foreach ($product_info as &$v_pro){
            $product_img_vedios = explode(',',$v_pro['product_img_vedio']);
            foreach ($product_img_vedios as $key=>$val){
                $url[$key] = $this->UPLOAD_URL.$val;
            }
            $v_pro['product_img_vedio'] = $url;
            $product_label = explode(',',$v_pro['product_label']);
            $label = [];
            foreach ($product_label as $l_k=>$l_v){
                if(isset($ProductLabels[$l_v])){
                    $label[$l_k] = $ProductLabels[$l_v];
                }
            }
            $v_pro['product_label'] = $label;
        }
        $res['product_info'] = $product_info;
        $res['all_nums'] = count($res['product_info']);
        $res['img_nums'] = 0;
        $res['vedio_nums'] = 0;
        if(count($res['product_info'])>0){
            foreach ($res['product_info'] as &$val){
                if($val['uptype'] == 1){
                    $res['img_nums'] +=1;
                }else{
                    $res['vedio_nums'] +=1;
                }
            }
        }
        $res['userinfo'] = DB::table('user')
            ->select('avatar','nickname','gender','province','city','mobile')
            ->where('id','=',$uid)
            ->orderby('id','desc')
            ->get();
        $res['on_money'] = 0;
        $orderinfo = Order::select('amount','create_time')->get()->toArray();
        $res['all_money'] = array_sum(array_map(function($val){return $val['amount'];}, $orderinfo));
        $res['all_count'] = count($orderinfo);
        $arr = [];
        foreach ($orderinfo as $k=>$v){
            if($v['create_time']<date("Y-m-d 23:59:59") && $v['create_time']>date("Y-m-d")){
                $res['on_money'] += $v['amount'];
                $arr[$k] = $v;
            }
        }
        $res['on_count'] = count($arr);
        if($res){
            return $this->success($res);
        }
        return $this->error();
    }
    /**
     * 用户不同作品类型信息展示
     * @param $id
     * @return mixed
     */
    public function productInfos(Request $request)
    {
        $uid = $request->input('uid');
        $type = $request->input('type');
        $res = DB::table('user_products as a')
            ->leftjoin('order as b',function($join){
                $join->on('a.id','=','b.product_id');
            })
            ->select('a.*',DB::raw("ifnull(sum(b.amount),0) as sum_amount"))
            ->where('a.uid','=',$uid)
            ->where('a.uptype','=',$type)
            ->groupBy('a.id')
            ->orderby('a.id','desc')
            ->get();
        if($res){
            $res = json_decode($res,true);
            $ProductLabels = app('db')->select("SELECT id,content FROM product_label where status = '1'");
            $ProductLabels = array_column($ProductLabels,'content','id');
            foreach ($res as &$v){
                $product_img_vedios = explode(',',$v['product_img_vedio']);
                foreach ($product_img_vedios as $key=>$val){
                    $url[$key] = $this->UPLOAD_URL.$val;
                }
                $v['product_img_vedio'] = $url;
                $product_label = explode(',',$v['product_label']);
                $label = [];
                foreach ($product_label as $l_k=>$l_v){
                    if(isset($ProductLabels[$l_v])){
                        $label[$l_k] = $ProductLabels[$l_v];
                    }
                }
                $v['product_label'] = $label;
            }
            return $this->success($res);
        }
        return $this->error();
    }
    /**
     * 删除
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function delete(Request $request)
    {
        $validator = Validator::make(request()->all(), ['id'=>'required'],['id.required'=>'id必须']);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return $this->error($messages->first());
        }
        $ids = $request->input('id');
        $str = explode(",",$ids);
        DB::beginTransaction();
        try{
            foreach($str as $v){
                $where['id'] = $v;
                DB::table('user')->where($where)->update(['status'=>0]);
            }
            //中间逻辑代码
            DB::commit();
            return $this->success();
        }catch (\Exception $e) {
            //接收异常处理并回滚
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }
}
