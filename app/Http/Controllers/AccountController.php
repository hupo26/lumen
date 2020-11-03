<?php

namespace App\Http\Controllers;

use App\Account;
use App\Banner;
use App\Product;
use App\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    private $nums;
    private $rule = [
        'admin_id' => 'required',
        'admin_token' => 'required',
        'username' => 'required|max:10',
        'password' => 'required|min:6',
        'mobile' => 'required',
        'rule_type' => 'required',
        'status' => 'required|in:0,1'
    ];

    private $message = [
        'admin_id.required' => '管理员id必须',
        'admin_token.required' => '管理员token必须',
        'username.required' => '账户名称必须',
        'username.max' => '账户名称最多10个字',
        'rule_name.max' => '账户名称字数不得超过10个字',
        'password.required' => '账户密码必须',
        'password.min' => '账户密码最少6位',
        'mobile.required' => '手机号码必须',
        'rule_type.required' => '账户角色必须',
        'status.required' => '状态必须',
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
        $this->nums = count($request->post());
    }

    /**
     * 查询
     * @param $id
     * @return mixed
     */
    public function index()
    {
        $res = DB::table('account as a')
            ->leftjoin('rule as b',function($join){
                $join->on('a.rule_type','=','b.id');
            })
            ->select('a.*','b.status as rule_status')
            ->orderBy('a.create_time','desc')
            ->get();
        $res_arr = json_decode($res,true);
        $Rule = Rule::get()->toArray();
        $rule_type_arr = array_column($Rule,'rule_name','id');
        foreach ($res_arr as &$v){
            $v['rule_type'] = $rule_type_arr[$v['rule_type']];
        }
        if($res_arr)
            return $this->success($res_arr);
        return $this->error();
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
            $account = DB::table('account')
                ->where('username','=',$request->input('username'))
                ->value('id');
            if($account)
                return $this->error('该账户名已存在');
            $rule_type = $request->input('rule_type');
            if(!app('db')->select("SELECT id,rule_name FROM rule where status = '1' and id = '$rule_type'"))
                return $this->error('角色类型不存在');
            $params = $request->all();
            if($params['rule_type'] == 1)
                return $this->error('不可创建超级管理员');
            $params['password'] = password_hash($params['password'], PASSWORD_BCRYPT);
            $params['token'] = strtoupper(md5(createrandstring(16, 1) . time()));
            $res = Account::create($params);
            if($res)
                return $this->success();
            return $this->error();
        }else{
            $return['rule'] = app('db')->select("SELECT id,rule_name FROM rule where status = '1'");
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
        if($this->nums != 2) {
            $this->rule['id'] = 'required';
            $this->message['id.required'] = 'id必须';
            $validator = Validator::make(request()->all(), $this->rule, $this->message);
            if ($validator->fails()) {
                $messages = $validator->errors();
                return $this->error($messages->first());
            }
            $id = request()->input('id');
            if ($id == 1)
                return $this->error('超级管理员无上权限不可修改');
            $Account = Account::find($id);
            if (!$Account)
                return $this->error('保存失败');
            $Account->username = $request->input('username');
            $Account->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
            $Account->mobile = $request->input('mobile');
            $Account->rule_type = $request->input('rule_type');
            $Account->status = $request->input('status');
            $res = $Account->save();
            if ($res)
                return $this->success();
            return $this->error('保存失败');
        }else{
            $return['rule'] = app('db')->select("SELECT id,rule_name FROM rule where status = '1'");
            return $this->success($return);
        }
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
        if($id ==1)
            return $this->error('不可删除超级管理员');
        $id_arr = explode(",",$id);
        DB::beginTransaction();
        try{
            foreach($id_arr as $v){
                $where['id'] = $v;
                $res = Account::where($where)->delete();
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
        $UserProduct = Account::find($id);
        if(!$UserProduct)
            return $this->error();
        $UserProduct->status = $request->input('status');
        $res = $UserProduct->save();
        if($res)
            return $this->success();
        return $this->error();
    }
}
