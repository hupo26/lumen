<?php

namespace App\Http\Controllers;

use App\Product;
use App\ProductLabel;
use App\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserProductController extends Controller
{
    private $nums;
    private $rule = [
        'admin_id' => 'required',
        'admin_token' => 'required',
        'uid' => 'required',
        'title' => 'required|max:30',
        'product_type' => 'required',
        'product_content' => 'required|max:300',
        'product_img_vedio' => 'required',
        'product_amount' => 'required',
        'product_money' => 'required',
        'product_allmoney' => 'required',
        'product_label' => 'required',
        'uptype' => 'required|in:1,2',
    ];

    private $message = [
        'admin_id.required' => '管理员id必须',
        'admin_token.required' => '管理员token必须',
        'uid.required' => 'uid必须',
        'title.required' => '产品标题必须',
        'title.max' => '产品标题最多可输入30字',
        'product_type.required' => '未选择产品分类',
        'product_content.required' => '产品内容必须',
        'product_content.max' => '产品内容最多可输入300字',
        'product_img_vedio.required' => '未上传图片或视频',
        'product_amount.required' => '未设置产品购买数量上限',
        'product_money.required' => '未设置产品单次购买金额',
        'product_allmoney.required' => '未设置产品版权金额',
        'product_label.required' => '未选择标签',
        'uptype.required' => '上传类型必须',
        'uptype.in' => '上传类型错误',
    ];
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct();
        $this->auth_manager($request->input('admin_id'),$request->input('admin_token'),1);
        $this->nums = count($request->post());
    }
    /**
     * 查询
     * @param $id
     * @return mixed
     */
    public function index(Request $request)
    {
        $pagenum = $request->input('pagenum');
        $pagenum = !empty($pagenum)?$pagenum:10;
        $UPLOAD_URL = env('UPLOAD_URL');
        $keyword = $request->input('keyword');
        $where = [];
        $product_type = $request->input('product_type');
        /*产品类型*/
        if($product_type)
            $where[] = ['product_type','=',$product_type];
        /*产品分类,1:图片，2：视频*/
        $uptype = $request->input('uptype');
        if($uptype)
            $where[] = ['uptype','=',$uptype];
        /*搜索*/
        if($keyword){
            $where[] = ['user_products.id','like',"%".$keyword."%"];
            $res = Product::select('user.nickname','user.avatar','product_type.content as product_type_title','user_products.*')
                ->join('user',function($join){
                    $join->on('user.id','=','user_products.uid');
                })->join('product_type',function($join){
                    $join->on('user_products.product_type','=','product_type.id');
                })
                ->where($where)
                ->orWhere('user_products.title','like','%'.$keyword.'%')
                ->orderby('user_products.id','desc')
                ->paginate($pagenum);
        }else{
            $res = Product::select('user.nickname','user.avatar','product_type.content as product_type_title','user_products.*')
                ->join('user',function($join){
                    $join->on('user.id','=','user_products.uid');
                })->join('product_type',function($join){
                    $join->on('user_products.product_type','=','product_type.id');
                })
                ->where($where)
                ->orderby('user_products.id','desc')
                ->paginate($pagenum);
        }
        $json_res = json_encode($res,true);
        $arr_res = json_decode($json_res, true);
        $data = $arr_res['data'];
        $ProductLabels = app('db')->select("SELECT id,content FROM product_label where status = '1'");
        $ProductLabels = array_column($ProductLabels,'content','id');
        foreach ($data as &$v){
            $product_img_vedios = explode(',',$v['product_img_vedio']);
            foreach ($product_img_vedios as $key=>$val){
                $url[$key] = $UPLOAD_URL.$val;
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
        $arr_res['product_type'] = ProductType::where("status","=",1)->get(['id', 'content']);
        $arr_res['data'] = $data;
        return $this->success($arr_res);
    }
    /**
     * 创建
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request)
    {
        if($this->nums != 2){
            $validator = Validator::make(request()->all(), $this->rule, $this->message);
            if ($validator->fails()) {
                $messages = $validator->errors();
                return $this->error($messages->first());
            }
            $title = $request->input('title');
            $is_title = app('db')->select("SELECT * FROM user_products where title = '$title' limit 1");
            if($is_title)
                return $this->error('作品名称已存在');
            $params = request()->all();
            if($params['uptype'] == 2){
                if(empty($params['vedio_first_img']) || empty($params['vedio_time']))
                    return $this->error('视频第一帧必须和视频时长必须');
            }
            $label_ids = explode(',',$params['product_label']);
            $product_label_title = ProductLabel::where(function($query) use ($label_ids){
                $query->whereIn('id', $label_ids);
            })->where(['status'=>1])->get('content');
            $label_name_arr = json_decode($product_label_title,true);
            $content = array_column($label_name_arr,'content');
            $params['product_label_title'] = implode(',',$content);
            $res = Product::create($request->all());
            if($res)
                return $this->success();
            return $this->error();
        }else{
            $return['product_type'] = app('db')->select("SELECT id,content FROM product_type where status = '1'");
            $return['product_label'] = app('db')->select("SELECT id,content FROM product_label where status = '1'");
            return $this->success($return);
        }
    }

    /**
     * 更新
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function update(Request $request)
    {
        $this->rule['id'] = 'required';
        $this->message['id.required'] = 'id必须';
        $validator = Validator::make(request()->all(), $this->rule, $this->message);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return $this->error($messages->first());
        }
        $id = request()->input('id');
        $UserProduct = Product::find($id);
        if(!$UserProduct)
            return $this->error('保存失败');
        $UserProduct->title = $request->input('title');
        $UserProduct->product_type = $request->input('product_type');
        $UserProduct->product_content = $request->input('product_content');
        $UserProduct->product_img_vedio = $request->input('product_img_vedio');
        $UserProduct->product_amount = $request->input('product_amount');
        $UserProduct->product_money = $request->input('product_money');
        $UserProduct->product_allmoney = $request->input('product_allmoney');
        $UserProduct->product_label = $request->input('product_label');
        $UserProduct->uptype = $request->input('uptype');
        $res = $UserProduct->save();
        if($res)
            return $this->success();
        return $this->error('保存失败');
    }
    /**
     * 删除
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function delete(Request $request)
    {
        $validator = Validator::make(request()->all(), ['id'=>'required'],['id.required' => 'id必须']);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return $this->error($messages->first());
        }
        $id = $request->input('id');
        $id_arr = explode(",",$id);
        DB::beginTransaction();
        try{
            foreach($id_arr as $v){
                $where['id'] = $v;
                $res = Product::where($where)->delete();
                if(!$res)
                    return $this->error();;
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
     * 上下架
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function status(Request $request)
    {
        $validator = Validator::make(
            request()->all(),
            ['id'=>'required','status'=>'required'],
            ['id.required' => 'id必须','status.required'=>'状态必须']
        );
        if ($validator->fails()) {
            $messages = $validator->errors();
            return $this->error($messages->first());
        }
        $id = $request->input('id');
        $UserProduct = Product::find($id);
        if(!$UserProduct)
            return $this->error();
        $UserProduct->status = $request->input('status');
        $res = $UserProduct->save();
        if($res)
            return $this->success();
        return $this->error();
    }
}
