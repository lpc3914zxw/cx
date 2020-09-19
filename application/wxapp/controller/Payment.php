<?php
namespace app\wxapp\controller;
use app\common\Common;
use app\wxapp\model\Collection;
use app\wxapp\model\Colliers;
use app\wxapp\model\CourseLearnLog;
use app\wxapp\model\CreditSource;
use app\wxapp\model\TeacherFollow;
use app\wxapp\model\Teachers;
use app\wxapp\model\Tutor;
use app\wxapp\model\TutorFollow;
use phpqrcode\QRcode;
use think\Controller;
use app\wxapp\controller\Base;
use app\wxapp\model\Level;
use think\Request;
use think\Cache;
use think\Loader;
use app\index\model\Sectiones;
use app\wxapp\model\Orders;
use app\index\model\Course;
use think\helper\Time;
use think\Db;
use aop\AopClient;
use aop\request\AlipayTradeAppPayRequest;

class Payment extends Base{

  	public $mchid = '';
    public $appid = '';
    public $key   = '';
    public $appsecret = '';

    /*
     * 我的页面
     * @return \type
     */
    public function myInfo() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $user = new \app\wxapp\model\User();
        $userInfo = $user->where('id',$this->uid)->field('id,name,headimg,student_no,level,signature,score,wechat,tel,is_auth')->find();
        if($userInfo){   //is_auth 0未认证 1已提交审核 2已认证 3认证驳回
            $level = new level();
            $mylevel = $level->where('value',$userInfo)->field('name')->find();
            if(empty($userInfo['signature'])){
                $userInfo['signature'] = '';
            }
            $userInfo['levelname'] = $mylevel['name'];
            return returnjson(1000,$userInfo,'获取成功');
        }
        return returnjson(1001,'','获取失败');
    }
    public function face_pay(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }

        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆');
        }
      //return returnjson(1001,'','太晚了，早点休息哦');
      	//if( isset(input('terminal')) &&input('terminal')==1){
      		$type = input('type');
      		$out_trade_no = 'FACE'.time();
      		if($type==1){
            	$aop = new \aop\AopClient();
                $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
                $aop->appId = '2021001163647643';
                $aop->rsaPrivateKey = 'MIIEogIBAAKCAQEAisEWrsiMrsk5Jt9txQ88fFD/8eRaXwO07+FFu89aFE01irKNAWT7rpLsiexC3VZGsyj7pBQ9QNTI8+xhylRQQwNdxNJpr3c7fFhjw6jqIoA05g8lsjyIdtXMI409hpc/Z58hZccppYxvjp3J+8DgjqgMbFTLH+WZm8fm8ry8uIfZD8OjY4pKs9d8uY8XtAE9qPNGhMk3D7ixK4dYSqRJZKucWT9rtD/4yQ+n/DiNC/rXfkVmc/hx369XOGNGZDqYbkl77uSTD9YSwQmUacwp9ZKiCIUoCJgjW0jUFilc60Bt3kKxkySy0gwAT4JkizVrBZrpsc2MwVplxTHfDzWb5QIDAQABAoIBACV0iAX4dxl7kiTvLTeDrGU2jFCkvsxlOMOEQQm4qG1QhkKBflTBCLuQR6/Xihkrf/w+9ObO29YTWoeV6LPwXJYqY3aNAiBuhC1FwvB/OWzExQLRVfCuLHw/rFJwfwpE68WyDBboU1Kv/TE3YN3HrBZ2QJxBq29Z0ERgCvS9hAgfpejfMwzAfmAQaHIKRacTDqJlBfkyGl7d2BtT4h1/r5CzlmZ31qkHvq38kxahg6zQLWJpFVCOrAoyIL2cDWeANPoa44Bl7OBraH+zoKhmXnsYRuEPxuJaQ9GRo9ErTxYxZOJklyrfuscr8X7c6YT9A7tukBbXiJP8T6FUW7RcbgUCgYEAwkRbfyCfrVNTFtBwlRMicSCTHdND3E0GTXvyN6npwmsX5GNE0XDS0Am3v0k8iAmz5Yr69E+Y06hdNoApTCNxDIP5uFCOa7q6qxvLKZaxeNdTChM1TltMgEqB3FnpXijPsauoDwTuJ1RtM4mYhz4+3IIE7O2W7xahF5moHbNLE/sCgYEAttjC+ZaT61noQKgfBWxna6F8tEYlz6tS2sqKnYhoB3umvvpyZzLLNUEC1cTCmH0mQMrWr0zZGtbQUArbVuLVyCM2SXtxEKbnyg8Ft1SaAZjckkfmmy0n5FwEk/31lVzVyFALf3FrAYdzLP28P1rnswvAEyz1Ds4mSg0XkSlTKZ8CgYAyB8TAkrhMvP7TC09TNSBTnh4FOllprPSIk/knWLz48veuO9qHTdUc+sO1objTGByaaxaCQNWM/Pk0hgEcuKvumzZ+v1BOckKMupWx2jtOcbXTDGtYCK9FAus6wnUVaNFEYn7fj1d+DYIqGa+MdP1fcKSwF+gdHujR2SKws3IMbQKBgFChXbl6cVhDmWuJt8RIfYK0/6zvkhT109+vmVjGojlKicmF35UqjPm65WknDzj3VzsTN4CuPr7bI5locDjsZqGPBY155e4V6/jqjva9U/yIUBwhoMulKgZFna81OmrXOV7QDYHxneJavKuGaND3YV1PPTA3jwksVy4of8//jTC5AoGAZvYq9iH1EEqWPprZHWNPTgfKoOHR1KB2HW9j2zHiwBVZXyVHxQ3zlz9y1Kh4B2/cKdO2neL7Cv2N3VIHn8b5GWiG3eq1sTq4bFxlAMscV3sefABKiNKMpwNxyyTPTgi5+MSvxD9Jc5jU+5GeMsk3V4RU1yS1gccGZ7AYdtf/nBw=';
                $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbPyffwy3iPQz0dodFKZZXRF5b6PMlAOhOrzuMixuSlN8qe3bLpE1CHMaVvgQZcYOzJzqd0d0mi69IPR8A1tZkCqF/jX/zbNpKDc4n3mPy7mA9gjfv2qJVpsysnaVSTcnKUkq8BD+MHGKq28LTF7GDts67glr7fni0LgV5NueLy3+BW6BFhG4cCNCD+Zq0hSQffrQnnk8wDha9kfrhsKZvyCN8wCCeOEGciQY3+9CR7u3jI4murUXdZtf2b1NV0qFCbivkDZoHEurRmDnkcMVrVTWVXMfxxfbxsruHRtHTAEb37tzp0DnJKfPRqaVuTN64is64/ywBWrUaanjmX89QIDAQAB';
                $aop->apiVersion = '1.0';
                $aop->signType = 'RSA2';
                $aop->postCharset = 'utf-8';
                $aop->format = 'json';
                //$aop->notify_url = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/paynotice/index';
                $request = new AlipayTradeAppPayRequest ();

                $param['out_trade_no'] = $out_trade_no;
                $param['total_amount'] = 0.01;
                $param['subject'] = '实名认证付费';
                $param['notify_url'] = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/paynotice/index';
                $param['body'] = 'face';

                $rpar = json_encode($param);

                $request->setBizContent($rpar);
                $result = $aop->sdkExecute($request);
                unset($param['notify_url']);
                $param['addtime'] = time();
                $param['uid'] = $this->uid;
                unset($param['body']);
                Db::name('face_order')->insert($param);

                return returnjson(1000,$result,'获取成功');
            }else if($type ==2){

				$nonce_str = $this->getRandom(32);
              $body = '实名支付费用';
              $url = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/paynotice/wxcallbacl';
              $pay_params['appid'] = 'wxdc7673f0ef6116c6';
              	$pay_params['mch_id'] = '1589016531';
              $pay_params['nonce_str'] = $nonce_str;
              $pay_params['body'] = $body;
              $pay_params['out_trade_no'] = $out_trade_no;
              $pay_params['total_fee'] = 1;
              $pay_params['spbill_create_ip'] = $this->get_real_ip();
              $pay_params['notify_url'] = $url;
              $pay_params['trade_type'] = 'APP';
              $pay_params['sign'] = $this->createSign($pay_params,'eo6j0FMBawB7UYAntDg76FbKmfo0BNM0');
              $paypr = $this->createXml($pay_params);
              $res = json_post('https://api.mch.weixin.qq.com/pay/unifiedorder',$paypr);
              $resarray = $this->xmltoarray($res);
              if($resarray['return_code'] == 'SUCCESS'){

                	$signParam = array(
                        'appid' => $resarray['appid'],
                        'partnerid' => $resarray['mch_id'],
                        'prepayid' => $resarray['prepay_id'],
                        'package' => 'Sign=WXPay',
                        'noncestr' => $this->getRandom(32),
                        'timestamp' => time(),
                    );

                    $signParam['sign'] = $this->createSign($signParam, 'eo6j0FMBawB7UYAntDg76FbKmfo0BNM0');

              }else{
              	return returnjson(1001,'','获取失败');
              }
              $param['out_trade_no'] = $out_trade_no;
                $param['total_amount'] = $pay_params['total_fee']/100;
                $param['subject'] = '实名认证付费';

                $param['addtime'] = time();
                $param['uid'] = $this->uid;

                Db::name('face_order')->insert($param);
              $signParam['out_trade_no'] = $param['out_trade_no'];
              $signParam = json_encode($signParam);
			return returnjson(1000,$signParam,'获取成功');

            }

        //}
        $pay_type = 1;
        if($pay_type == 1){
           	$aop = new \aop\AopClient();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
            $aop->appId = '2021001163647643';
            $aop->rsaPrivateKey = 'MIIEogIBAAKCAQEAisEWrsiMrsk5Jt9txQ88fFD/8eRaXwO07+FFu89aFE01irKNAWT7rpLsiexC3VZGsyj7pBQ9QNTI8+xhylRQQwNdxNJpr3c7fFhjw6jqIoA05g8lsjyIdtXMI409hpc/Z58hZccppYxvjp3J+8DgjqgMbFTLH+WZm8fm8ry8uIfZD8OjY4pKs9d8uY8XtAE9qPNGhMk3D7ixK4dYSqRJZKucWT9rtD/4yQ+n/DiNC/rXfkVmc/hx369XOGNGZDqYbkl77uSTD9YSwQmUacwp9ZKiCIUoCJgjW0jUFilc60Bt3kKxkySy0gwAT4JkizVrBZrpsc2MwVplxTHfDzWb5QIDAQABAoIBACV0iAX4dxl7kiTvLTeDrGU2jFCkvsxlOMOEQQm4qG1QhkKBflTBCLuQR6/Xihkrf/w+9ObO29YTWoeV6LPwXJYqY3aNAiBuhC1FwvB/OWzExQLRVfCuLHw/rFJwfwpE68WyDBboU1Kv/TE3YN3HrBZ2QJxBq29Z0ERgCvS9hAgfpejfMwzAfmAQaHIKRacTDqJlBfkyGl7d2BtT4h1/r5CzlmZ31qkHvq38kxahg6zQLWJpFVCOrAoyIL2cDWeANPoa44Bl7OBraH+zoKhmXnsYRuEPxuJaQ9GRo9ErTxYxZOJklyrfuscr8X7c6YT9A7tukBbXiJP8T6FUW7RcbgUCgYEAwkRbfyCfrVNTFtBwlRMicSCTHdND3E0GTXvyN6npwmsX5GNE0XDS0Am3v0k8iAmz5Yr69E+Y06hdNoApTCNxDIP5uFCOa7q6qxvLKZaxeNdTChM1TltMgEqB3FnpXijPsauoDwTuJ1RtM4mYhz4+3IIE7O2W7xahF5moHbNLE/sCgYEAttjC+ZaT61noQKgfBWxna6F8tEYlz6tS2sqKnYhoB3umvvpyZzLLNUEC1cTCmH0mQMrWr0zZGtbQUArbVuLVyCM2SXtxEKbnyg8Ft1SaAZjckkfmmy0n5FwEk/31lVzVyFALf3FrAYdzLP28P1rnswvAEyz1Ds4mSg0XkSlTKZ8CgYAyB8TAkrhMvP7TC09TNSBTnh4FOllprPSIk/knWLz48veuO9qHTdUc+sO1objTGByaaxaCQNWM/Pk0hgEcuKvumzZ+v1BOckKMupWx2jtOcbXTDGtYCK9FAus6wnUVaNFEYn7fj1d+DYIqGa+MdP1fcKSwF+gdHujR2SKws3IMbQKBgFChXbl6cVhDmWuJt8RIfYK0/6zvkhT109+vmVjGojlKicmF35UqjPm65WknDzj3VzsTN4CuPr7bI5locDjsZqGPBY155e4V6/jqjva9U/yIUBwhoMulKgZFna81OmrXOV7QDYHxneJavKuGaND3YV1PPTA3jwksVy4of8//jTC5AoGAZvYq9iH1EEqWPprZHWNPTgfKoOHR1KB2HW9j2zHiwBVZXyVHxQ3zlz9y1Kh4B2/cKdO2neL7Cv2N3VIHn8b5GWiG3eq1sTq4bFxlAMscV3sefABKiNKMpwNxyyTPTgi5+MSvxD9Jc5jU+5GeMsk3V4RU1yS1gccGZ7AYdtf/nBw=';
            $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbPyffwy3iPQz0dodFKZZXRF5b6PMlAOhOrzuMixuSlN8qe3bLpE1CHMaVvgQZcYOzJzqd0d0mi69IPR8A1tZkCqF/jX/zbNpKDc4n3mPy7mA9gjfv2qJVpsysnaVSTcnKUkq8BD+MHGKq28LTF7GDts67glr7fni0LgV5NueLy3+BW6BFhG4cCNCD+Zq0hSQffrQnnk8wDha9kfrhsKZvyCN8wCCeOEGciQY3+9CR7u3jI4murUXdZtf2b1NV0qFCbivkDZoHEurRmDnkcMVrVTWVXMfxxfbxsruHRtHTAEb37tzp0DnJKfPRqaVuTN64is64/ywBWrUaanjmX89QIDAQAB';
            $aop->apiVersion = '1.0';
            $aop->signType = 'RSA2';
            $aop->postCharset = 'utf-8';
            $aop->format = 'json';
            //$aop->notify_url = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/paynotice/index';
            $request = new AlipayTradeAppPayRequest ();
            $out_trade_no = 'FACE'.time();
            $param['out_trade_no'] = $out_trade_no;
            $param['total_amount'] = 2;
            $param['subject'] = '实名认证付费';
            $param['notify_url'] = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/paynotice/index';
            $param['body'] = 'face';
            $rpar = json_encode($param);
             $request->setBizContent($rpar);
            $result = $aop->sdkExecute($request);
            unset($param['notify_url']);
            $param['addtime'] = time();
            $param['uid'] = $this->uid;
            unset($param['body']);
            Db::name('face_order')->insert($param);

            return returnjson(1000,$result,'获取成功');

        }else if($pay_type == 2){
            // 加载基础 adapay 基础类
            // SDK 初始化文件加载
            include_once  "../extend/adapay/AdapaySdk/init.php";
            // 在文件中设置 DEBUG 为 true 时， 则可以打印日志到指定的日志目录下 LOG_DIR
            include_once  "../extend/adapay/AdapayDemo/config.php";
            \AdaPay\AdaPay::init("../extend/adapay/AdapayDemo/config/config.json", "live");
            $payment = new \AdaPaySdk\Payment();
            $type = input('type');

            if($type == 1){
                $pay_channel = 'alipay';
            }else if($type == 2){
                $pay_channel = 'wx_lite';
            }

            $out_trade_no = 'FACE'.time();
            # 支付设置
            $payment_params = array(
                "app_id"=> "app_947f38cf-c5d2-462e-8d2e-8fcb54dff91b",
                "order_no"=> $out_trade_no,
                "pay_channel"=> $pay_channel,
                "time_expire"=> date("YmdHis",time()+7200),
                "pay_amt"=>"2.00",
                "goods_title"=> "subject",
                "goods_desc"=> "实名认证",
                "description"=> "description"
            );

            # 发起支付
            $payment->create($payment_params);

            # 对支付结果进行处理
            if ($payment->isError()){
                //失败处理
                //echo "<per>";
                $result= $payment->result;
                //print_r($payment->result);

                 return returnjson(1000,json_encode($result),'获取失败');
            } else {
                //成功处理

                $param['out_trade_no'] = $out_trade_no;
                $param['total_amount'] = 2;
                $param['subject'] = '实名认证付费';


                $param['addtime'] = time();
                $param['uid'] = $this->uid;

                Db::name('face_order')->insert($param);
                $result= $payment->result;

                return returnjson(1000,json_encode($result),'获取成功');

            }
            exit;
        }




    }
    function  echoExecuteResult($obj, $funcName){
        //print_r($funcName."接口调用开始:\n");
        # 对进件结果进行处理
        if ($obj->isError()){
            print_r($funcName."失败结果::".json_encode($obj->result, JSON_UNESCAPED_UNICODE)."\n");
            //失败处理
        } else {
            //成功处理
            print_r($funcName."接口调用成功! \n");
        }
        //print_r($funcName."接口调用结束:\n\n");
    }

    function callback($documentRoot){
        $result = input('');
        file_put_contents($documentRoot.'/log_6666.txt',print_r($result,true),FILE_APPEND);
                 return returnjson(1000,json_encode($result),'获取失败');
    }

  //获取32位随机数
  	function getRandom($param){
        $str="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $key = "";
        for($i=0;$i<$param;$i++)
         {
             $key .= $str{mt_rand(0,32)};    //生成php随机数
         }
         return $key;
     }
  	function get_real_ip()
	{
		$ip=false;
		$clientip = "clientip";
		$xrealip = "xrealip";
		$userip ='userip';
		$fwip ='fwip';
		$aliip = 'aliip';
		$forwardedip ='forwardedip';
		if(isset($_SERVER["HTTP_CLIENT_IP"]) && !empty($_SERVER['HTTP_CLIENT_IP']) && !preg_match("/^(10|172.16|192.168)./i",$_SERVER['HTTP_CLIENT_IP']) && $_SERVER["HTTP_CLIENT_IP"]!='118.26.171.83' && $_SERVER["HTTP_CLIENT_IP"]!='118.26.171.84')
		{
			$ip = $_SERVER["HTTP_CLIENT_IP"];
			$clientip = $_SERVER["HTTP_CLIENT_IP"];
		}
		else if(isset($_SERVER["HTTP_X_REAL_IP"]) && !empty($_SERVER['HTTP_X_REAL_IP']) && !preg_match("/^(10|172.16|192.168)./i",$_SERVER['HTTP_X_REAL_IP']) && $_SERVER["HTTP_X_REAL_IP"]!='118.26.171.83' && $_SERVER["HTTP_X_REAL_IP"]!='118.26.171.84')
		{
			$ip = $_SERVER['HTTP_X_REAL_IP'];
			$xrealip = $_SERVER["HTTP_X_REAL_IP"];
		}
		else if(isset($_SERVER["HTTP_USER_IP"]) && !empty($_SERVER['HTTP_USER_IP']) && !preg_match("/^(10|172.16|192.168)./i",$_SERVER['HTTP_USER_IP']) && $_SERVER["HTTP_USER_IP"]!='118.26.171.83' && $_SERVER["HTTP_USER_IP"]!='118.26.171.84')
		{
			$ip = $_SERVER['HTTP_USER_IP'];
			$userip =$_SERVER['HTTP_USER_IP'];
		}
		else if(isset($_SERVER["HTTP_FW_ADDR"]) && !empty($_SERVER['HTTP_FW_ADDR']) && !preg_match("/^(10|172.16|192.168)./i",$_SERVER['HTTP_FW_ADDR']) && $_SERVER["HTTP_FW_ADDR"]!='118.26.171.83' && $_SERVER["HTTP_FW_ADDR"]!='118.26.171.84')
		{
			$ip = $_SERVER['HTTP_FW_ADDR'];
			$fwip = $_SERVER["HTTP_FW_ADDR"];
		}
		else if(isset($_SERVER["HTTP_ALI_CDN_REAL_IP"]) && !empty($_SERVER['HTTP_ALI_CDN_REAL_IP']) && !preg_match("/^(10|172.16|192.168)./i",$_SERVER['HTTP_ALI_CDN_REAL_IP']) && $_SERVER["HTTP_ALI_CDN_REAL_IP"]!='118.26.171.83' && $_SERVER["HTTP_ALI_CDN_REAL_IP"]!='118.26.171.84')
		{
			$ip = $_SERVER['HTTP_ALI_CDN_REAL_IP'];
			$aliip = $_SERVER["HTTP_ALI_CDN_REAL_IP"];
		}
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		{
			$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
			if ($ip)
			{
			array_push($ips, $ip);
				$ip = FALSE;
			}
			for ($i = 0; $i < count($ips); $i++)
			{
				if (isset($ips[$i]) && !preg_match("/^(10|172.16|192.168)./i", $ips[$i]) && $ips[$i]!='118.26.171.83' && $ips[$i]!='118.26.171.84')
				{
					$ip = $ips[$i];
					break;
				}
			}
			$forwardedip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
	}
  	/**
     * 生成签名
     * @author Steed
     * @param array $data
     * @param $key
     * @return string
     */
    private function createSign($data = [], $key) {
        //按ASCII字典序排序
        ksort($data);
        $str = '';
        foreach ($data as $k => $val) {
            $str .= $k . '=' . $val . '&';
        }
        $str .= 'key=' . $key;
        return strtoupper(md5($str));
    }

  /**
     * 组装xml
     * @author Steed
     * @param array $data
     * @return string
     */
    private function createXml($data = []) {
        $str = '<xml>';
        foreach ($data as $key => $value) {
            $str .= '<' . $key . '>' . $value . '</' . $key . '>';
        }
        $str .= '</xml>';
        return $str;
    }




    // Xml 转 数组, 不包括根键
     private function xmltoarray( $xml )
    {
        $arr = $this->xml_to_array($xml);
        $key = array_keys($arr);
        $data = $arr[$key[0]];
        foreach ($data as $k=>$value){
            $data[$k] = str_replace(array('<![CDATA[',']]>'), '', $value);
        }
        return $data;
    }

      function xml_to_array( $xml )
    {
        $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches))
        {
            $count = count($matches[0]);
            $arr = array();
            for($i = 0; $i < $count; $i++)
            {
                $key= $matches[1][$i];
                $val = $this->xml_to_array( $matches[2][$i] );  // 递归
                if(array_key_exists($key, $arr))
                {
                    if(is_array($arr[$key]))
                    {
                        if(!array_key_exists(0,$arr[$key]))
                        {
                            $arr[$key] = array($arr[$key]);
                        }
                    }else{
                        $arr[$key] = array($arr[$key]);
                    }
                    $arr[$key][] = $val;
                }else{
                    $arr[$key] = $val;
                }
            }
            return $arr;
        }else{
            return $xml;
        }
    }

}
