<?php
/*
 * Created by PhpStorm.
 * User: xiaomage
 * Date: 2018/9/10
 * Time: 12:44
 * 上传接口
 */

namespace app\wxapp\controller;

use think\Loader;
use Qcloud\Cos\Client;
use think\Config;
use QcloudImage\CIClient;
use app\wxapp\controller\Base;

class Upload extends Base
{
    protected $cosConfig = [];

    /*
     * 录制音频
     * @param string $img
     */
    public function uploadAudio(){
        $file = request()->file('file');
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            $filename = $info->getSaveName();
            $file = 'uploads/'. $info->getSaveName();
            $filePath = $this->uploadAudioLocalCos($file,'audio',$filename);
            return json_encode(['code' => 0,'filepath' =>$filePath]);
        }
    }

    /*
     * 上传图片
     * @return mixed|string
     */
    public function uploadImg(){
        $file = request()->file('file');
        //var_dump($file);exit;
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            $filename = $info->getSaveName();
            $file = 'uploads/'. $info->getSaveName();
            $url = uploadLocalToOss($file,"images", $filename);
            //var_dump($url);exit;
            unlink($file);
            return json_encode(['code'=>0,'filepath'=>$url,'msg'=>'上传成功']);
        }
    }

    /*
     * APP 反馈问题图片上传 调用接口
     * @return false|mixed|string
     */
  /*  public function uploadImage(){
        $str = '';
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        //$documentRoot = $_SERVER['DOCUMENT_ROOT'];
        //var_dump($_POST);exit;
        if(!empty($_POST['file'])){

            $a = 0;
            foreach($_POST['file'] as $fkey=> $fval){
              $name = time().$fkey;

              file_put_contents(ROOT_PATH . 'public' . DS .'uploads/feedback/'.$name.'.png',print_r($_POST['file'][$fkey],true),FILE_APPEND);
                //$url = uploadLocalToOss($file,"images", $filename);

              $result = uploadLocalToOss(ROOT_PATH . 'public' . DS .'uploads/feedback/'.$name.'.png','images',$name.'.png');


              $str .= $result.',';

            }
        }
        $str = trim($str,',');

        return json_encode(['code'=>0,'filepath'=>$str,'msg'=>'上传成功']);

    }*/

    /*
        * APP 反馈问题图片上传 调用接口
        * @return false|mixed|string
        */
    public function uploadImage(){
        $file = request()->file('file');
        //var_dump($file);exit;
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            $filename = $info->getSaveName();
            $file = 'uploads/'. $info->getSaveName();
            $url = uploadLocalToOss($file,"images", $filename);
            //var_dump($url);exit;
            unlink($file);
            return json_encode(['code'=>0,'filepath'=>$url,'msg'=>'上传成功']);
        }

    }


    /**
     * 处理上传的图片
     * @author Steed
     * @param $img
     * @return string
     */
    private function disposeImg($img) {
        $tmp = [];
        foreach ($img as $value) {
            $tmp[] = $this->systeminfo['cosurl'].$value['savepath'] . $value['savename'];
        }
        return implode(',',$tmp);
    }
}
