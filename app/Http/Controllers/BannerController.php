<?php

namespace App\Http\Controllers;

use App\Banner;
use App\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    private $rule = [
        'admin_id' => 'required',
        'admin_token' => 'required',
        'banner_img' => 'required',
        'status' => 'required|in:0,1'
    ];

    private $message = [
        'admin_id.required' => '管理员id必须',
        'admin_token.required' => '管理员token必须',
        'banner_img.required' => '图片必须',
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
        $this->auth_manager($request->input('admin_id'),$request->input('admin_token'),4);
    }
    /**
     * 查询
     * @param $id
     * @return mixed
     */
    public function index()
    {
        $res = Banner::select('id',Db::raw("concat("."'".env('UPLOAD_URL')."'".",banner.banner_img) as banner_img"),'status','create_time')->get();
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
        if (!file_exists($request->server('DOCUMENT_ROOT').request()->all()['banner_img']))
            return $this->error('文件上传出错，请重试');
        $res = Banner::create($request->all());
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
        $banner = Banner::find($id);
        if(!$banner)
            return $this->error('保存失败');
        $banner->banner_img = $request->input('banner_img');
        $res = $banner->save();
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
                $res = banner::where($where)->delete();
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
