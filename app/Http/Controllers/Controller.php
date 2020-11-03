<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    /**
     * [_initialize 初始化]
     */
    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-type:text/json;charset=utf-8");
        header('Access-Control-Allow-Methods:POST,GET,OPTIONS,DELETE'); //支持的http 动作
        header('Access-Control-Allow-Headers:sign-uid,sign-token,x-requested-with,content-type'); //响应头 请按照自己需求添加。
    }
    /**
     * 获取成功输出
     * @param array $data
     * @param string $message
     * @param int $httpCode
     * @return mixed
     */
    public function success($data=array(),$message='成功',$httpCode=200){
        return response()->json(array(
            'status' => 200,
            'message'=>$message,
            'data'=>$data
        ),$httpCode);
    }


    /**
     * 错误提示输出
     * @param string $message
     * @param int $httpCode
     * @return mixed
     */
    public function error($message='失败',$httpCode=400){
        return response()->json(array(
            'status' => $httpCode,
            'message'=>$message,
            'data'=>[]
        ),$httpCode);
    }

    /**
     * 权限管理
     * @param $admin_id
     * @param $admin_token
     */
    public function auth_manager($admin_id,$admin_token,$rule_id){
        if(empty($admin_id) || empty($admin_token) || empty($rule_id))
            exit(self::resJson('权限鉴权失败'));
        $rule_type = DB::table('account')
            ->where('status','=',1)
            ->where('id','=',$admin_id)
            ->where('token','=',$admin_token)
            ->value('rule_type');
        if(!$rule_type && empty($rule_type))
            exit(self::resJson('管理员不存在或token已失效'));
        $rule_power = DB::table('rule')
            ->where('status','=',1)
            ->where('id','=',$rule_type)
            ->value('power');
        if(!$rule_power)
            exit(self::resJson('角色不存在'));
        $power_arr = explode(',',$rule_power);
        if($power_arr[$rule_id-1] == 0 || count($power_arr) != 8)
            exit(self::resJson('无权限操作'));
    }

    /**
     * @param $message
     * @return false|string
     */
    public function resJson($message ='')
    {
        return json_encode(['status' => 400, 'message'=>$message],true);
    }

    /**
     * @param $message
     * @return false|string
     */
    public function ApiVerify($input = [])
    {
        if(!array_key_exists('uid',$input) ||
            !array_key_exists('token',$input)
        )
            exit(self::resJson('鉴权失败'));
        $where['id'] = $input['uid'];
        $token = Db::table('user')->where($where)->value('member_token');
        if(empty($token))
            exit(self::resJson('用户不存在'));
        if($token!=$input['token'])
            exit(self::resJson('token已失效'));
    }
}
