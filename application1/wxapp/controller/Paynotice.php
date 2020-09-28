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
class Paynotice extends Base {
    public function __construct(){
       $this->request = Request::instance();
    }
    public function index(){
        
        $aop = new AopClient;
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbPyffwy3iPQz0dodFKZZXRF5b6PMlAOhOrzuMixuSlN8qe3bLpE1CHMaVvgQZcYOzJzqd0d0mi69IPR8A1tZkCqF/jX/zbNpKDc4n3mPy7mA9gjfv2qJVpsysnaVSTcnKUkq8BD+MHGKq28LTF7GDts67glr7fni0LgV5NueLy3+BW6BFhG4cCNCD+Zq0hSQffrQnnk8wDha9kfrhsKZvyCN8wCCeOEGciQY3+9CR7u3jI4murUXdZtf2b1NV0qFCbivkDZoHEurRmDnkcMVrVTWVXMfxxfbxsruHRtHTAEb37tzp0DnJKfPRqaVuTN64is64/ywBWrUaanjmX89QIDAQAB';//支付宝公钥
        $flag = $aop->rsaCheckV1($_POST, $aop->alipayrsaPublicKey, "RSA2");
        if($_POST['trade_status'] == 'TRADE_SUCCESS'){
            $ispay = Db::name('face_order')->where(['out_trade_no'=>$_POST['out_trade_no']])->find();
            if($ispay['status'] == 1){
                exit('success');
            }
            Db::name('face_order')->where(['out_trade_no'=>$_POST['out_trade_no']])->update(['status'=>1,'paytime'=>time(),'paytype'=>1]);
                    //return returnjson(1000,'','支付成功');
          
        	exit('success');//返回给支付宝success,页面不能有其它输出
        }
    }
    //学才商 财学堂支付宝支付回调
    public function alinotify(){

        $aop = new AopClient;
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAgbPyffwy3iPQz0dodFKZZXRF5b6PMlAOhOrzuMixuSlN8qe3bLpE1CHMaVvgQZcYOzJzqd0d0mi69IPR8A1tZkCqF/jX/zbNpKDc4n3mPy7mA9gjfv2qJVpsysnaVSTcnKUkq8BD+MHGKq28LTF7GDts67glr7fni0LgV5NueLy3+BW6BFhG4cCNCD+Zq0hSQffrQnnk8wDha9kfrhsKZvyCN8wCCeOEGciQY3+9CR7u3jI4murUXdZtf2b1NV0qFCbivkDZoHEurRmDnkcMVrVTWVXMfxxfbxsruHRtHTAEb37tzp0DnJKfPRqaVuTN64is64/ywBWrUaanjmX89QIDAQAB';//支付宝公钥
        $flag = $aop->rsaCheckV1($_POST, $aop->alipayrsaPublicKey, "RSA2");
        if($_POST['trade_status'] == 'TRADE_SUCCESS'){
            
           Db::name('order')->where('out_trade_no',$_POST['out_trade_no'])->update(['status'=>4,'paytime'=>time(),'pay_type'=>6]);
           
            exit('success');//返回给支付宝success,页面不能有其它输出
        }
    }
  	public function wxcallbacl(){
        $result = (array)simplexml_load_string($this->request->getInput(), null, LIBXML_NOCDATA);
      	
      	$wx = Db::name('payment')->where('type','wx')->find();
        (($result['result_code'] !== 'SUCCESS') || ($result['mch_id'] !==$wx['mch_id']) || ($result['appid'] !== $wx['appid'])) && $this->wechatResult('FAIL', 'invalid param');
      	$this->createSign($result, $wx['key']) !== $result['sign'] && $this->wechatResult('FAIL', 'invalid sign');
        if($result['return_code'] == 'SUCCESS'){
          $ispay = Db::name('face_order')->where(['out_trade_no'=>$result['out_trade_no']])->find();
            if($ispay['status'] == 1){
                exit('success');
            }
            Db::name('face_order')->where(['out_trade_no'=>$result['out_trade_no']])->update(['status'=>1,'paytime'=>time(),'paytype'=>2]);
             //return returnjson(1000,'','支付成功');
          
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
           Db::name('order')->where(['out_trade_no'=>$result['out_trade_no']])->update(['status'=>4,'paytime'=>time(),'pay_type'=>5]);
           exit('success');
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

}

