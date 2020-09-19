<?php
namespace app\index\controller;

use app\index\controller\Base;
use think\Url;
use think\Config;
use Qcloud\Cos\Client;
use think\Loader;
class Qrcode extends Base
{
    /*
     * 生成会议二维码
     * @param int $id 会议id
     */
    public function meetingQrcode($id = 0){
        $match_model = new \app\index\model\Match();
        $system_model = new \app\index\model\System();
        $files = $match_model->where (['id' => $id])->column ( 'qrcode' )[0];
        if (!empty ( $files )) {
            return [
                'code' => 1,
                'msg' => '获取二维码成功',
                'data' =>$files
            ];
        }else{
            $wxinfo = $system_model->field('appid,appsecret')->find();
            $access_token = $this->AccessToken($wxinfo);
            $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $access_token;
            $scene = $id; //  签到
            $width = 60;
            $page = 'pages/index/index';
            $auto_color = 'false'; // 默认值是false，自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调；
            $line_color = '{"r":"0","g":"0","b":"0"}'; //auth_color 为 false 时生效，使用 rgb 设置颜色 例如 {"r":"xxx","g":"xxx","b":"xxx"}
            $data = '{"scene":"' . $scene . '","page":"' . $page . '","width":' . $width . ',"auto_color":' . $auto_color . ',"line_color":' . $line_color . '}';
            $return = $this->request_post($url, $data);

            $filename = time().'.jpg';
            $path_info = 'qrcode/';
            $path = $path_info.$filename;
            file_put_contents($path, $return);
            $cosimg = $this->uploadLocalCos($path,'qrcode',$filename);
            if ($match_model->where (['id' => $id])->update (['qrcode' => $cosimg]) !== false) {
                return [
                    'code' => 1,
                    'msg' => '生成二维码成功',
                    'data' => $cosimg
                ];
            }
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

    /*
    * 获取小程序的access_token：
    * @param $wxinfo
    * @return mixed
    */
    public function AccessToken($wxinfo) {
        $appid = $wxinfo['appid'];
        $appsecret = $wxinfo['appsecret'];
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
        $data = '{"name":sucaihuo}';
        $token_request = $this->request_post($url, $data);
        $tokens = json_decode($token_request, true);
        $access_token = $tokens['access_token'];
        return $access_token;
    }

    /*
     * 远程请求
     * @param $url 请求url
     * @param $data 请求数据
     * @return bool|mixed
     */
    public function request_post($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        } else {
            return $tmpInfo;
        }
    }
}
