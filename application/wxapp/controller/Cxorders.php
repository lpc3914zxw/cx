<?php
namespace app\wxapp\controller;
use app\common\Common;
use app\index\model\Advanced;
use app\wxapp\controller\Base;
use app\index\model\Course;
use app\wxapp\model\Colliers;
use app\wxapp\model\PayLog;
use think\Db;
use think\Log;
use think\Loader;
use aop\AopClient;
use aop\request\AlipayTradeAppPayRequest;
Loader::import('wxpay.lib.WxPay', EXTEND_PATH, '.Api.php');
class Cxorders extends Base {

    /*
     * 下单
     * @param int $course_id
     * @param string $token
     */
    public function addOrder($course_id = 0) {

        $token = input('token');
        //$course_type = input('course_type');

        if(!empty($token)) {
            $this->getUserInfo($token);
        }

        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $user_model = new \app\wxapp\model\User();
        $order_model = new \app\wxapp\model\Orders();
        $course_model = new Course();
        $advanced_model = new Advanced();
        
        // 查询当前所学进阶
        $advanced_id = $course_model->where('id',$course_id)->value('advanced_id');
        $advancedInfo = $advanced_model->field('type,studying_num,reward,value,deadline,pay_type,learn_power')->where(['id'=>$advanced_id])->find();
        $courseInfo = $course_model->field('imgurl,name,advanced_id')->where('id',$course_id)->find();

        
            $status = '5,6,7';
            if($order_model->where(['course_id'=>$course_id,'uid'=>$this->uid,'status'=>['in',($status)]])->find()){
                return returnjson(1001,'','已购买过，请勿重复购买');
            }
        





        $order_id = time().rand(1000,9999);
        $data = [
            'order_id'=>$order_id,
            'course_id'=>$course_id,
            'uid'=>$this->uid,
            'status'=>0,
            'value'=>$advancedInfo['value'],
            'effective'=>$advancedInfo['deadline'],
            'score'=>$advancedInfo['reward'],
            'advanced_id'=>$courseInfo['advanced_id'],
            'addtime'=>time(),
            'paytime'=>time(),
            'course_type'=>1
        ];
        //var_dump($data);exit;
        if(!$order_model->insert($data)) {
            return returnjson(1001,'','下单失败');
        }
        if($advancedInfo['pay_type'] == 1) {  // 1 学分支付 2 支付宝支付  3 微信支付
            $courseInfo['value'] = $advancedInfo['value']."学分";
        }else {
            $courseInfo['value'] = $advancedInfo['value']."元";
        }
        $courseInfo['deadline'] = $advancedInfo['deadline'];
        $courseInfo['order_id'] = $order_id;
        $courseInfo['score'] = $advancedInfo['value'];
        $pay_types = explode(',',$advancedInfo['pay_type']);
        $courseInfo['is_score'] = 0;
        $courseInfo['is_alipay'] = 0;
        $courseInfo['is_wxpay'] = 0;
        foreach ($pay_types as $val) {
            if($val == 1) {
                $courseInfo['is_score'] = 1;
            }else if($val == 2) {
                $courseInfo['is_alipay'] = 1;
            }else if($val == 3) {
                $courseInfo['is_wxpay'] = 1;
            }
        }
        $courseInfo['paytype'] = $pay_types;
        return returnjson(1000,$courseInfo,'下单成功');
    }

    /*
     * 确认支付
     * @param $order_id  订单号   $pay_type 支付方式   1 学分兑换  2 支付宝支付  3 微信支付
     */
    public function submitPayData($order_id = '',$pay_type = 3) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
       // $pay_type = input('pay_type');
        if($this->uid == 0) {
            return returnjson(1100,'','该设备在其他地方登录');
        }
        $order_model = new \app\wxapp\model\Orders();
        $orderInfo = $order_model->field('value,status')->where('order_id',$order_id)->find();
        //$order_model->where('order_id',$order_id)->update(['paytype'=>6]);exit;
        if($orderInfo['status'] != 0) {
            return returnjson(1001,'','该订单已支付');
        }
        $user_model = new \app\wxapp\model\User();
        $advanced_model = new Advanced();
        $course_model = new Course();

