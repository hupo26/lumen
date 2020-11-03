<?php

namespace App\Http\Controllers;

use App\user;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private $rule = [
        'email' => 'required|email|max:255|unique:users',
        'password' => 'required',
        'name' => 'required',
    ];

    private $message = [
        'name.required' => '姓名必须',
        'email.required' => '邮箱必须',
        'email.email' => '邮箱格式不正确',
        'email.max' => '邮箱最大255个字',
        'email.unique' => '该邮箱已存在',
        'password.required' => '密码必须',
    ];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        //在 Authenticate 里 使用 guard => api  验证用户信息
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * @description register user
     *
     * @param
     * @return
     * @author guilong
     * @date 2018-08-02
     */
    public function register()
    {

        //直接输出错误
//        $this->validate($request, $this->rule,$this->message);
//        捕获错误
        $validator = Validator::make(request()->all(), $this->rule, $this->message);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return response()->json([
                'code' => 501,
                'msg' => $messages->first()
            ]);
        }

        $user = [
            'email' => request()->input('email'),
            'name' => request()->input('name'),
            'password' => bcrypt(request()->input('password')),
        ];
        try {
            //插入数据库
            $user_info = User::create($user);
            //获取token
            $token = JWTAuth::fromUser($user_info);
            //更新token
            User::where('id', '=', $user_info['id'])->update(['api_token' => $token]);


        } catch (Exception $e) {
//            var_dump($e->getMessage());
//            var_dump($e->getCode());
            return response()->json([
                'code' => 502,
                'msg' => $this->message['email.unique']
            ]);
        }

        return response()->json([
            'code' => 200,
            'msg' => '',
            'access_token' => $token
        ]);
    }

    public function login()
    {
        //直接输出错误
//        $this->validate($request, $this->rule,$this->message);
//        捕获错误
//        $validator = Validator::make(request()->all(), ['email' => 'required|email|max:255', 'password' => $this->rule['password']], $this->message);
//        if ($validator->fails()) {
//            $messages = $validator->errors();
//            return response()->json([
//                'code' => 501,
//                'msg' => $messages->first()
//            ]);
//        }

        $credentials = request(['email', 'password']);
        $token = auth()->attempt($credentials);
        print_r($token);die;
        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                'code' => 401,
                'msg' => '登录失败'
            ]);
        }

        return response()->json([
            'code' => 200,
            'msg' => '',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ]
        ]);
    }

    public function me()
    {

        try {
            $user = auth()->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json([
                'code' => 401,
                'msg' => '登录失败',
            ]);
        }

        return response()->json([
            'code' => 200,
            'msg' => '',
            'data' => $user,
        ]);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json([
            'code' => 200,
            'msg' => 'logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'code' => 200,
            'msg' => '',
            'data' => [
                'access_token' => auth()->refresh(),
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ]
        ]);
    }
}
