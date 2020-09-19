<?php
namespace app\index\controller;
use think\Controller;
use think\Loader;
use think\Config;
use Qcloud\Cos\Client;
/**
 * 文件上传
 * Class Upload
 * @author Steed
 * @package app\index\controller
 */
class Upload extends Controller {
    private $config = [];
    protected $systeminfo = [];
    public function __construct() {
        parent::__construct();
        $this->config = json_decode(preg_replace('/\/\*[\s\S]+?\*\//', '', file_get_contents('./static/ueditor/php/config.json')), true);
        $this->getSystemInfo();
    }

    /*
    *获取系统信息
    */
    public function getSystemInfo(){
        $system_model = new \app\index\model\System();
        $systeminfo = $system_model->find();
        $this->systeminfo = $systeminfo;
        Config::set('sysinfo',$systeminfo);
    }

    /**
     * ueditor图片上传
     * @author Steed
     */
    public function ueditor($action = '') {
        switch ($action) {
            case 'config':
                $result =  json_encode($this->config);
                break;
            /* 上传图片 */
            case 'uploadimage':
                /* 上传涂鸦 */
            case 'uploadscrawl':
                /* 上传视频 */
            case 'uploadvideo':
                /* 上传文件 */
            case 'uploadfile':
                $result = $this->ueditor_image();
                break;
            /* 列出图片 */
            case 'listimage':
                $result = include("action_list.php");
                break;
            /* 列出文件 */
            case 'listfile':
                $result = include("action_list.php");
                break;
            /* 抓取远程文件 */
            case 'catchimage':
                $result = include("action_crawler.php");
                break;
            default:
                $result = json_encode(['state' => '请求地址出错']);
                break;
        }
        /* 输出结果 */
        if ($this->request->has('callback')) {
            if (preg_match("/^[\w_]+$/", $this->request->param('callback'))) {
                return htmlspecialchars($this->request->param('callback')) . '(' . $result . ')';
            } else {
                return json_encode(['state'=> 'callback参数不合法']);
            }
        }
        return $result;
    }

    /**
     * 上传图片
     * @author Steed
     */
    private function ueditor_image() {
        $info = uploadOss($this->request->file(), '/news');
        false === $info['status'] && $this->out_msg($info['msg']);
        $this->out_msg('SUCCESS',
            $info['data']['upfile']['savepath'] . $info['data']['upfile']['savename'],
            $info['data']['upfile']['savename'], $info['data']['upfile']['name'],
            $info['data']['upfile']['ext'], $info['data']['upfile']['size']);
    }

    /*
     * 公共上传图片
     */
    public function uploadImg() {
        if (!empty($this->request->file('logo'))) {
            $info = uploadOss($this->request->file('logo'), '/caixue');
            if (false === $info['status']) {
                $this->error($info['msg']);
            }
            $url = $this->systeminfo['ossurl'].$info['data']['savepath'];
            return json_encode(['code'=>0,'msg'=>'上传成功','data'=>$url]);
        }
    }

    /*
     * 上传头像图片
     */
    public function uploadHeadImg(){
        if (!empty($this->request->file('logo'))) {
            $info = uploadOss($this->request->file('logo'), '/teacher');
            if (false === $info['status']) {
                $this->error($info['msg']);
            }
            $url = $this->systeminfo['ossurl'].$info['data']['savepath'];
            return json_encode(['code'=>0,'msg'=>'上传成功','data'=>$url]);
        }
    }

    /*
     * 上传图片
     */
    public function uploadImgurl(){
        if (!empty($this->request->file('imgurl'))) {
            $info = uploadOss($this->request->file('imgurl'), '/images');
            if (false === $info['status']) {
                $this->error($info['msg']);
            }
            $url = $this->systeminfo['ossurl'].$info['data']['savepath'];
            return json_encode(['code'=>0,'msg'=>'上传成功','data'=>$url]);
        }
    }

