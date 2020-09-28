<?php
namespace app\wxapp\controller;
use think\Controller;
use think\Log;
use wxpay\Jsapi;
use wxpay\WxPayConfig;
use think\Loader;
use aop\AopClient;
use aop\request\AlipayTradeAppPayRequest;
Loader::import('wxpay.lib.WxPay', EXTEND_PATH, '.Api.php');
class Pay extends Base {
    public $mchid = '';
    public $appid = '';
    public $key   = '';
    public $appsecret = '';
    public function pay(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }

        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        //$res=Log::check();
        //var_dump($res);exit;
        Log::record('获取预支付订单失败','error');
        //return returnjson(1001,'','太晚了，早点休息哦');
        //if( isset(input('terminal')) &&input('terminal')==1){
 /*       $type = input('type');
        $out_trade_no = 'ORDER'.time();
        if($type==1){
            $wxOrderData = new  \WxPayUnifiedOrder();
            $wxOrderData->SetOut_trade_no($out_trade_no);
            $wxOrderData->SetTrade_type('JSAPI');
            $wxOrderData->SetTotal_fee(1 * 100);
            $wxOrderData->SetBody('才学');
            $wxOrderData->SetTrade_type("APP");
            $wxOrderData->SetNotify_url('https://'.$_SERVER['SERVER_NAME'].'/wxapp/paynotice/index');
            //var_dump($wxOrderData);exit;
            $wxOrder = \WxPayApi::unifiedOrder($wxOrderData);
            // 失败时不会返回result_code
            if($wxOrder['return_code'] != 'SUCCESS' || $wxOrder['result_code'] !='SUCCESS'){
                Log::record($wxOrder,'error');
                Log::record('获取预支付订单失败','error');
//            throw new Exception('获取预支付订单失败');
            }
            //var_dump($wxOrder);exit;
            //$this->recordPreOrder($wxOrder);
            $signature = $this->sign($wxOrder);
            $signature = json_encode($signature);
            return returnjson(1000,$signature,'获取成功');
        }else if($type ==2){
                // 订单号
                $order_number = $out_trade_no;
                // 获取金额
                $money = '0.1';
                // 名称
				$body='aaa';
                // 异步回调地址，这个后面不能带参数的
                $notify_url = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/paynotice/index';

                // 可根据具体需要写出具体需求 可创建支付订单 判断支付成功调用支付方法

                // 调用支付宝支付
                $str = $this->alipay($body, $money, $order_number , $notify_url );
                // 这里要注意json会将支付返回的字符串进行转义，所以必须加上htmlspecialchars_decode避免转义，这个需要注意的！！！
                return json(['status' => 200, 'data' => htmlspecialchars_decode($str)]);
            }*/


        }





    private function alipay($body, $total_amount, $product_code, $notify_url)
    {
        $aop = new AopClient();
        $aop->gatewayUrl            = config('alipay.gatewayUrl');
        $aop->appId                 = config('alipay.appId');
        $aop->rsaPrivateKey         = config('alipay.rsaPrivateKey');
        $aop->format                = config('alipay.format');
        $aop->charset               = config('alipay.charset');
        $aop->signType              = config('alipay.signType');
        $aop->alipayrsaPublicKey    = config('alipay.alipayrsaPublicKey');
        $request = new AlipayTradeAppPayRequest();
        $arr['body']                = $body;
        $arr['subject']             = $body;
        $arr['out_trade_no']        = $product_code;
        $arr['timeout_express']     = '30m';
        $arr['total_amount']        = floatval($total_amount);
        $arr['product_code']        = 'QUICK_MSECURITY_PAY';

        $json = json_encode($arr);
        $request->setNotifyUrl($notify_url);
        $request->setBizContent($json);
        $response = $aop->sdkExecute($request);
        return htmlspecialchars($response);
    }
    // 签名
    private function sign($wxOrder)
    {
        $jsApiPayData = new \WxPayJsApiPay();
        $jsApiPayData->SetAppid("wx426b3015555a46be");
        $jsApiPayData->SetTimeStamp((string)time());
        $rand = md5(time() . mt_rand(0, 1000));
        $jsApiPayData->SetNonceStr($rand);
        $jsApiPayData->SetPackage('Sign=WXPay');
        $jsApiPayData->SetSignType('md5');
        $sign = $jsApiPayData->MakeSign();
        $rawValues = $jsApiPayData->GetValues();
        $rawValues['paySign'] = $sign;
        $rawValues['prepay_id'] = $wxOrder['prepay_id'];
        unset($rawValues['appId']);
        return $rawValues;
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

