<?php

namespace App\Http\Controllers;

use App\ProductLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductLabelController extends Controller
{
    private $rule = [
        'admin_id' => 'required',
        'admin_token' => 'required',
        'content' => 'required|max:6',
        'type' => 'required|in:1,2',
        'status' => 'required|in:0,1'
    ];

    private $message = [
        'admin_id.required' => '管理员id必须',
        'admin_token.required' => '管理员token必须',
        'content.required' => '标签必须',
        'content.max' => '标签字数不得超过6个字',
        'type.required' => '标签类型必须',
        'status.required' => '标签状态必须',
        'type.in' => '标签类型错误',
        'status.in' => '标签状态错误',
    ];
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct();
        $this->auth_manager($request->input('admin_id'),$request->input('admin_token'),6);
    }
    /**
     * 查询
     * @param $id
     * @return mixed
     */
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        if($keyword) {
            $res = ProductLabel::where('id', 'like', '%' . $keyword . '%')
                ->orWhere('content', 'like', '%' . $keyword . '%')
                ->get();
        }else{
            $res = ProductLabel::all();
        }
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
        $res = ProductLabel::create($request->all());
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
        $ProductLabel = ProductLabel::find($id);
        if(!$ProductLabel)
            return $this->error('保存失败');
        $ProductLabel->content = $request->input('content');
        $ProductLabel->type = $request->input('type');
        $ProductLabel->status = $request->input('status');
        $res = $ProductLabel->save();
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
                $res = ProductLabel::where($where)->delete();
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
        $ProductLabel = ProductLabel::find($id);
        if(!$ProductLabel)
            return $this->error();
        $ProductLabel->status = $request->input('status');

        $res = $ProductLabel->save();
        if($res)
            return $this->success();
        return $this->error();
    }
}
