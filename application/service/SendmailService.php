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




/**
 * 地区服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class SendmailService
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
     * 发送邮箱
     * @return stdClass
     */
    public static  function sendmail() {

        $smtpserver = "ssl://smtp.163.com";//SMTP服务器
        $smtpserverport =465;//SMTP服务器端口
        $smtpusermail = "lpc3914@163.com";//SMTP服务器的用户邮箱
        $smtpemailto = "lpc3913@163.com";//发送给谁
        $smtpuser = "lpc3914@163.com";//SMTP服务器的用户帐号，注：部分邮箱只需@前面的用户名
        $smtppass = "XNPGDVUVOMYHIDKE";//SMTP服务器的用户密码
        $mailtitle = "测试";//邮件主题
        $mailcontent = "<h3>1111111</h3>";//邮件内容
        $mailtype = "TXT";//邮件格式（HTML/TXT）,TXT为文本邮件
        //************************ 配置信息 ****************************
        $smtp = new \Smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
        $smtp->debug = false;//是否显示发送的调试信息
        $state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailtitle, $mailcontent, $mailtype);
        if($state==""){

        }

    }


}
?>
