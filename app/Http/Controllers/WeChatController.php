<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\DB;

class WeChatController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function auth()
    {
        //备注：微信网页授权 获取用户基本信息
        $appid = env('WECHAT_APPID');
        $redirect_uri = urlencode ( 'http://lumen.hupo-games.com/wechat/getuserinfo');
        $url ="https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";
        header("Location:".$url);
    }

    /**
     * 获取微信授权信息
     * @return mixed
     */
    public function getuserinfo()
    {
        $appid = env('WECHAT_APPID');
        $secret = env('WECHAT_SECRET');
        if(!isset($_GET["code"]))
            return $this->error('授权失败');
        $code = $_GET["code"];
        //第一步:取得openid
        $oauth2Url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $secret . "&code=" . $code . "&grant_type=authorization_code";
        $oauth2 = getJson($oauth2Url);
        if(!isset($oauth2['access_token']) && !isset($oauth2['openid']))
            return $this->error('授权失败');
        $openid = $oauth2['openid'];
        $access_token = $oauth2['access_token'];
        $get_user_info_url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
        $userinfo = getJson($get_user_info_url);
        $data = [
            'openid'=>$openid,
            'nickname'=>$userinfo['nickname'],
            'gender'=>$userinfo['sex'],
            'city'=>$userinfo['city'],
            'province'=>$userinfo['province'],
            'country'=>$userinfo['country'],
            'avatar'=>$userinfo['headimgurl']
        ];
        DB::beginTransaction();
        $res = User::where(['openid'=>$openid])->select('id as uid','member_token as token')->sharedLock()->first();
        try{
            if(!$res){
                $data['member_token'] = strtoupper(md5(createrandstring(16, 1) . time()));
                $data['uid'] = User::insertGetId($data);
                //中间逻辑代码
                DB::commit();
                $data['token'] = $data['member_token'];
                unset($data['member_token']);
                return $this->success($data);
            }else{
                $token = strtoupper(md5(createrandstring(16, 1) . time()));
                $res['token'] = $token;
                User::where(['id'=>$res['uid']])->update(['member_token'=>$token]);
                //中间逻辑代码
                DB::commit();
                return $this->success($res);
            }
        }catch (\Exception $e) {
            //接收异常处理并回滚
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

}
