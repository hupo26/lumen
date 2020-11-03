<?php

namespace App\Http\Controllers;

use App\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RuleController extends Controller
{

    private $rule = [
        'admin_id' => 'required',
        'admin_token' => 'required',
        'rule_name' => 'required|max:10',
        'power' => 'required',
        'explain' => 'required',
        'status' => 'required|in:0,1'
    ];

    private $message = [
        'admin_id.required' => '管理员id必须',
        'admin_token.required' => '管理员token必须',
        'rule_name.required' => '角色名称必须',
        'rule_name.max' => '角色名称字数不得超过10个字',
        'power.required' => '权限必须',
        'status.required' => '状态必须',
        'explain.required' => '角色说明必须',
        'status.in' => '状态错误',
    ];

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
    public function index()
    {
        $res = Rule::all();
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
        $res = Rule::create($request->all());
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
        if($id == 1)
            return $this->error('超级管理员无上权限不可修改');
        $Rule = Rule::find($id);
        if(!$Rule)
            return $this->error('保存失败');
        $Rule->rule_name = $request->input('rule_name');
        $Rule->power = $request->input('power');
        $Rule->explain = $request->input('explain');
        $Rule->status = $request->input('status');
        $res = $Rule->save();
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
        $id_str = $request->input('id');
        $ids = explode(",",$id_str);
        DB::beginTransaction();
        try{
            foreach($ids as $v){
                $where['id'] = $v;
                Rule::where($where)->delete();
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
