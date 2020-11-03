<?php

namespace App\Http\Controllers;

use App\ProductLabel;
use App\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductTypeController extends Controller
{
    private $rule = [
        'admin_id' => 'required',
        'admin_token' => 'required',
        'content' => 'required|max:10',
        'status' => 'required|in:0,1',
        'type' => 'required|in:1,2',
        'sort' => 'required'
    ];

    private $message = [
        'admin_id.required' => '管理员id必须',
        'admin_token.required' => '管理员token必须',
        'content.required' => '类型内容必须',
        'content.max' => '类型内容字数不得超过10个字',
        'status.required' => '状态必须',
        'type.required' => '分类类型必须',
        'sort.required' => '排序必须',
        'status.in' => '状态错误',
        'type.in' => '分类类型状态错误',
    ];
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct();
        $this->auth_manager($request->input('admin_id'),$request->input('admin_token'),5);
    }
    /**
     * 查询
     * @param $id
     * @return mixed
     */
    public function index()
    {
        $res = ProductType::orderByDesc('sort')->orderByDesc('create_time')->get();
        if($res)
            return $this->success($res);
        return $this->error();
    }
    /**
     * 创建
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request)
    {
        $validator = Validator::make(request()->all(), $this->rule, $this->message);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return $this->error($messages->first());
        }
        $res = ProductType::create($request->all());
        if($res)
            return $this->success();
        return $this->error();
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
        $ProductType = ProductType::find($id);
        if(!$ProductType)
            return $this->error('保存失败');
        $ProductType->content = $request->input('content');
        $ProductType->status = $request->input('status');
        $ProductType->type = $request->input('type');
        $ProductType->sort = $request->input('sort');
        $res = $ProductType->save();
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
                $res = ProductType::where($where)->delete();
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
}
