<?php

namespace App\Http\Controllers;

use App\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminLoginController extends Controller
{
    private $rule = [
        'username' => 'required|max:10',
        'password' => 'required|min:6',
    ];

    private $message = [
        'username.required' => '账户名称必须',
        'password.required' => '账户密码必须',
        'username.max' => '账户名称最多10个字',
        'password.min' => '账户密码最少6位',
    ];

    /**
     * AdminLoginController constructor.
     */
    public function __construct() {
        parent::__construct();
//        header("Access-Control-Allow-Origin:*");    //允许访问的来源域名
//        header("Access-Control-Allow-Credentials: true");   //是否可以携带cookie
//        header("Access-Control-Allow-Methods: POST,GET,PUT,OPTIONS,DELETE");   //允许请求方式
//        header("Access-Control-Allow-Headers: X-Custom-Header");   //允许请求字段，由客户端决定
//        header("Content-Type: text/html; charset=application/json"); //返回数据类型（ text/html; charset=utf-8、 application/json; charset=utf-8 )
    }
    /**
     * 登录
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        $validator = Validator::make(request()->all(), $this->rule, $this->message);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return $this->error($messages->first());
        }
        $username = $request->input('username');
        $account_info = DB::table('account')
            ->where('username','=',$username)
            ->where('status','=',1)
            ->get();
        $res = json_decode($account_info,true);
        if(!$res)
            return $this->error('账号错误');
        $password = $request->input('password');
        if (password_verify($password, $res[0]['password'])) {
            $return['admin_id'] = $res[0]['id'];
            $return['admin_token'] = strtoupper(md5(createrandstring(16, 1) . time()));
            $logintype = account::find($return['admin_id']);
            $logintype->token = $return['admin_token'];
            $logintype->update_time = date("Y-m-d H:i:s");
            $res = $logintype->save();
            if($res)
                return $this->success($return);
            return $this->error();
        } else {
            return $this->error('密码错误');
        }
    }

}
