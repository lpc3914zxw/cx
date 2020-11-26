<?php
namespace app\wxapp\controller;
use app\common\Common;

use think\Db;
use think\Log;
use think\Loader;
use aop\AopClient;
use aop\request\AlipayTradeAppPayRequest;
Loader::import('wxpay.lib.WxPay', EXTEND_PATH, '.Api.php');
class Cards extends Base {

    /*
     * 会员卡详情
     * @param 
     * @param string $token
     */
    public function cardsDetail($type = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        if(empty($type)){
            return returnjson('1001','','参数缺失！');
        }
        $card = Db::name('cards')->where('id',$type)->find();
        
       	if(!empty($card)){
       	    if($card['discount']!=0){
       	        $card['reality_price'] = round($card['price'] * $card['discount']/10,2 );
       	    }
       	    return returnjson('1000',$card,'成功');
       	}
       	 return returnjson('1000',$card,'成功');
    }
    /*
     * 会员卡下单
     * @param 
     * @param string $token
     */
    public function addCardsOrder($id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        if(empty($id)){
            return returnjson('1001','','参数缺失！');
        }
        
        $order = Db::name('cards_order')->where('uid',$this->uid)->where('endtime','>',time())->where('status',1)->where('cardid',$id)->find();
        //$order1 = Db::name('cards_order')->where('uid',$this->uid)->where('endtime','>',time())->where('status',1)->where('cardid',1)->find();
       // $order2 = Db::name('cards_order')->where('uid',$this->uid)->where('endtime','>',time())->where('status',1)->where('cardid',2)->find();
        if($order){
            return returnjson('1001','','您已购买');
        }
        $card = Db::name('cards')->where('id',$id)->find();
        $reality_price = $card['price'];
       	if(!empty($card)){
       	    if($card['discount']!=0){
       	        $card['reality_price'] = round($card['price'] * $card['discount']/10,2 );
       	        $reality_price = round($card['price'] * $card['discount']/10,2 );
       	    }
       	    
       	}else{
       	    return returnjson('1001','','无此会员卡');
       	}
       	$orderid = 'CC'.time();
       	$data = array('orderid'=>$orderid,'cardid'=>$card['id'],'price'=>$reality_price,'discount'=>$card['discount'],'addtime'=>time(),'uid'=>$this->uid);
       	Db::name('cards_order')->insert($data);
       	$card['orderid'] = $orderid;
       	$card['paytype'] = array('2');
       	return returnjson('1000',$card,'成功');
    }
   /*
     * 确认支付
     * @param $order_id  订单号   $pay_type 支付方式     2 支付宝支付  1 微信支付
     */
    public function submitPayData($orderid = '',$pay_type = 2) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
       // $pay_type = input('pay_type');
        if($this->uid == 0) {
            return returnjson(1100,'','该设备在其他地方登录');
        }
        $order_id = $orderid;
        if(empty($order_id)){
            return returnjson('1001','','参数缺失');
        }
        $order = Db::name('cards_order')->where('orderid',$order_id)->find();
        
        if($order['status']==1){
            return returnjson('1001','','该订单已支付');
        }
        $card = Db::name('cards')->where('id',$order['cardid'])->find();
        if($card['discount']>0){
            $card['price'] = $card['price']*$card['dedication_value']/10;
        }
        if($pay_type == 1) {
            
        

            //return returnjson('1001','支付暂未开通','支付暂未开通');
        }else if($pay_type == 2) {
            // 订单号
            //$out_trade_no = 'ORDER'.time();
            $order_number = $order_id;
            // 获取金额
            $money = $card['price'];
            //$money = 0.01;
            // 名称
            $body='购买-'.$card['name'];
            // 异步回调地址，这个后面不能带参数的
            $notify_url = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/paynotice/alicardnotify';

            // 可根据具体需要写出具体需求 可创建支付订单 判断支付成功调用支付方法
            $money = 0.01;
            // 调用支付宝支付
            $str = $this->alipay($body, $money, $order_number , $notify_url );
            //$param['out_trade_no'] = $out_trade_no;
            //$param['total_amount'] = $money;
            //$param['subject'] = $body;
            //$param['pay_type'] = 6;
            //$order_model->where('order_id','=',$order_id)->update($param);
            // 这里要注意json会将支付返回的字符串进行转义，所以必须加上htmlspecialchars_decode避免转义，这个需要注意的！！！
           // return json(['status' => 200, 'data' => htmlspecialchars_decode($str)]);
            return returnjson(1000,htmlspecialchars_decode($str),'获取成功');

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
     * 会员卡详情
     * @param 
     * @param string $token
     */
    public function myCardsDetail($type=0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $userinfo = Db::name('user')->where('id',$this->uid)->field('card_level,name,headimg,level')->find();
        
        $order = Db::name('cards_order')->where(['status'=>1,'uid'=>$this->uid])->where('endtime','>',time())->where('cardid',$type)->value('endtime');
        $card = Db::name('cards')->where('id',$type)->find();
        if($order<time()){
            return returnjson('1001','','您没有会员卡');
        }
        $userinfo['time'] = date('Y-m-d',$order);
        $userinfo['cardname'] = $card['name'];
        $userinfo['explain'] = $card['explain'];
        $userinfo['abstract'] = $card['abstract'];
        $userinfo['level'] = Db::name('user_level')->where('value',$userinfo['level'])->value('name');
       	return returnjson('1000',$userinfo,'成功');
    }
    //计算团队业绩
    public function team_total_price($uid){
        Db::name('team_level')->order('value')->select();
        $userids = Db::name('user')->where('is_auth',1)->where('parentids')->field('id')->select();
        $userids = implode(',',$userids);
        $value1 = Db::name('order')->where('status',4)->where('uid','in',$userids)->sum('value');
        $value2 = Db::name('cards_order')->where('status',1)->where('uid','in',$userids)->sum('price');
        
    }
}

