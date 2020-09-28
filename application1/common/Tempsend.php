<?php
/**
 * Created by PhpStorm.
 * User: xiaomage
 * Date: 2018/9/12
 * Time: 12:44
 * 微信模板消息推送
 */

namespace app\common;
use think\Controller;
use app\common\Factory;

class Tempsend extends Controller
{
    /**
     * 发送模板消息
     * @param string $formwork 填充模板的信息
     * @return mixed
     */
    public function sendTempMessage($formwork = ''){
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=" . $access_token . "";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formwork);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * 获取token
     * @return mixed
     */
    protected function getAccessToken(){
        $wxset_model = Factory::driver('Appset');
        $wxinfo = $wxset_model->find();
        $appid = $wxinfo['appid'];
        $secret = $wxinfo['appsecret'];
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $secret . "";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($data, true);
        return $data['access_token'];
    }

}