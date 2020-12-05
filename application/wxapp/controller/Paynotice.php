<?php
namespace app\wxapp\controller;
use app\common\Common;

use think\Controller;
use app\wxapp\controller\Base;

use think\Request;

use app\wxapp\model\Orders;
use think\Db;
use aop\AopClient;
use aop\request\AlipayTradeAppPayRequest;
use app\service\WalletService;
class Paynotice extends Base {
    public function __construct(){
       $this->request = Request::instance();
    }
    //人脸支付宝支付回调
    public function index(){

        $aop = new AopClient;
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbPyffwy3iPQz0dodFKZZXRF5b6PMlAOhOrzuMixuSlN8qe3bLpE1CHMaVvgQZcYOzJzqd0d0mi69IPR8A1tZkCqF/jX/zbNpKDc4n3mPy7mA9gjfv2qJVpsysnaVSTcnKUkq8BD+MHGKq28LTF7GDts67glr7fni0LgV5NueLy3+BW6BFhG4cCNCD+Zq0hSQffrQnnk8wDha9kfrhsKZvyCN8wCCeOEGciQY3+9CR7u3jI4murUXdZtf2b1NV0qFCbivkDZoHEurRmDnkcMVrVTWVXMfxxfbxsruHRtHTAEb37tzp0DnJKfPRqaVuTN64is64/ywBWrUaanjmX89QIDAQAB';//支付宝公钥
        $flag = $aop->rsaCheckV1($_POST, $aop->alipayrsaPublicKey, "RSA2");
        if($_POST['trade_status'] == 'TRADE_SUCCESS'){

            Db::name('face_order')->where(['out_trade_no'=>$_POST['out_trade_no']])->update(['status'=>1,'paytime'=>time(),'paytype'=>1]);
                  

        	exit('success');//返回给支付宝success,页面不能有其它输出
        }
    }
    //学才商 财学堂支付宝支付回调
    public function alinotify(){

        $aop = new AopClient;
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbPyffwy3iPQz0dodFKZZXRF5b6PMlAOhOrzuMixuSlN8qe3bLpE1CHMaVvgQZcYOzJzqd0d0mi69IPR8A1tZkCqF/jX/zbNpKDc4n3mPy7mA9gjfv2qJVpsysnaVSTcnKUkq8BD+MHGKq28LTF7GDts67glr7fni0LgV5NueLy3+BW6BFhG4cCNCD+Zq0hSQffrQnnk8wDha9kfrhsKZvyCN8wCCeOEGciQY3+9CR7u3jI4murUXdZtf2b1NV0qFCbivkDZoHEurRmDnkcMVrVTWVXMfxxfbxsruHRtHTAEb37tzp0DnJKfPRqaVuTN64is64/ywBWrUaanjmX89QIDAQAB';//支付宝公钥
        $flag = $aop->rsaCheckV1($_POST, $aop->alipayrsaPublicKey, "RSA2");
        if($_POST['trade_status'] == 'TRADE_SUCCESS'){
           Db::name('order')->where(['out_trade_no'=>$_POST['out_trade_no']])->update(['status'=>4,'paytime'=>time(),'paytype'=>2]);
            exit('success');//返回给支付宝success,页面不能有其它输出
        }
    }
    //人脸微信支付回调
  	public function wxcallbacl(){
        $result = (array)simplexml_load_string($this->request->getInput(), null, LIBXML_NOCDATA);

      	$wx = Db::name('payment')->where('type','wx')->find();
        (($result['result_code'] !== 'SUCCESS') || ($result['mch_id'] !==$wx['mch_id']) || ($result['appid'] !== $wx['appid'])) && $this->wechatResult('FAIL', 'invalid param');
      	$this->createSign($result, $wx['key']) !== $result['sign'] && $this->wechatResult('FAIL', 'invalid sign');
        if($result['return_code'] == 'SUCCESS'){
            Db::name('face_order')->where(['out_trade_no'=>$result['out_trade_no']])->update(['status'=>1,'paytime'=>time(),'paytype'=>2]);
             exit('success');
        }
    }
    //学才商 财学堂微信支付回调
    public function redirectnotify(){
        $result = (array)simplexml_load_string($this->request->getInput(), null, LIBXML_NOCDATA);

        $wx = Db::name('payment')->where('type','wx')->find();
        (($result['result_code'] !== 'SUCCESS') || ($result['mch_id'] !==$wx['mch_id']) || ($result['appid'] !== $wx['appid'])) && $this->wechatResult('FAIL', 'invalid param');
        $this->createSign($result, $wx['key']) !== $result['sign'] && $this->wechatResult('FAIL', 'invalid sign');
        if($result['return_code'] == 'SUCCESS'){
           Db::name('order')->where(['out_trade_no'=>$result['out_trade_no']])->update(['status'=>4,'paytime'=>time(),'paytype'=>2]);
           exit('success');
        }
    }
    //会员卡宝支付回调
    public function alicardnotify(){

        $aop = new AopClient;
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnSsGitlOZzWGxHKDQlVzJrr9htwV5FO5301ZaVf8iWOiiR3DUm0D6jFq1ryX7NOcUFlTfUWisfm1szoE6/PGqGpqRfoq/W0EhX7fV7CBN4nZE6V0f51fr5JqdxZW3azC/ukltaQ16ipxNljZFtw+cVjfWy7Wv0GenTeGjJ14UDI3lLonjgwJB9K/cXTt6xIo+nDLQlU54q62wjdnpd8kk7WlAySldFocNhc0ehCSArKGhC3eSbj+YMUX4xkuQ+BS2Y+EbdRPS7chW8ypbjElN6fcwLoKAgf6KWyYiSzshuM3Kx0ePLLucDFEqpz4Zg918rC6uQn9o2OkUiRENCXTfQIDAQAB';//支付宝公钥
        $flag = $aop->rsaCheckV1($_POST, $aop->alipayrsaPublicKey, "RSA2");
      //  $documentRoot = $_SERVER['DOCUMENT_ROOT'];
//file_put_contents($documentRoot.'/log_666666.txt',print_r($_POST,true),FILE_APPEND);
        if($_POST['trade_status'] == 'TRADE_SUCCESS'){
            $yeartime = time()+31536000;
            $order = Db::name('cards_order')->where('orderid',$_POST['out_trade_no'])->find();
           Db::name('cards_order')->where('orderid',$_POST['out_trade_no'])->update(['status'=>1,'paytime'=>time(),'endtime'=>$yeartime,'paytype'=>1]);
           $card = Db::name('cards')->where('id',$order['cardid'])->field('name,discount,price,dedication_value')->find();
           $pid = Db::name('user')->where('id',$order['uid'])->value('pid');
           if($card['discount']>0){
               $dedi = $card['dedication_value'] * $card['discount']/10;
           }else{
               $dedi = $card['dedication_value'];
           }
           
           $dedicData = [
                    'uid'=>$order['uid'],
                    'type'=>23,
                    'value'=>$dedi,
                    'obj_id'=>$order['cardid'],
                    'sq_type'=>0,
                    'content'=>'购买'.$card['name'],
                    'addtime'=>time()
                ];
            Db::name('dedication_log')->insert($dedicData);
           if(!empty($pid)){
               $dedicData = [
                        'uid'=>$pid,
                        'type'=>23,
                        'value'=>$dedi,
                        'obj_id'=>$order['cardid'],
                        'sq_type'=>0,
                        'content'=>'购买'.$card['name'],
                        'addtime'=>time()
                    ];
                Db::name('dedication_log')->insert($dedicData);
                Db::name('user')->where('id',$pid)->setInc('dedication_value',$dedi);
           }
           //$common_model->dedicationLog($order['uid'],23,$order['cardid'],'购买'.$card);
           Db::name('user')->where('id',$order['uid'])->update(['card_level'=>$order['cardid']]);
           Db::name('user')->where('id',$order['uid'])->setInc('dedication_value',$dedi);
           $this->card_fenxiao($_POST['out_trade_no']);
            exit('success');//返回给支付宝success,页面不能有其它输出
        }
    }
  	private function wechatResult($code, $msg) {
        $xml = '<xml>';
        $xml .= '<return_code><![CDATA[' . $code . ']]></return_code>';
        $xml .= '<return_msg><![CDATA[' . $msg . ']]></return_msg>';
        $xml .= '</xml>';
        echo $xml;die;
    }

