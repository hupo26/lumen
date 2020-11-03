<?php

namespace App\Http\Controllers;

use App\AmountLog;
use App\Banner;
use App\Product;
use App\Zan;
use App\User;
use App\Order;
use App\ProductType;
use App\ProductLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller
{
    private $uid;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct();
        $this->uid = $request->input('uid');
        $this->ApiVerify(['uid'=>$this->uid,'token'=>$request->input('token')]);
    }

    /**
     * 首页列表
     * @param Request $request
     * @return mixed
     */
    public function getlist(Request $request)
    {
        $page = $request->input('page');
        $pagenum = $request->input('pagenum');
        $page = empty($page)?1:$page;
        $pagenum = !empty($pagenum)?$pagenum:20;
        $startnum = ($page-1)*$pagenum;
        $keyword = $request->input('keyword');
        $where = [];
        $where[] = ['user_products.status','=',1];
        $product_type = $request->input('product_type');
        /*产品类型*/
        if($product_type)
            $where[] = ['product_type','=',$product_type];
        /*产品分类,1:图片，2：视频*/
        $uptype = $request->input('uptype');
        if($uptype)
            $where[] = ['uptype','=',$uptype];
        if($keyword){
            $where[] = ['user_products.product_label_title','like',"%".$keyword."%"];
            $res['info'] = Product::select('user_products.*','user.nickname','user.avatar','product_type.content as product_type')
                ->join('user',function($join){
                    $join->on('user.id','=','user_products.uid');
                })->join('product_type',function($join){
                    $join->on('user_products.product_type','=','product_type.id');
                })
                ->where($where)
                ->orWhere('user_products.title','like','%'.$keyword.'%')
                ->orderby('user_products.zan','desc')
                ->orderby('user_products.create_time','desc')
                ->offset($startnum)->limit($pagenum)->get();
        }else{
            $res['info'] = Product::select('user_products.*','user.nickname','user.avatar','product_type.content as product_type')
                ->join('user',function($join){
                    $join->on('user.id','=','user_products.uid');
                })->join('product_type',function($join){
                    $join->on('user_products.product_type','=','product_type.id');
                })
                ->where($where)
                ->orderby('user_products.zan','desc')
                ->orderby('user_products.create_time','desc')
                ->offset($startnum)->limit($pagenum)->get();
        }
        $UPLOAD_URL = env('UPLOAD_URL');
        $res['info'] = json_decode($res['info'],true);
        $product_ids = array_column($res['info'],'id');
        $zan_pids = json_decode(Zan::where(function($query) use ($product_ids){
            $query->whereIn('pid', $product_ids);
        })->get('pid'),true);
        $zan = 0;
        if($zan_pids !=[])
        {
            $pids_arr = array_column($zan_pids,'pid');
            $zan = 1;
        }
        foreach ($res['info'] as &$v){
            $product_img_vedios = explode(',',$v['product_img_vedio']);
            $v['resource_nums'] = count($product_img_vedios);
            foreach ($product_img_vedios as $key=>$val){
                $url[$key] = $UPLOAD_URL.$val;
            }
            if($zan == 0){
                $v['is_zan'] = $zan;
            }else{
                if(in_array($v['id'],$pids_arr)){
                    $v['is_zan'] = $zan;
                }else{
                    $v['is_zan'] = 0;
                }
            }
            $v['product_img_vedio'] = $url;
            $v['product_label_title'] = explode(',',$v['product_label_title']);
        }
        if($page==1){
            $raw = Db::raw("concat("."'".env('UPLOAD_URL')."'".",banner.banner_img) as banner_img");
            $banner_obj = Banner::select($raw)
                ->where('status','=',1)
                ->limit(6)
                ->orderBy('create_time','desc')
                ->get();
            $res['banner'] = array_column(json_decode($banner_obj,true),'banner_img');
        }
        if($page==1){
            $label_obj = ProductLabel::where(['status'=>1])->get('content');
            $res['product_label'] = array_column(json_decode($label_obj,true),'content');
        }
        $res['count'] = ceil(Product::where($where)->count()/$pagenum);
        $res['page'] = $page;
        if($res)
            return $this->success($res);
        return $this->error();
    }
    /**
     * 作品详情
     * @param Request $request
     * @return mixed
     */
    public function getDetail(Request $request)
    {
        $pid = $request->input('pid');
        if(empty($pid))
            return $this->error('作品标识必须');
        $where = [];
        $where[] = ['user_products.status','=',1];
        $where[] = ['user_products.id','=',$pid];
        $res = Product::select('user_products.*','user.nickname','user.avatar','product_type.content as product_type')
            ->join('user',function($join){
                $join->on('user.id','=','user_products.uid');
            })->join('product_type',function($join){
                $join->on('user_products.product_type','=','product_type.id');
            })
            ->where($where)
            ->first();
        if(!$res)
            return $this->success();;
        $UPLOAD_URL = env('UPLOAD_URL');
        $res = json_decode($res,true);
        $zan_id = Zan::where(['uid'=>$this->uid,'pid'=>$pid])->value('id');
        $zan = 0;
        if($zan_id)
            $zan = 1;
        if($res['product_img_vedio']){
            $product_img_vedios = explode(',',$res['product_img_vedio']);
            $res['resource_nums'] = count($product_img_vedios);
            foreach ($product_img_vedios as $key=>$val){
                $url[$key] = $UPLOAD_URL.$val;
            }
        }
        $res['is_zan'] = $zan;
        $res['product_img_vedio'] = $url;
        $res['product_label_title'] = explode(',',$res['product_label_title']);
        if($res)
            return $this->success($res);
        return $this->error();
    }
    /**
     * 点赞
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function zan(Request $request)
    {
        $validator = Validator::make(request()->all(),
            ['state'=>'required|in:0,1'],
            [
                'state.required' => '点赞状态必须',
                'state.in' => '点赞状态错误',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->errors();
            return $this->error($messages->first());
        }
        $pid = $request->input('pid');
        $state = $request->input('state');
        if(empty($pid))
            return $this->error();
        $res = Zan::where(['uid'=>$this->uid,'pid'=>$pid])->lockForUpdate()->get(['pid','id','state']);
        $res = json_decode($res,true);
        if($res!=[] && $res[0]['state'] == 0 && $state == 0)
            return $this->error('点赞状态错误');
        if($res!=[] && $res[0]['state'] == 1 && $state == 1)
           return $this->error('已点赞过');
        DB::beginTransaction();
        try{
            if($res){
                Zan::where(['id'=>$res[0]['id']])->update(['state'=>$state]);
                if($state == 1){
                    Product::where(['id'=>$pid])->increment('zan');
                }else{
                    Product::where(['id'=>$pid])->decrement('zan');
                }
            }else{
                Zan::create($request->all());
                Product::where(['id'=>$pid])->increment('zan');
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

    /**
     * 我的
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function owninfo()
    {
        $res = DB::table('user as a')
            ->leftjoin('user_products as b',function($join){
                $join->on('a.id','=','b.uid');
            })
            ->select('a.nickname','a.avatar','a.gender','a.birthday','a.city','a.province','a.amount',DB::raw("ifnull(sum(b.zan),0) as zan"))
            ->where(['a.id'=>$this->uid])
            ->groupBy('a.id')
            ->first();
        return $this->success($res);
    }

    /**
     * 我的作品
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function myProducts(Request $request)
    {
        $page = $request->input('page');
        $pagenum = $request->input('pagenum');
        $uptype = $request->input('uptype');
        $status = $request->input('status');
        $page = empty($page)?1:$page;
        $pagenum = !empty($pagenum)?$pagenum:20;
        $startnum = ($page-1)*$pagenum;
        $status = empty($status) && $status!=0 ?2:$request->input('status');
        if($status <= 1)
            $where[] = ['user_products.status','=',$status];
        $where[] = ['user_products.uptype','=',empty($uptype)?1:$uptype];
        $where[] = ['user_products.uid','=',$this->uid];
        $res['info'] = Product::select('user_products.*','user.nickname','user.avatar','product_type.content as product_type')
            ->join('user',function($join){
                $join->on('user.id','=','user_products.uid');
            })->join('product_type',function($join){
                $join->on('user_products.product_type','=','product_type.id');
            })
            ->where($where)
            ->orderby('user_products.zan','desc')
            ->orderby('user_products.create_time','desc')
            ->offset($startnum)->limit($pagenum)->get();
        $res['info'] = json_decode($res['info'],true);
        $product_ids = array_column($res['info'],'id');
        $zan_pids = json_decode(Zan::where(function($query) use ($product_ids){
            $query->whereIn('pid', $product_ids);
        })->get('pid'),true);
        $zan = 0;
        if($zan_pids !=[])
        {
            $pids_arr = array_column($zan_pids,'pid');
            $zan = 1;
        }
        $UPLOAD_URL = env('UPLOAD_URL');
        foreach ($res['info'] as &$v){
            $product_img_vedios = explode(',',$v['product_img_vedio']);
            $v['resource_nums'] = count($product_img_vedios);
            foreach ($product_img_vedios as $key=>$val){
                $url[$key] = $UPLOAD_URL.$val;
            }
            if($zan == 0){
                $v['is_zan'] = $zan;
            }else{
                if(in_array($v['id'],$pids_arr)){
                    $v['is_zan'] = $zan;
                }else{
                    $v['is_zan'] = 0;
                }
            }
            $v['product_img_vedio'] = $url;
            $v['product_label_title'] = explode(',',$v['product_label_title']);
        }
        $res['count'] = ceil(Product::where($where)->count()/$pagenum);
        $res['page'] = $page;
        return $this->success($res);
    }
    /**
     * 我的购买/我已卖出
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function myBuySaleProducts(Request $request)
    {
        $page = $request->input('page');
        $pagenum = $request->input('pagenum');
        $type = $request->input('type');
        $page = empty($page)?1:$page;
        $pagenum = !empty($pagenum)?$pagenum:20;
        $startnum = ($page-1)*$pagenum;
        if($type == 1){
            $where[] = ['order.pay_id','=',$this->uid];
            $field = 'order.up_id';
        }else{
            $where[] = ['order.up_id','=',$this->uid];
            $field = 'order.pay_id';
        }
        $res['info'] = DB::table('order')
            ->select('order.order_no','user_products.*','user.nickname','user.avatar','product_type.content as product_type')
            ->join('user_products',function($join){
                $join->on('user_products.id','=','order.product_id');
            })->join('product_type',function($join){
                $join->on('user_products.product_type','=','product_type.id');
            })->join('user',function($join) use ($field){
                $join->on('user.id','=',$field);
            })
            ->where($where)
            ->orderby('order.create_time','desc')
            ->offset($startnum)->limit($pagenum)->get();
        $res['info'] = json_decode($res['info'],true);
        $product_ids = array_column($res['info'],'id');
        $zan_pids = json_decode(Zan::where(function($query) use ($product_ids){
            $query->whereIn('pid', $product_ids);
        })->get('pid'),true);
        $zan = 0;
        if($zan_pids !=[])
        {
            $pids_arr = array_column($zan_pids,'pid');
            $zan = 1;
        }
        $UPLOAD_URL = env('UPLOAD_URL');
        foreach ($res['info'] as &$v){
            $product_img_vedios = explode(',',$v['product_img_vedio']);
            $v['resource_nums'] = count($product_img_vedios);
            foreach ($product_img_vedios as $key=>$val){
                $url[$key] = $UPLOAD_URL.$val;
            }
            if($zan == 0){
                $v['is_zan'] = $zan;
            }else{
                if(in_array($v['id'],$pids_arr)){
                    $v['is_zan'] = $zan;
                }else{
                    $v['is_zan'] = 0;
                }
            }
            $v['product_img_vedio'] = $url;
            $v['product_label_title'] = explode(',',$v['product_label_title']);
        }
        $res['count'] = ceil(Order::where($where)->count()/$pagenum);
        $res['page'] = $page;
        return $this->success($res);
    }

    /**
     * 我的点赞
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function myZanProducts(Request $request)
    {
        $page = $request->input('page');
        $pagenum = $request->input('pagenum');
        $uptype = $request->input('uptype');
        $uptype = empty($uptype)?1:$uptype;
        $page = empty($page)?1:$page;
        $pagenum = !empty($pagenum)?$pagenum:20;
        $startnum = ($page-1)*$pagenum;
        $where[] = ['a.state','=',1];
        $where[] = ['a.uid','=',$this->uid];
        $where[] = ['b.uptype','=',$uptype];
        $res['info'] = DB::table('zan_log as a')
            ->select('b.*','d.nickname','d.avatar','c.content as product_type')
            ->join('user_products as b',function($join){
                $join->on('b.id','=','a.pid');
            })->join('product_type as c',function($join){
                $join->on('b.product_type','=','c.id');
            })->join('user as d',function($join){
                $join->on('d.id','=','b.uid');
            })
            ->where($where)
            ->orderby('b.create_time','desc')
            ->offset($startnum)->limit($pagenum)->get();
        $res['info'] = json_decode($res['info'],true);
        $UPLOAD_URL = env('UPLOAD_URL');
        foreach ($res['info'] as &$v){
            $product_img_vedios = explode(',',$v['product_img_vedio']);
            $v['resource_nums'] = count($product_img_vedios);
            foreach ($product_img_vedios as $key=>$val){
                $url[$key] = $UPLOAD_URL.$val;
            }
            $v['is_zan'] = 1;
            $v['product_img_vedio'] = $url;
            $v['product_label_title'] = explode(',',$v['product_label_title']);
        }
        $res['count'] = ceil(Zan::where(['state'=>1,'uid'=>$this->uid])->count()/$pagenum);
        $res['page'] = $page;
        return $this->success($res);
    }
    /**
     * 我的钱包
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function myBag(Request $request)
    {
        $page = $request->input('page');
        $page = empty($page)?1:$page;
        $pagenum = $request->input('pagenum');
        $pagenum = !empty($pagenum)?$pagenum:20;
        $startnum = ($page-1)*$pagenum;
        if($page == 1){
            $end_time = date("Y-m-d");
            $start_time = date("Y-m-d",strtotime("-1 day"));
            $res = Order::select('amount','create_time')->where('up_id','=',$this->uid)->get();
            $res_arr = json_decode($res,true);
            $return = [
                'amount'=>0,
                'yes_count'=>0,
                'yes_money'=>0,
                'all_count'=>0,
                'all_money'=>0
            ];
            /*当前余额*/
            $return['amount'] = User::where('id','=',$this->uid)->value('amount');
            $return['all_money'] = array_sum(array_map(function($val){return $val['amount'];}, $res_arr));
            $return['all_count'] = count($res_arr);
            if($return['all_count'] != 0){
                foreach ($res_arr as &$v){
                    if($v['create_time']<$end_time && $v['create_time']>$start_time){
                        $return['yes_money'] += $v['amount'];
                        $return['yes_count'] += 1;
                    }
                }
            }
        }
        $return['amount_log'] = AmountLog::select('amount','balance','status','amount_time')
            ->where('uid','=',$this->uid)
            ->offset($startnum)->limit($pagenum)
            ->orderby('amount_time','desc')
            ->get();
        return $this->success($return);
    }
}
