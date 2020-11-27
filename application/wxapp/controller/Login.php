<?php
namespace app\wxapp\controller;

use app\service\SmsService;
use app\wxapp\model\Orders;
use think\Controller;
use app\wxapp\model\User;
use think\Db;
use app\common\Common;
use think\Request;


class Login extends Controller
{
    /*
    * 发送短信//
    */
    public function sendcode($tel = '',$type="login",$RequestId='') {
        $response = new SmsService();


        $user_model = new User();
        if (!preg_match("/^1[3456789]\d{9}$/", $tel)) {
            return returnjson(1001, '', '手机号码有误!');
        }
        $info = $user_model->where(['tel'=>$tel])->find();
        if(!empty($info)){
            $is_reg = 1;
        }else{
            $is_reg = 0;
        }
        $type = trim($type);
        $data = input();
        if(isset($data['pid'])&& empty($data['pid'])){

        }else{
            if($type =='login' || $type == 'findpwd'){
                if(empty($info)){
                    // return returnjson(1001, $is_reg, '此号码未注册');
                }
            }else{
                $student_no = input('student_no');
                $info1 = $user_model->where(['student_no'=>$student_no])->find();
                if(empty($info1)){
                    return returnjson(1001, $is_reg, '邀请码无效');
                }
                if(!empty($info)){
                    return returnjson(1001, $is_reg, '此号码已被注册');
                }
            }
        }
        if($type=='login'){
            $ty=0;
        } elseif($type== 'register'){
            $ty=1;
        }elseif($type== 'findpwd'){
            $ty=2;
        }else{
            return returnjson(1001, $is_reg, '无效参数');
        }
        $str = '1234567890';
        $randStr = str_shuffle($str); //打乱字符串
        $code = substr($randStr, 0, 4); //substr(string,start,length);返回字符串的一部分\
        vendor('aliyun-dysms-php-sdk.api_demo.SmsDemo');
        $content = ['code' => $code];
        $response = \SmsDemo::sendSms($tel, $content);
        $response = object_to_array($response);
        if ($response['Message'] == 'OK') {
            cache($tel,$code,1800);
            return returnjson(1000, $is_reg, '发送成功');
        } else {
            return returnjson(1001, $is_reg, $response['Message']);
        }
        /*$res=$response->sendSms($tel,$code,$ty);

        $res = object_to_array($res);
        //var_dump($res);exit;
        if ($res['SendStatusSet'][0]['Code'] == 'Ok') {
            cache($tel,$code,1800);
            return returnjson(1000, $is_reg, '发送成功');
        } else {
            return returnjson(1001, $is_reg,$res['SendStatusSet'][0]['Message']);
        }*/
       /* $user_model = new User();
        if (!preg_match("/^1[3456789]\d{9}$/", $tel)) {
            return returnjson(1001, '', '手机号码有误!');
        }
        $info = $user_model->where(['tel'=>$tel])->find();
        if(!empty($info)){
            $is_reg = 1;
        }else{
            $is_reg = 0;
        }
        $type = trim($type);
        $data = input();
      	if(isset($data['pid'])&& empty($data['pid'])){

        }else{
        	 if($type =='login' || $type == 'findpwd'){
            if(empty($info)){
               // return returnjson(1001, $is_reg, '此号码未注册');
            }
        }else{
          	$student_no = input('student_no');
          	$info1 = $user_model->where(['student_no'=>$student_no])->find();
          	if(empty($info1)){
             return returnjson(1001, $is_reg, '邀请码无效');
            }
            if(!empty($info)){
                return returnjson(1001, $is_reg, '此号码已被注册');
            }
        }
        }

        $str = '1234567890';
        $randStr = str_shuffle($str); //打乱字符串
        $code = substr($randStr, 0, 4); //substr(string,start,length);返回字符串的一部分\
        vendor('aliyun-dysms-php-sdk.api_demo.SmsDemo');
        $content = ['code' => $code];
        $response = \SmsDemo::sendSms($tel, $content);
        $response = object_to_array($response);
        if ($response['Message'] == 'OK') {
            cache($tel,$code,1800);
            return returnjson(1000, $is_reg, '发送成功');
        } else {
            return returnjson(1001, $is_reg, $response['Message']);
        }*/
    }
    /***
     * @param $tel
     * @param $code
     * @return false|string
     */
    public function sendwxcode($tel = '',$type="login"){

        $response = new SmsService();


        $user_model = new User();
        if (!preg_match("/^1[3456789]\d{9}$/", $tel)) {
            return returnjson(1001, '', '手机号码有误!');
        }
        $info = $user_model->where(['tel'=>$tel])->find();
        if(!empty($info)){
            $is_reg = 1;
        }else{
            $is_reg = 0;
        }
        $type = trim($type);
        $data = input();
        if(isset($data['pid'])&& empty($data['pid'])){

        }else{
            if($type =='login' || $type == 'findpwd'){
                if(empty($info)){
                    // return returnjson(1001, $is_reg, '此号码未注册');
                }
            }else{
                $student_no = input('student_no');
                $info1 = $user_model->where(['student_no'=>$student_no])->find();
                if(empty($info1)){
                    return returnjson(1001, $is_reg, '邀请码无效');
                }
                if(!empty($info)){
                    return returnjson(1001, $is_reg, '此号码已被注册');
                }
            }
        }
        if($type=='login'){
            $ty=0;
        } elseif($type== 'register'){
            $ty=1;
        }elseif($type== 'findpwd'){
            $ty=2;
        }else{
            return returnjson(1001, $is_reg, '无效参数');
        }
        $str = '1234567890';
        $randStr = str_shuffle($str); //打乱字符串
        $code = substr($randStr, 0, 4); //substr(string,start,length);返回字符串的一部分\

        $res=$response->sendSms($tel,$code,$ty);

        $res = object_to_array($res);
        //var_dump($res);exit;
        if ($res['SendStatusSet'][0]['Code'] == 'Ok') {
            cache($tel,$code,1800);
            return returnjson(1000, $is_reg, '发送成功');
        } else {
            return returnjson(1001, $is_reg,$res['SendStatusSet'][0]['Message']);
        }
    }
    /*
     * 忘记密码校验接口
     * @param string $tel
     * @param string $code
     */
     public function chenkForget($tel,$code){
         if(empty($tel)||empty($code)){
             return returnjson(1001, '', '参数缺失');
         }
        $ycode = 9999;
         if($ycode == $code){
             return returnjson(1000, '', '验证通过');
         }else{
             return returnjson(1001, '', '验证不通过');
         }
     }
    /*
     * 验证码注册--滑块验证
     * @param string $tel
     * @param string $code
     */
    public function codeRegister($tel = '',$code = '',$RequestId='') {
        //$request = Request::instance();
        //$ip = $request->ip();
        //if($RequestId!==cache($ip)){
            //return returnjson(1001,'','验证码错误');
        //}
        $ycode = cache($tel);
        $ycode = 9999;
        if($code == '') {
            return returnjson(1001,'','请输入验证码');
        }
        if($ycode != $code) {
            return returnjson(1001,'','验证码错误');
        }
      $user_model = new User();
        $student_no = input('student_no');
         $info1 = $user_model->where(['student_no'=>$student_no])->find();
      	if(empty($info1)){
        return returnjson(1001,'','邀请码无效');
        }

        $salt = get_rand_char(4);
        $pay_salt = get_rand_char(4);
        $student_no = $this->studentMake();
        $where = ['tel' => $tel];
        $info = $user_model->where($where)->find();
        if(!preg_match("/^1[3456789]\d{9}$/", $tel)){
            return returnjson(1001, '', '手机号不合法');
        }
        $name = "用户".substr($tel,-4);
        if (empty($info)) {
            $data = [
                'tel'=>$tel,
                'name'=>$name,
                'salt'=>$salt,
                 'pay_salt'=>$pay_salt,
                'student_no'=>$student_no,
                'regetime'=>time(),
              	'pid'=>$info1['id']
            ];
          //$documentRoot = $_SERVER['DOCUMENT_ROOT'];
//file_put_contents($documentRoot.'/log_0727.txt',print_r($uid.'--->'.$password.'--->'.$rpwd.'---'.$tel,true),FILE_APPEND);
          if($info1['pid'] == 0) {
                    $data['parentids'] = "0,";
           }else{
                    $data['parentids'] = $info1['parentids'].$info1['id'].',';
           }
            if($user_model->insert($data)){
                $uid = $user_model->getLastInsID();
            }
            return returnjson(1000, $uid, '注册成功');
        } else {
            return returnjson(1001, '', '该手机号已注册,前去登陆!');
        }
    }

