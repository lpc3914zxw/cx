<?php
namespace app\wxapp\controller;
use app\common\Common;

use think\Db;
use think\Log;
use think\Loader;
use aop\AopClient;
use aop\request\AlipayTradeAppPayRequest;
use app\service\WalletService;

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
            //$money = 0.01;
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
    public function task_jicha(){
        $time = time()+60;
        $list = Db::name('cards_order')->where('is_jicha',0)->where('paytime','<',$time)->field('orderid')->select();
        
        $i = 0;
        foreach($list as $key=>$val){
            $this->jicha($val['orderid']);
            $i++;
        }
        echo '执行'.$i.'条';
    }
    public function jicha($orderid){
        //平级奖比例
        $team_set = Db::name('team_set')->find();
        if($team_set['is_open']==0){
            return '未开启极差';
        }
        //团队业绩等级
        $team_level = Db::name('team_level')->order('value','ASC')->select();
        if(empty($team_level)){
            return '业绩未设置';
        }
        $team_level_ = array();
        foreach($team_level as $tkey=>$tval){
            $value_ = $tval['value'];
            $team_level_[$value_] = $tval;
            $team_level_[$value_]['is_pinji'] =0;
            $team_level_[$value_]['pinji_uid'] =0;
            $team_level_[$value_]['is_jicha'] =0;
            $team_level_[$value_]['jicha_uid'] =0;
            $team_level_[$value_]['jicha_pid'] =0;
            $team_level_[$value_]['jicha_price'] =0;
            $team_level_[$value_]['pinji_price'] =0;
        }
        $orderinfo = Db::name('cards_order')->where('orderid',$orderid)->find();
        if($orderinfo['is_jicha']==1){
            return '已发放过';
        }
        $price = $orderinfo['price'];
        $userinfo = Db::name('user')->where('id',$orderinfo['uid'])->field('parentids')->find();
        if(empty($userinfo['parentids'])){
            return '上级信息异常';
        }
        //起始级别
        $start_level = $team_level[0]['value'];
        $i = 0; 
        $parent_array = $this->get_parents($userinfo['parentids']);
        // $this->inc_parents_yeji($parent_array,$price,$userinfo['parentids']);
        foreach($parent_array as $key=>$val){
            if($val['status']!=0||$val['card_level']==0){
                continue;
            }else{
                $level_value = $this->get_user_level($team_level_,$val);
                if(empty($level_value)){
                    continue;
                }
                $now_level = $level_value['value'];
                if($now_level<=$start_level &&$i!=0){
                    if(!empty($team_level_[$now_level]['pinji_uid'])){
                        continue;
                    }else{
                       $pinjires = $this->get_user_pinji($team_level_,$now_level,$price,$val);
                       if($pinjires){
                           $team_level_[$now_level]['pinji_price'] =  $pinjires;
                           $team_level_[$now_level]['pinji_uid'] = $val['id'];
                       }
                       continue;
                    }
                }else if($now_level<=$start_level&&$i==0){
                    $team_level_[$now_level]['jicha_price'] = $this->get_user_jicha($team_level_,$now_level,$price);
                    $team_level_[$now_level]['jicha_uid'] = $val['id'];
                    $team_level_[$now_level]['jicha_pid'] = $val['pid'];
                    $start_level = $now_level;
                    $end = end($team_level_);
                    $i++;
                    if($end['value']<=$start_level){
                        break;
                    }
                }else{
                    $team_level_[$now_level]['jicha_price'] = $this->get_user_jicha($team_level_,$now_level,$price);
                    $team_level_[$now_level]['jicha_uid'] = $val['id'];
                    $team_level_[$now_level]['jicha_pid'] = $val['pid'];
                    $start_level = $now_level;
                    $end = end($team_level_);
                    $i++;
                    if($end['value']<=$start_level){
                        break;
                    }
                }
            }
        }    
        //var_dump($team_level_);exit;
        $this->give_out($team_level_,$orderinfo);
    }
    //发放奖金
    private function give_out($team_level_,$order){
        $common = new Common();
        foreach($team_level_ as $key=>$val){
            if(!empty($val['jicha_uid'])){
                $jichaprice = sprintf("%.2f",$val['jicha_price']*0.8);
                $credit = $val['jicha_price'] - $jichaprice;
                $common->save_credit($this->uid,$credit,1);
                $ress = WalletService::UserWalletMoneyUpdate($order,$val['ratio'],4,$val['jicha_uid'],$jichaprice,1,'normal_money',4,'极差奖',1);
            }
            if(!empty($val['pinji_uid'])){
                $pinjiaprice = sprintf("%.2f",$val['pinji_price']*0.8);
                $credit = $val['pinji_price'] - $pinjiaprice;
                $common->save_credit($this->uid,$credit,2);
                $ress = WalletService::UserWalletMoneyUpdate($order,$val['ratio'],4,$val['pinji_uid'],$pinjiaprice,1,'normal_money',3,'平级奖',1);
            }
        }
        Db::name('cards_order')->where('id',$order['id'])->update(['is_jicha'=>1]);
    }
    //获取全部族系成员
    public function get_parents($uids){
        $uids = ltrim($uids,'0,');
        $uids = '202,'.$uids;
        $uids = rtrim($uids,',');
        //echo $uids;exit;
        if(empty($uids)){
            return false;
        }else{
            $users = Db::name('user')->alias('u')->join('team_performance t','u.id = t.uid','LEFT')->where('u.id','in',$uids)->field('u.pid,u.id,u.is_auth,u.status,u.card_level,t.total')->select();
        }
        $array = array_reverse($users);
        return $array;
    }
    //族系增加业绩
    public function inc_parents_yeji($array,$price,$uids){
        if(empty($array) || $price<=0){
            return;
        }
        $uids = ltrim($uids,'0,');
        $uids = '202,'.$uids;
        $uids = rtrim($uids,',');
        $uidarray = Db::name('team_performance')->where('uid','in',$uids)->field('uid')->select();
        $uidarray = array_column($uidarray,'uid');
        $updateids = '';
        $insertids = '';
        foreach($array as $key=>$val){
            if(in_array($val['id'],$uidarray)){
                 $updateids .= ','.$val['id'];
            }else{
               $insertids .= ','.$val['id'];
            }
        }
        if(!empty($updateids)){
            $updateids = trim($updateids,',');
            
            Db::name('team_performance')->where('uid','in',$updateids)->setInc('total',$price);
        }
        if(!empty($insertids)){
            $insertids = trim($insertids,',');
            
        }else{
            return;
        }
        $insertids = explode(',',$insertids);
        $all = array();
        foreach($insertids as $ikey=>$ival){
            $u['uid'] = $ival;
            $u['total'] = $price;
            $all[] = $u;
        }
        if(!empty($all)){
            Db::name('team_performance')->insertAll($all);
        }
    }
    //获取团队级别
    public function get_user_level($team_level,$uinfo){
        foreach($team_level as $key=>$val){
            if($uinfo['total']>=$val['minprice']&&$uinfo['total']<=$val['maxprice']){
                return $val;
            }else{
                continue;
            }
        }
    }
    //计算获取的极差奖
    public function get_user_jicha($team_level,$level,$price){
        $accumulative_price = 0;
        $setratio = $team_level[$level]['ratio'];
        $total_price = $price*$setratio/100;
        foreach($team_level as $key=>$val){
            if($key<$level){
                if(empty($val['jicha_price'])){
                    $val['jicha_price'] = 0;
                }
                $accumulative_price += $val['jicha_price'];
            }else{
                continue;
            }
        }
        return $total_price - $accumulative_price;
    }
    //计算获取的平级奖
    public function get_user_pinji($team_level,$level,$price,$userinfo){
        $accumulative_price = 0;
        $setratio = $team_level[$level]['ratio'];
        $total_price = $price*$setratio/100;
        if($team_level[$level]['jicha_pid'] != $userinfo['id']){
            return false;
        }
        foreach($team_level as $key=>$val){
            if($key<$level){
                if(empty($val['jicha_price'])){
                    $val['jicha_price'] = 0;
                }
                $accumulative_price += $val['jicha_price'];
            }else{
                continue;
            }
        }
        return ($total_price - $accumulative_price)/10;
    }
}