    /*
     * 上传课程缩略图
     */
    public function uploadSmallImg() {
        if (!empty($this->request->file('samll_imgurl'))) {
            $info = uploadOss($this->request->file('samll_imgurl'), '/samllimgurl');
            if (false === $info['status']) {
                $this->error($info['msg']);
            }
            $url = $this->systeminfo['ossurl'].$info['data']['savepath'];
            return json_encode(['code'=>0,'msg'=>'上传成功','data'=>$url]);
        }
    }

    /*
   * 上传邀请背景图
   * @return false|mixed|string
   */
    public function uploadInviteImg() {
        if (!empty($this->request->file('inviteimg'))) {
            $info = uploadOss($this->request->file('inviteimg'), '/images');
            if (false === $info['status']) {
                $this->error($info['msg']);
            }
            $url = $this->systeminfo['ossurl'].$info['data']['savepath'];
            return json_encode(['code'=>0,'msg'=>'上传成功','data'=>$url]);
        }
    }

    /*
     * 上传班级顶部图
     * @return false|mixed|string
     */
    public function uploadClassImg() {
        if (!empty($this->request->file('classimg'))) {
            $info = uploadOss($this->request->file('classimg'), '/images');
            if (false === $info['status']) {
                $this->error($info['msg']);
            }
            $url = $this->systeminfo['ossurl'].$info['data']['savepath'];
            return json_encode(['code'=>0,'msg'=>'上传成功','data'=>$url]);
        }
    }

    /*
     * 上传音频文件
     */
    public function uploadAudio(){
        if (!empty($this->request->file('audio'))) {
            $info = uploadOss($this->request->file('audio'), '/audio');
            if (false === $info['status']) {
                $this->error($info['msg']);
            }
            $url = $this->systeminfo['ossurl'].$info['data']['savepath'];
            return json_encode(['code'=>0,'msg'=>'上传成功','data'=>$url]);
        }
    }
  /*
     * 上传音频文件
     */
    public function uploade(){
        if (!empty($this->request->file('excelFile'))) {
            $info = uploadOss($this->request->file('excelFile'), '/ex');
            if (false === $info['status']) {
                $this->error($info['msg']);
            }
            $url = $this->systeminfo['ossurl'].$info['data']['savepath'];
            return json_encode(['code'=>0,'msg'=>'上传成功','data'=>$url]);
        }
    }


    /*
     * 本地图片上传到COS
     * @param string $save_path  保存路径
     * @param string $rootpath 保存到COS根目录
     * @param string $filename 保存名称
     */
    public  function uploadLocalCos($save_path = '',$rootpath = '',$filename = ''){
        $fileinfo = file_get_contents($save_path);
        // 图片鉴黄要求图片最大1M.需要压缩成小雨1M的图片
        Loader::import('COS.cos-autoloader', VENDOR_PATH);
        $cosClient = new Client(array(
            'region' => $this->systeminfo['region'],
            'credentials' => array(
                'appId' => $this->systeminfo['cos_appid'],
                'secretId' => $this->systeminfo['secretid'],
                'secretKey' => $this->systeminfo['secretkey'])));

        $imagepath = $rootpath . '/';

        $result = $cosClient->upload(
            $bucket = $this->systeminfo['bucket'],
            $key = 'images/'. $imagepath . $filename,  //上传路径
            $body = $fileinfo,
            $options = array(
                'CacheControl' => 'private',
                'ServerSideEncryption' => 'AES256')
        );
        unlink($save_path);
        $filepath = $this->systeminfo['cosurl'] . '/'.$imagepath . $filename;
        return $filepath;
    }

    /**
     * @author Steed
     * @param string $state
     * @param string $url
     * @param string $title
     * @param string $original
     * @param string $type
     * @param string $size
     */
    private function out_msg($state = '', $url = '', $title = '', $original = '', $type = '', $size = '') {
        $data = ['state' => $state];
        $data['url'] = $url;
        $data['title'] = $title;
        $data['original'] = $original;
        $data['type'] = $type;
        $data['size'] = $size;
        echo json_encode($data);exit();
    }
}