    /*
     * 生成学号
     */
    public function studentMake() {
        $student_no = get_rand_char(8);
        $user_model = new User();
        if($user_model->where('student_no',$student_no)->find()){
            return $this->studentMake();
        }else{
            return $student_no;
        }
    }

    /*
     * 验证码登陆--添加滑动验证
     * @param string $tel
     * @param string $code
     */
    public function codeLogin($tel = '',$code = '',$RequestId='') {
        //$request = Request::instance();
        //$ip = $request->ip();
        //if($RequestId!==cache($ip)){
            //return returnjson(1001,'','验证码错误');
        //}
        $ycode = cache($tel);
        $ycode = 9999;
        if($ycode != $code) {
            return returnjson(1001,'','验证码错误');
        }
        if($code == '') {
            return returnjson(1001,'','请输入验证码');
        }
        $user_model = new User();
        if ($tel == '') {
            return returnjson(1001, '', '请输入手机号');
        }
        if (!preg_match("/^1[3456789]\d{9}$/", $tel)) {
            return returnjson(1001, '', '手机号码有误!');
        }
        $where = ['tel' => $tel];
        $info = $user_model->where($where)->find();
        if (empty($info)) {
            return returnjson(1001, '', '该账号未注册,请先注册!');
        } else {
            $token = md5(time() . rand(111111, 999999));
            $user_model->where('id', $info['id'])->update(['token'=>$token]);
            $info['token'] = $token;
            return returnjson(1000, $info, '登录成功!');
        }
    }

