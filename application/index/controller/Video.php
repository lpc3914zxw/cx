<?php
namespace app\index\controller;

use app\index\controller\Base;
use think\Url;
use think\Config;
use Qcloud\Cos\Client;
use think\Loader;
class Video extends Base
{
    //生成签名
    public function makesign(){

        $secret_id = $this->systeminfo['secretid'];
        $secret_key = $this->systeminfo['secretkey'];
        // 确定签名的当前时间和失效时间
        $current = time();
        $expired = $current + 86400;  // 签名有效期：1天
        // 向参数列表填入参数
        $arg_list = array(
            "secretId" => $secret_id,
            "currentTimeStamp" => $current,
            "expireTime" => $expired,
            "random" => rand(),
            "procedure"=>"QCVB_SimpleProcessFile(1,1)"//指定视频处理方式为:设置水印和转码
        );
        // 计算签名
        $orignal = http_build_query($arg_list);
        $signature = base64_encode(hash_hmac('SHA1', $orignal, $secret_key, true).$orignal);
        return ['signature'=>$signature,'returnMsg'=>'return successfully!','returnValue'=>0];
    }

    public function getVideoUrl($id = 0){
        $match_model = new \app\index\model\Match();
        return $match_model->where('id',$id)->column('videourl')[0];
    }
    
}