        $common = new Common();
        $course_id = $order_model->where('order_id',$order_id)->value('course_id');
        $advanced_id = $course_model->where('id',$course_id)->value('advanced_id');
        $advanced_name = $advanced_model->where('id',$advanced_id)->value('name');
        if($pay_type == 1) {   // 学分兑换
            Db::startTrans();
            if(false === $order_model->where('order_id',$order_id)->update(['pay_type'=>$pay_type,'status'=>1])) {//更新支付状态
                return returnjson(1001,'','购买失败');
            }
            //查看学分知否不足
            $userScore = $user_model->where('id',$this->uid)->value('score');
            if($orderInfo['value'] != 0) {
                if(floatval($orderInfo['value']) > floatval($userScore)) {
                    return returnjson(1001,'','学分余额不足');
                }
            }
            //更新学分
            if(floatval($orderInfo['value']) > 0) {
                if(false === $user_model->where('id',$this->uid)->setDec('score',$orderInfo['value'])) {
                    Db::rollback();
                    return returnjson(1001,'','购买失败');
                }
                $courseName = $course_model->where('id',$course_id)->value('name');
                // 荣誉值--根据条件获取荣誉值
                if(false === $common->honorLog($this->uid,1,$course_id)) {
                    Db::rollback();
                    return returnjson(1001,'','购买失败');
                }
            }
            Db::commit();
            return returnjson(1000,'','购买成功');
        }else if($pay_type == 2) {
            // 订单号
            $out_trade_no = 'ORDER'.time();
            $order_number = $out_trade_no;
            // 获取金额
            $money = '0.1';
            // 名称
            $body='order';
            // 异步回调地址，这个后面不能带参数的
            $notify_url = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/paynotice/alinotify';

            // 可根据具体需要写出具体需求 可创建支付订单 判断支付成功调用支付方法

            // 调用支付宝支付
            $str = $this->alipay($body, $money, $order_number , $notify_url );
            // 这里要注意json会将支付返回的字符串进行转义，所以必须加上htmlspecialchars_decode避免转义，这个需要注意的！！！
           // return json(['status' => 200, 'data' => htmlspecialchars_decode($str)]);
            return returnjson(1000,htmlspecialchars_decode($str),'获取成功');


            //return returnjson('1001','支付暂未开通','支付暂未开通');
        }else if($pay_type == 3) {
            Db::startTrans();
            $out_trade_no = 'ORDER'.time();
            $nonce_str = $this->getRandom(32);
            $body = $advanced_name;
            $url = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/paynotice/redirectnotify';
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
                Db::rollback();
                return returnjson(1001,'','获取失败');
            }
            $param['out_trade_no'] = $out_trade_no;
            $param['total_amount'] = $pay_params['total_fee']/100;
            $param['subject'] = $advanced_name;
            $param['pay_type'] = 5;
            $order_model->where('order_id','=',$order_id)->update($param);
            //添加支付日志
           /* $param['payment_name']='微信支付';
            $param['payment']='wxpay';
            $param['add_time']=time();
            $param['uid']=$this->uid;
            PayLog::create($param);*/

            $signParam['out_trade_no'] = $param['out_trade_no'];
            $signParam = json_encode($signParam);
            $signParam = stripslashes($signParam);
            $signParam = json_decode($signParam);
            Db::commit();
            return returnjson(1000,$signParam,'获取成功');

        }else{
            Db::rollback();
            return returnjson(1001,'','购买方式错误');
        }
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
    /*
     * 人脸识别支付成功
     */
    public function paySuccess($result = '') {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        return returnjson(1000,'','已完成认证');
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