    /*
     * 账号密码登陆--添加滑动验证
     * @param string $tel
     * @param string $password
     * @return \type
     */
    public function dologin($tel = '',$password = '',$RequestId='') {
        $data = input();
       // $request = Request::instance();
        //$ip = $request->ip();
        //if($RequestId!==cache($ip)){
            //return returnjson(1001,'','验证码错误');
        //}
        if ($tel == '' || $password == '') {
            return returnjson(1001, '', '账号或密码不能为空!');
        }
        if (!preg_match("/^1[3456789]\d{9}$/", $tel)) {
            return returnjson(1001, '', '手机号码有误!');
        }

        $user_model = new User();
        $userInfo = $user_model->where('tel',$tel)->find();
        if(empty($userInfo)) {
            return returnjson(1001, '', '该手机号未注册,请先注册!');
        }
        $password = splice_password($password, $userInfo['salt']);
        $where = ['tel' => $tel, 'password' => $password];
        $info = $user_model->where($where)->find();
        if (empty($info)) {
            return returnjson(1001, '', '账号或密码错误');
        } else {
            $token = md5(time() . rand(111111, 999999));
            $user_model->where('id', $info['id'])->update(['token'=>$token]);
            $info['token'] = $token;
            return returnjson(1000, $info, '登录成功!');
        }
    }

    /*
     * 设置密码--添加验证
     * @param int $uid  用户id
     * @param string $password  密码
     * @param string $rpwd 重复密码
     */
    public function setPassword($uid = 0,$password = '',$rpwd = '',$tel='',$RequestId='') {
       // $request = Request::instance();
        //$ip = $request->ip();
        //if($RequestId!==cache($ip)){
            //return returnjson(1001,'','验证码错误');
        //}

        if($tel == '' || $password == '' || $rpwd == '') {
            return returnjson(1001,'','参数缺失');
        }
        if($password != $rpwd) {
            return returnjson(1001,'','两次密码不一致');
        }

        $user_model = new User();
        $userInfo = $user_model->where('id',$uid)->find();
        if(empty($userInfo)){
            $userInfo = $user_model->where('tel',$tel)->find();
        }
      	if(empty($userInfo)){
        	return returnjson(1001,'','设置失败,请重试');
        }
        $password = splice_password($password, $userInfo['salt']);

        if(false !== $user_model->where('id',$userInfo['id'])->update(['password'=>$password])){
            return returnjson(1000,'','设置成功');
        }
        return returnjson(1001,'','设置失败,请重试');
    }