    /**
     * 生成签名
     * @author Steed
     * @param array $data
     * @param $key
     * @return string
     */
    private function createSign($data = [], $key) {
        unset($data['sign']);
        //按ASCII字典序排序
        ksort($data);
        $str = '';
        foreach ($data as $k => $val) {
            $str .= $k . '=' . $val . '&';
        }
        $str .= 'key=' . $key;
        return strtoupper(md5($str));
    }
     function card_fenxiao($params){
        //$params = $this->data_post;
        //获取订单信息
        //$params = input();
        $res=Db::name('cards_order')->where(['orderid'=>$params])->find();
        //$res['id'] = 0;
        //var_dump($res['total_amount']);exit;
        if($res['is_fenxiao']==1){
            return;
        }
        $common = new common();
        $uids=Db::name('user')->where('id','=',$res['uid'])->value('parentids');
        $pid=Db::name('user')->where('id','=',$res['uid'])->where('card_level','neq',0)->value('pid');
        WalletService::AddUserWallet($res['uid']);//自动添加钱包
        
        if(!empty($pid)){
            //获取一级分销比例
            $one_course_scale =MyC('one_course_scale');
            $money_total=$res['price'];
            $money_one_sale=$money_total*$one_course_scale/100;
            //var_dump(PriceNumberFormat($money_one_sale));exit;
            $money1 = sprintf("%.2f",$money_one_sale*0.8);
            $credit = $money_one_sale - $money1;
            $common->save_credit($this->uid,$credit,3);
            if($money_one_sale>0){
                $ress = WalletService::UserWalletMoneyUpdate($res,$one_course_scale,1,$pid,$money_one_sale,1,'normal_money',1,'下级购买会员卡分销',1);
                //var_dump($ress);exit;
            }
            
        }else{
            $pid=0;//没有上级
        }
        $ppid=Db::name('user')->where('id',$pid)->where('card_level','neq',0)->value('pid');
        if(!empty($ppid)){
            $two_course_scale =MyC('two_course_scale');
            $money_total=$res['price'];
            $money_two_sale=$money_total*$two_course_scale/100;
            if($money_one_sale>0){
                $money2 = sprintf("%.2f",$money_one_sale*0.8);
                $credit = $money_one_sale - $money2;
                $common->save_credit($this->uid,$credit,3);
                WalletService::UserWalletMoneyUpdate($res,$two_course_scale,2,$ppid,$money_two_sale,1,'normal_money',1,'二级购买会员卡分销',1);
            }
        }else{
            $ppid=0;//没有上级
        }
        //is_scale是否分销
        Db::name('cards_order')->where(['orderid'=>$params])->update(['is_fenxiao'=>1,'fenxiao_time'=>time()]);
        $array = $this->get_parents($uids);
        $this->inc_parents_yeji($array,$res['price'],$uids);
    }
    //获取全部族系成员
    public function get_parents($uids){
        $uids = ltrim($uids,'0,');
        $uids = '202,'.$uids;
        $uids = rtrim($uids,',');
        if(empty($uids)){
            return false;
        }else{
            $users = Db::name('user')->alias('u')->join('team_performance t','u.id = t.uid','LEFT')->where('u.id','in',$uids)->field('u.id,u.is_auth,u.status,u.card_level,t.total')->select();
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
}

