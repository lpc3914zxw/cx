<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2019 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\service;


use TencentCloud\Captcha\V20190722\CaptchaClient;
use TencentCloud\Captcha\V20190722\Models\DescribeCaptchaResultRequest;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Sms\V20190711\Models\SendSmsRequest;
use TencentCloud\Sms\V20190711\SmsClient;



/**
 * 地区服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class SmsService
{
    protected $secretId;
    protected $secretKey;
    protected $CaptchaAppId;
    protected $AppSecretKey;
    function __construct()
    {

        $this->secretId = config('tx.secretId');
        $this->secretKey = config('tx.secretKey');
        $this->CaptchaAppId = config('tx.CaptchaAppId');
        $this->AppSecretKey = config('tx.AppSecretKey');
    }

    /**
     * 发送短信
     * @return stdClass
     */
    public  function sendSms($phone,$content,$ty) {
             $secretId=$this->secretId;
             $secretKey=$this->secretKey;
             $content=strval($content);
            $cred = new Credential($secretId,$secretKey);
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("sms.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new SmsClient($cred, "", $clientProfile);

            $req = new SendSmsRequest();
            if($ty==0){
                $template='744962';
            }elseif ($ty==1){
                $template='744965';
            }elseif ($ty==2){
                $template='744966';
            }else{
                return returnjson(1001, '', '无效参数');
            }

        $params = array(
            "PhoneNumberSet" => array( "86".$phone ),
            "TemplateID" => $template,
            "Sign" => "财学帮",
            "TemplateParamSet" => array($content ),
            "SmsSdkAppid" => "1400436054"
        );
            $req->fromJsonString(json_encode($params));

            $resp = $client->SendSms($req);

            return $resp;

    }

    /**
     * 滑动验证码结果
     * @return stdClass
     */
    public  function verify($ticket,$ip,$randstr) {
        $secretId=$this->secretId;
        $secretKey=$this->secretKey;
        $CaptchaAppId=$this->CaptchaAppId;
        $AppSecretKey=$this->AppSecretKey;
        $cred = new Credential($secretId,$secretKey);
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint("captcha.tencentcloudapi.com");

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $client = new CaptchaClient($cred, "", $clientProfile);

        $req = new DescribeCaptchaResultRequest();
        $params = array(
            "CaptchaType" => 9,
            "Ticket" => $ticket,
            "UserIp" => $ip,
            "Randstr" => $randstr,
            "CaptchaAppId" => $CaptchaAppId,
            "AppSecretKey" => $AppSecretKey
        );
        //var_dump(json_encode($params));exit;
        $req->fromJsonString(json_encode($params));
        $resp = $client->DescribeCaptchaResult($req);
        return $resp;
    }


}
?>
