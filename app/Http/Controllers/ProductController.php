<?php

namespace App\Http\Controllers;

use App\Product;
use App\ProductLabel;
use App\Rule;
use App\Zan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    private $uid;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        parent::__construct();
        $this->uid = $request->input('uid');
        $this->ApiVerify(['uid'=>$this->uid,'token'=>$request->input('token')]);
    }
    /**
     * 文件上传（图片，视频）
     * @param Request $request
     * @return mixed
     */
    public function UploadFile(Request $request)
    {
        if($request->hasFile('file')) {
            $root = $request->server('DOCUMENT_ROOT');
            $file = $request->file('file');
            if ($file->getSize() / 1024 > 20480)
                return $this->error('请检查您上传的文件不能大于20MB');
            $fileName = strtolower($file->getClientOriginalName());
            if (!preg_match('/\.(jpg|jpeg|png|gif|mp4)$/', $fileName))
                return $this->error('您只能上传通用的图片格式');
            $destinationPath = '/uploads/' . date('Ymd');
            $fileExtendName = substr($fileName, strpos($fileName, '.'));
            $realPath = $root . $destinationPath;
            if (!file_exists($realPath))
                mkdir($realPath,0777,true);
            $newFileName = uniqid() . mt_rand(1, 100000) . $fileExtendName;
            $file->move($realPath, $newFileName);
            $filePath=$destinationPath . '/' . $newFileName;
            return $this->success([$filePath]);
        } else{
            return $this->error('请选择文件再上传');
        }
    }
    /**
     * 发布作品
     * @param Request $request
     * @return mixed
     */
    public function ProductUpload(Request $request)
    {
        $rule = [
            'uid' => 'required',
            'title' => 'required',
            'product_type' => 'required',
            'product_content' => 'required',
            'product_img_vedio' => 'required',
            'product_amount' => 'required',
            'product_money' => 'required',
            'product_allmoney' => 'required',
            'product_label' => 'required',
            'uptype' => 'required|in:1,2',
        ];

        $message = [
            'uid.required' => 'uid必须',
            'title.required' => '产品标题必须',
            'product_type.required' => '未选择产品分类',
            'product_content.required' => '产品内容必须',
            'product_img_vedio.required' => '未上传图片或视频',
            'product_amount.required' => '未设置产品购买数量上限',
            'product_money.required' => '未设置产品单次购买金额',
            'product_allmoney.required' => '未设置产品版权金额',
            'product_label.required' => '未选择标签',
            'uptype.required' => '上传类型必须',
            'uptype.in' => '上传类型错误',
        ];
        $validator = Validator::make(request()->all(), $rule, $message);
        if ($validator->fails()) {
            $messages = $validator->errors();
            return $this->error($messages->first());
        }
        $params = $request->all();
        $title = $params['title'];
        $uid = $params['uid'];
        $product_type = $params['product_type'];
        $product_img_vedio = $params['product_img_vedio'];
        $product_img_vedio_arr = explode(',',$product_img_vedio);
        foreach ($product_img_vedio_arr as $v)
        {
            if (!file_exists($request->server('DOCUMENT_ROOT').$v))
                return $this->error('文件上传出错，请重试');
        }
        $is_title = DB::select("SELECT * FROM user_products where title = '$title' limit 1");
        if($is_title)
            return $this->error('作品名称已存在');
        $is_uid = DB::select("SELECT * FROM user where id = '$uid' limit 1");
        if(!$is_uid)
            return $this->error('用户不存在');
        $is_product_type = DB::select("SELECT * FROM product_type where id = '$product_type' limit 1");
        if(!$is_product_type)
            return $this->error('产品类型不存在');
        $product_label = $params['product_label'];
        $is_product_label = DB::select("SELECT * FROM product_label where `id` in ($product_label)");
        if(count($is_product_label) != count(explode(',',$product_label)))
            return $this->error('产品标签不存在');
        if($params['uptype'] == 2){
            if(empty($params['vedio_first_img']) || empty($params['vedio_time']))
                return $this->error('视频第一帧必须或视频时长必须');
        }
        $label_ids = explode(',',$params['product_label']);
        $product_label_title = ProductLabel::where(function($query) use ($label_ids){
            $query->whereIn('id', $label_ids);
        })->where(['status'=>1])->get('content');
        $label_name_arr = json_decode($product_label_title,true);
        $content = array_column($label_name_arr,'content');
        $params['product_label_title'] = implode(',',$content);
        DB::beginTransaction();
        try{
            Product::create($params);
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