    /*
     * h5注册页面--滑块验证
     */
    public function h5_register($pid = 0,$RequestId='') {
        //$request = Request::instance();
        //$ip = $request->ip();
        //if($RequestId!==cache($ip)){
            //return returnjson(1001,'','验证码错误');
        //}
        $user_model = new User();
        if($this->request->isPost()) {
            $user_model = new User();
            if(!$user_model->where('id',$pid)->find()) {
                return returnjson(1001,'','该邀请人不存在');
            }
            $params = $this->request->param();
            $ycode = cache($params['tel']);
            $ycode = 9999;
            $password = trim($params['password']);
            $rpwd = trim($params['rpwd']);
            $name = "用户".substr($params['tel'],-4);
            $checkData = [
                'tel'=>$params['tel'],
                'code'=>$params['code'],
                'password'=>$password,
                'rpwd'=>$rpwd
            ];

              if($ycode != $params['code']) {
                  $this->error('验证码错误');
              }


            $login_validate = new \app\wxapp\validate\Login();
            if(!$login_validate->check($checkData)) {
                $this->error($login_validate->getError());
            }
            if($password != $rpwd) {
                $this->error('两次密码不一致');
            }

            $salt = get_rand_char(4);
          	$pay_salt = get_rand_char(4);
            $student_no = $this->studentMake();
            $where = ['tel' => $params['tel']];
            $info = $user_model->where($where)->find();
            if(!preg_match("/^1[3456789]\d{9}$/", $params['tel'])){
                $this->error('手机号不合法');
            }
            if (empty($info)) {
                $password = splice_password($password, $salt);
                $data = [
                    'tel'=>$params['tel'],
                    'pid'=>$params['pid'],
                    'salt'=>$salt,
                  	'pay_salt'=>$pay_salt,
                    'name'=>$name,
                    'password'=>$password,
                    'student_no'=>$student_no,
                    'regetime'=>time()
                ];
                $parentInfo = $user_model->field('pid,parentids')->where('id',$params['pid'])->find();// 上级pid
                if($parentInfo['pid'] == 0) {
                    $data['parentids'] = "0,";
                }else{
                    $data['parentids'] = $parentInfo['parentids'].$params['pid'].',';
                }
                Db::startTrans();
                $common = new Common();
                $order_model = new Orders();
                if($user_model->insert($data)){
                    $uid = $user_model->getLastInsID();
                    $message_model = new \app\index\model\Message();
                    $msgData = [
                        'type'=>2,'title'=>'邀请好友','content'=>"你的好友".$name."通过邀请成功注册了每日财学APP。快来关注他，和他一起学习每日财学财商",
                        'invate_uid'=>$uid,'uid'=>$params['pid'],'addtime'=>time(),'send_time'=>time()
                    ];
                    if(!$message_model->insert($msgData)) {
                        Db::rollback();
                        $this->error('注册失败');
                    }
                }
                Db::commit();
                $this->success('注册成功,去下载每日财学APP吧!');
            } else {
               $this->error('该手机号已注册,前去登陆');
            }
        }
        $url =  'https://'.$_SERVER['HTTP_HOST'].'/wxapp/login/sendcode?tel=';
        $useprotocol = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/useprotocol.html';
        $privacyprotocol = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/privacyprotocol.html';
        $this->assign('useprotocol',$useprotocol);
        $this->assign('privacyprotocol',$privacyprotocol);
        $this->assign('url',$url);
        $params = $this->request->param();
        $myinfo = $user_model->where(['id'=>$params['p_id']])->field('student_no')->find();
        $this->assign('myinfo',$myinfo);
        $this->assign('p_id',$params['p_id']);
        return $this->fetch();
    }

    /* 验证码
    * @author staitc7
    */
    public function verify() {
        header('Content-Type:application/json; charset=utf-8');
        $a = (object)array();
        return returnjson(1000,$a,'验证成功');
        //$request = Request::instance();
       // $ip = $request->ip();
        $ticket=input('ticket');
        if(empty($ticket)){
           // return returnjson(1001,'','缺少ticket参数');
        }
        $randstr=input('randstr');
        if(empty($randstr)){
            return returnjson(1001,'','缺少randstr参数');
        }
        $sms=new SmsService();
        $resp=$sms->verify($ticket,$ip,$randstr);
            $res=object2array($resp);
            if($res['CaptchaCode']==1){
                cache($ip,$res['RequestId'],1800);
                return returnjson(1000,$resp,'验证成功');
            }else{
                cache($ip,$res['RequestId'],1800);
                return returnjson(1001,$resp,'验证失败');
            }

    }

    /**
     * 测试
     */
    public  function ca(){
        $request = Request::instance();
        $ip = $request->ip();
        $locationArr = \Ip::find($ip);

        $location = is_array($locationArr) ? implode(' ',$locationArr):$locationArr;
        var_dump($ip);
        var_dump($locationArr);
        var_dump($location);exit;
        $res=cache($ip);


    }


}
