<?php
namespace app\wxapp\controller;
use app\common\Common;
use app\index\model\HonorLog;
use app\index\model\PosterTemp;
use app\wxapp\model\Collection;
use app\wxapp\model\Colliers;
use app\wxapp\model\CourseBehavior;
use app\wxapp\model\CourseLearnLog;
use app\wxapp\model\CreditSource;
use app\wxapp\model\Dayarticle;
use app\wxapp\model\DedicationLog;
use app\wxapp\model\KnowledgeArticleBehav;
use app\wxapp\model\LearnPowerLog;
use app\wxapp\model\MessageReadLog;
use app\wxapp\model\PosterUser;
use app\wxapp\model\StartLevel;
use app\wxapp\model\TeacherFollow;
use app\wxapp\model\Teachers;
use app\wxapp\model\Tutor;
use app\wxapp\model\Message;
use app\wxapp\model\TutorFollow;
use app\wxapp\model\UserLike;
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
use app\index\model\Advanced;
use Think\Exception;
use app\service\SmsService;
use think\Db;
class User extends Base{

    /*
     * 是否认证
     */
    public function isAuth() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user_model = new \app\wxapp\model\User();
        $is_auth = $user_model->where('id',$this->uid)->value('is_auth');
        if($is_auth == 0) {
            return returnjson(1001,$is_auth,'请去认证');
        }
        return returnjson(1000,$is_auth,'已认证');
    }

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
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user = new \app\wxapp\model\User();
        $startLevel = new StartLevel();
        $userInfo = $user->where('id',$this->uid)->field('id,name,headimg,student_no,level,start_level,signature,score,wechat,tel,is_auth,wximg')->find();
        $tel = Db::name('system')->where('id',1)->value('tel');
        $userInfo['serviceTel'] = $tel;
        if($userInfo){   //is_auth 0未认证 1已提交审核 2已认证 3认证驳回
            $level = new level();
            $mylevel = $level->where('value',$userInfo['level'])->field('name')->find();
            if(empty($userInfo['signature'])){
                $userInfo['signature'] = '';
            }
            $userInfo['levelname'] = $mylevel['name'];
            if($userInfo['start_level'] == 0) {
                $userInfo['start_level'] = '';
            }else{
                $startInfo = $startLevel->field('name')->where('value',$userInfo['start_level'])->find();
                $userInfo['start_level'] = $startInfo['name'];
            }

            return returnjson(1000,$userInfo,'获取成功');
        }
        return returnjson(1001,'','获取失败');
    }
    //等级和勋章
    public function medal(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user = new \app\wxapp\model\User();
        $startLevel = new StartLevel();
        $userInfo = $user->where('id',$this->uid)->field('id,level,start_level,dedication_value,headimg')->find();
        $startInfo = $startLevel->field('name,invite_people,value,small_sq,contribution')->where('is_delete',0)->select();
        foreach($startInfo as $skey=>$svalue){
            $num = $user->where('start_level',$svalue['value'])->count();
            $startInfo[$skey]['count'] = $num;
        }
        if($userInfo){   //is_auth 0未认证 1已提交审核 2已认证 3认证驳回
            $level = new level();
            $mylevel = $level->where('value',$userInfo['level'])->field('name')->find();

            $userInfo['levelname'] = $mylevel['name'];



        }
        $data['userinfo'] = $userInfo;
        $data['startinfo'] = $startInfo;
        return returnjson(1000,$data,'获取成功');
    }
    //行业列表
    public function industry(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $tel = input('tel');
        $type = input('type');
        if($this->uid == 0) {
            return returnjson(1100,'','该用户已在其他设备登陆');
        }
        $industry = Db::name('industry')->order('sort')->select();
        return returnjson(1000,$industry,'获取成功');
    }
    //职业列表
    public function occupation(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $tel = input('tel');
        $type = input('type');
        if($this->uid == 0) {
            return returnjson(1100,'','该用户已在其他设备登陆');
        }
        $industry = Db::name('occupation')->order('sort')->select();
        return returnjson(1000,$industry,'获取成功');
    }
    /*
    * 发送短信
    */
    public function sendcode() {

        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $tel = input('tel');
        $type = input('type');
        if($this->uid == 0) {
            return returnjson(1100,'','该用户已在其他设备登陆');
        }
        if(empty($type)){
            return returnjson(1001,'','参数缺失');
        }
        $user_model = new \app\wxapp\model\User;
        if (!preg_match("/^1[3456789]\d{9}$/", $tel)) {
            //return returnjson(1001, '', '手机号码有误!');
        }
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $myuserinfo = $user_model->where(['id' => $this->uid])->find();
        $str = '1234567890';
        $randStr = str_shuffle($str); //打乱字符串
        $code = substr($randStr, 0, 4); //substr(string,start,length);返回字符串的一部分\
        vendor('aliyun-dysms-php-sdk.api_demo.SmsDemo');
        //$content = ['code' => $code];
        switch ($type) {
            case "up_password":
                //$teltype =
                break;
            case "set_pay_password":

                break;
            case "up_tel":

                break;
            case "new_tel":
                if($tel == $myuserinfo['tel']){
                    return returnjson(1001, '', '更改的手机号不能相同!');
                }
                $userinfo = $user_model->where(['tel' => $tel])->find();
                // $oldtelcode = cache('up_tel'.$userinfo['tel']);
                //if(empty($oldtelcode)){
                //    return returnjson(1, '', '验证码错误!');
                // }
                if ($userinfo) {
                    return returnjson(1001, '', '此手机号已被注册!');
                }
                break;
            default:
                return returnjson(1001, '', '验证码类型错误!');
        }
        //echo $post['type'].$tel;exit;
        $response = new SmsService();
        //echo $post['type'].$tel;exit;
        $res=$response->sendSms($tel,$code,3);

        $res = object_to_array($res);
        //var_dump($res);exit;
        if ($res['SendStatusSet'][0]['Code'] == 'Ok') {
            Cache::set($type.$tel,$code,1800);
            return returnjson(1000, '', '发送成功');
        } else {
            return returnjson(1001, '',$res['SendStatusSet'][0]['Message']);
        }
    }
    //修改手机时验证老号码验证码
    public function checkTelCode(){
        $post = Request::instance()->post();
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        if(empty($post['tel'])||empty($post['code'])){
            return returnjson(1001,'','参数缺失');
        }
        //$code = $post['code'];
        $user = new \app\wxapp\model\User;
        $userInfo = $user->where('id',$this->uid)->field('id,name,headimg,student_no,level,signature,score,wechat,tel')->find();
        if (!Cache::has('up_tel'.$userInfo['tel'])){
            return returnjson(1001,'','验证码错误');
        }
        if (Cache::get('up_tel'.$userInfo['tel']) != $post['code']) {
            return returnjson(1001,'','验证码错误');
        }
        return returnjson(1000,'','验证通过');
    }

    //修改登录密码时验证验证码
    public function checkTelCodePass(){

        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        if(empty($post['tel'])||empty($post['code'])){
            return returnjson(1001,'','参数缺失');
        }
        //$code = $post['code'];
        $user = new \app\wxapp\model\User;
        $userInfo = $user->where('id',$this->uid)->field('id,name,headimg,student_no,level,signature,score,wechat,tel')->find();
        if (!Cache::has('up_password'.$userInfo['tel'])){
            return returnjson(1001,'','验证码错误');
        }
        if (Cache::get('up_password'.$userInfo['tel']) != $post['code']) {
            return returnjson(1001,'','验证码错误');
        }
        return returnjson(1000,'','验证通过');
    }

    //支付密码时验证验证码
    public function checkTelCodePayPass(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        if(empty($post['tel'])||empty($post['code'])){
            return returnjson(1001,'','参数缺失');
        }
        //$code = $post['code'];
        $user = new \app\wxapp\model\User;
        $userInfo = $user->where('id',$this->uid)->field('id,name,headimg,student_no,level,signature,score,wechat,tel')->find();
        if (!Cache::has('set_pay_password'.$userInfo['tel'])){
            return returnjson(1001,'','验证码错误');
        }
        if (Cache::get('set_pay_password'.$userInfo['tel']) != $post['code']) {
            //return returnjson(1001,'','验证码错误');
        }
        return returnjson(1000,'','验证通过');

    }
    public function getjiguangid(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $code = input('code');
        if(empty($code)){
            return returnjson(1001,'','参数缺失');
        }
        $user = new \app\wxapp\model\User;
        $res = $user->where('id',$this->uid)->update(['registrationid'=>$code]);
        return returnjson(1000,'','修改成功');
    }

    /***
     * 修改头像，昵称，签名，密码，手机号等信息--滑块验证
     *
     */
    public function updateInfo($RequestId='') {
        $request = Request::instance();
        $ip = $request->ip();
        if($RequestId!==cache($ip)){
            return returnjson(1001,'','验证码错误');
        }
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        if(empty($post['type'])||empty($post['param'])){
            return returnjson(1001,'','参数缺失');
        }
        $param = $post['param'];
        $user = new \app\wxapp\model\User;
        $userInfo = $user->where('id',$this->uid)->field('id,name,headimg,student_no,level,signature,score,wechat,tel,salt,pay_salt')->find();

        if($post['type'] == 1){
            $key = 'headimg';
        }elseif($post['type'] == 2){
            $key = 'name';
        }elseif($post['type'] == 3){
            $key = 'signature';
        }elseif($post['type'] == 4){
            $key = 'password';
            $param = splice_password($param,$userInfo['salt']);
        }elseif($post['type'] == 5){
            if (!Cache::has('new_tel'.$param)){
                return returnjson(1001,'','验证码错误');
            }
            if (Cache::get('new_tel'.$param) != $post['code']) {
                return returnjson(1001,'','验证码错误');
            }
            $key = 'tel';
        }elseif($post['type'] == 6){

            $key = 'pay_password';
            $param = splice_password($param,$userInfo['pay_salt']);
        }elseif($post['type'] == 7){  // 微信号
            $key = 'wechat';
        }else if($param['type'] == 8) {  // 微信二维码
            $key = 'wximg';
        } else{
            return returnjson(1001,'','修改类型非法');
        }
        $res = $user->where('id',$this->uid)->update([$key=>$param]);

        $userInfo1 = $user->where('id',$this->uid)->field('id,name,headimg,student_no,level,signature,score,wechat,tel,is_auth')->find();
        if($userInfo1){   //is_auth 0未认证 1已提交审核 2已认证 3认证驳回
            $level = new level();
            $mylevel = $level->where('value',$userInfo1)->field('name')->find();
            if(empty($userInfo1['signature'])){
                $userInfo1['signature'] = '';
            }
            $userInfo1['levelname'] = $mylevel['name'];
        }
        if(false !== $res){
            return returnjson(1000,$userInfo1,'修改成功');
        }
        return returnjson(1001,'','修改失败');
    }

    /*
     * 上传头像
     */
    public function uplode_img(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user_model = new \app\wxapp\model\User;
        $user_model->where('id', $this->uid)->update(['token'=>'']);
        return returnjson(1000,'','上传成功');
    }

    //获取身份验证信息
    public function getIdentity(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
            if($this->uid == 0) {
                return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
            }
        }
        $user_model = new \app\wxapp\model\User;
        $userinfo = $user_model->where('id', $this->uid)->field('identityid,realname,is_auth')->find();
        $userinfo['text1'] = '请认真填写资料，以保证资料正确，错误将无法通过用户真实性对比。';
        $userinfo['text2'] = '为确保用户真实性，我们将调用第三方公司的认证系统进行实名认证，认证费用随机1.90～2.10元间（重阳节活动期间暂不收取，活动时间2020/10/23-2020/11/3）认证过程只做用户真实性比对，不做其他用途。';
        $userinfo['text3'] = '活动期间每个人每天只有2次认证机会，超出则需要第二天认证，请仔细核对填写正确，必须人、证、脸一致方可通过。';
        if($userinfo['is_auth'] ==1){
            $userinfo['is_auth'] =1;
            return returnjson(1000,$userinfo,'您已实名认证！');
        }else{
            $userinfo['is_auth'] = 0;
        }

        return returnjson(1000,$userinfo,'');
    }

    //保存实名信息（姓名，身份证号）
    public function saveIdentity(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();
        if(empty($post['realname'])||empty($post['identityid'])){
            return returnjson(1001,'','姓名或身份证号不能为空！');
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        //return returnjson(1001,'','系统繁忙，请稍后再试');
        //超过认证数量今日不再接受认证
        $system_model = new \app\wxapp\model\system();
        $authnum = $system_model->value('authnum');
        if(!empty($authnum)){
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;

            $authwhere['paytime'] = ['between',$beginToday.','.$endToday];
          $authwhere['uid'] = $this->uid;
            $count = Db::name('face_order')->where(['is_auth'=>1])->where($authwhere)->count();
            if($authnum<=$count){
                return returnjson(1001,'','今日已超过验证次数，请明日再来');
            }

        }
        $user_model = new \app\wxapp\model\User();
      	//判断此身份证是佛存在
      	$id_is_has = $user_model->where('is_auth', 1)->where('identityid', $post['identityid'])->find();
      	if(!empty($id_is_has['identityid'])){
        	//return returnjson(1001,'','身份证已被使用！');
        }
        $userinfo = $user_model->field('is_auth,level')->where('id', $this->uid)->find();
        if($userinfo['is_auth']==1){
            return returnjson(1001,'','您已实名认证过！');
        }


        $user_model->where('id', $this->uid)->update(['realname'=>$post['realname'],'identityid'=>$post['identityid']]);
      	$faceorder = Db::name('face_order')->where(['uid'=>$this->uid,'status'=>1])->find();
        if(!empty($faceorder)){
            return returnjson(1002,'','您已付过款！');
        }
        $rdata['money'] = 2;
        $rdata['img'] = 'https://cx.hanshiqiang.com/static/wxapp/img/shiming-icon@3x.png';
        $rdata['title'] = '第三方实名认证服务';

        //$advanced_model = new Advanced();
        //$advancedInfo = $advanced_model->field('reward,value,deadline,pay_type,learn_power')->where('id',$courseInfo['advanced_id'])->find();
        //$pay_types = explode(',',$advancedInfo['pay_type']);
        //$courseInfo['is_score'] = 0;
        $rdata['pay_types'] = array('2');
        $rdata['paytype'] = array('2');
        $rdata['is_alipay'] = 1;
        $rdata['is_wxpay'] = 0;
        // foreach ($pay_types as $val) {
        //    if($val == 1) {
        //$courseInfo['is_score'] = 1;
        //    }else if($val == 2) {
        //       $courseInfo['is_alipay'] = 1;
        //   }else if($val == 3) {
        //      $courseInfo['is_alipay'] = 1;
        //  }
        // }
        //$pay_types = array('1','2');

        return returnjson(1000,$rdata,'已提交');
    }

  	//腾讯人脸获取token

	function get_tengxun_acctoken($uid){
        $token = Db::name('face_token')->where(['id'=>1])->field('tengxun_updatetime,tengxun_access_token')->find();
        $uptime = 7200 + $token['tengxun_updatetime'];
        if(time()<$uptime){
            return $token['tengxun_access_token'];
        }
        $post_data['app_id']      = 'IDAieejR';
        $post_data['secret'] = 'QjlzT0OjhjZ8j4s1TeSfGo7oCJzYMZ2jWAyHL0XjVjmgOdt3Z6V2MAyrKTWpb60G';
      	$post_data['grant_type']       = 'client_credential';
      	$post_data['version']       = '1.0.0';
      	$o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }

      	$post_data = substr($o,0,-1);
      	$url = 'https://idasc.webank.com/api/oauth2/access_token?'.$post_data;
        //echo $url;exit;
        $res = $this->request_get($url);
        $data = json_decode($res,true);
      	//echo "<pre>";
      	//print_r($data);exit;
        $data1['tengxun_updatetime'] = time();

      	$data1['tengxun_access_token'] = $data['access_token'];
        Db::name('face_token')->where(['id'=>1])->update($data1);
        return $data['access_token'];


    }

  	//腾讯人脸获取ticket

  	function get_tengxun_ticket($uid){
        $token = Db::name('face_token')->where(['id'=>1])->field('ticket_update_time,tengxun_ticket')->find();
        $uptime = 1 + $token['ticket_update_time'];
        if(time()<$uptime){
            return $token['tengxun_ticket'];
        }
        $post_data['app_id']      = 'IDAieejR';
        $post_data['access_token'] = $this->get_tengxun_acctoken($uid);
      	$post_data['type']       = 'NONCE';
      	$post_data['version']    = '1.0.0';
      	$post_data['user_id']    = $uid;

        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);
		 $url = 'https://idasc.webank.com/api/oauth2/api_ticket?'.$post_data;

        $res = $this->request_get($url);

        $data = json_decode($res,true);

        $data1['ticket_update_time'] = time();
      	$data1['tengxun_ticket'] = $data['tickets'][0]['value'];
        Db::name('face_token')->where(['id'=>1])->update($data1);
        return $data['tickets'][0]['value'];


    }
    //腾讯人脸获取ticket

  	function get_tengxun_ticket_n($uid){
        $token = Db::name('face_token')->where(['id'=>1])->field('ticket_n_updatetime,tengxun_ticket_n')->find();
        $uptime = 1 + $token['ticket_n_updatetime'];
        if(time()<$uptime){
            return $token['tengxun_ticket_n'];
        }
        $post_data['app_id']      = 'IDAieejR';
        $post_data['access_token'] = $this->get_tengxun_acctoken($uid);
      	$post_data['type']       = 'SIGN';
      	$post_data['version']    = '1.0.0';
      	//$post_data['user_id']    = $uid;

        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);
		 $url = 'https://idasc.webank.com/api/oauth2/api_ticket?'.$post_data;

        $res = $this->request_get($url);

        $data = json_decode($res,true);

        $data1['ticket_n_updatetime'] = time();
      	$data1['tengxun_ticket_n'] = $data['tickets'][0]['value'];
        Db::name('face_token')->where(['id'=>1])->update($data1);
        return $data['tickets'][0]['value'];


    }
  	//签名
  	function get_sign($data){

  	    $arr_test = array_values($data);

        asort($arr_test);

        $arr_test =implode('',$arr_test);

        return sha1($arr_test);
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
  	//前端调起人脸参数
  	function get_param(){

      	$token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }

        if($this->uid == 0) {
            return returnjson(1100,'','该用户已在其他设备登陆');
        }
        $len = strlen($this->uid);
        $uid = $this->getRandom(32 - $len).$this->uid;

      	$data['version'] = '1.0.0';
      	$data['wbappid'] = 'IDAieejR';
      	$data['ticket'] = $this->get_tengxun_ticket($uid);
      	//echo 11111212311111;exit;

      	$data['userId'] = $uid;
      	$data['nonceStr'] = $this->getRandom(32);
      	$si[] = '1.0.0';
      	$si[] = 'IDAieejR';
      	$si[] = $data['ticket'];
      	$si[] = $uid;
      	$si[] = $data['nonceStr'];
      	$res = $this->get_sign($si);
      	$data['sign'] = $res;

      	return returnjson(1000,$data,'');
    }
  	//腾讯 获取人脸id
  	function getfaceid(){
    	$token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();//
        if($this->uid == 0) {
            return returnjson(1100,'','该用户已在其他设备登陆');
        }
      	$post = Request::instance()->post();//
      	if(empty($post['version'])||empty($post['wbappid'])||empty($post['name'])||empty($post['idNo'])||empty($post['userId'])||empty($post['sign'])||empty($post['nonceStr'])){
      	    return returnjson(1001,'','参数缺失');
      	}
      	//var_dump($post);exit;
      	$data['version'] = $post['version'];
      	$data['webankAppId'] = $post['wbappid'];
      	$data['orderNo'] = 'txfc'.time();
      	$data['name'] = $post['name'];
      	$data['idNo'] = $post['idNo'];
      	$data['sourcePhotoStr'] = '';
      	$data['sourcePhotoType'] = 2;
      	//if(isset($post['uid'])){
      	  //  $data['userId'] = $post['uid'];
      	//}else{
      	    $data['userId'] = $post['userId'];
      	//}


      	$data['sign'] =$post['sign'];

      	$url = 'https://idasc.webank.com/api/server/getfaceid';
      	$res = $this->json_post($url, $data);
      	$reslut = json_decode($res,true);
      	//var_dump($reslut);exit;
        if($reslut['code']==0){
          	if($reslut['result']['success'] ===false){
            	//return returnjson(1001,$data,'失败');
            }

          	$data['wbappid'] = $post['wbappid'];
          	$data['nonceStr'] =$post['nonceStr'];
          	$data['faceId'] = $reslut['result']['faceId'];
          	$data['keyLicence'] = 'kaTdEV0NWMJ2g4cnHSftAw/PgUrvIRfi0p4VTyu/bcg0gQNdXjsEIE7WZX2Lp9G76N8fEFyV92LFS7yYcyIKuh16dHRdIJNL/1FlaxDAlW+Vi/+fNGxyDRWFuO4NRyCJM1zx3x1olNlZEdwwTPELkUA+84OE1lLgx6Ml6rHup/h4Mc24MTYWEHTFsTC8VYi53Eh2UNm1LhJ01UKmCiwmdlTNMKp6rpFxyRbj+JqNoAMdAqOYcKP/qeCXsM0YTMYPrxmUyEDhiKfKYPlue9hcoOzyoEU9rRTdDcZFa2VnMdcD+i9y+0ReKzd8YjRh0LNrMxmOhXFZKJgnQT3XJz3l/A==';

        	return returnjson(1000,$data,'验证通过');
        }
    }
  	function json_post($url, $data = NULL)
    {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(!$data){
            return 'data is null';
        }
        if(is_array($data))
        {
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER,array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length:' . strlen($data),
                'Cache-Control: no-cache',
                'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;

    }

  	//腾讯查询是否认证成功
  	function sync(){
    	$token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();
        if($this->uid == 0) {
            return returnjson(1100,'','该用户已在其他设备登陆');
        }
      	$post = Request::instance()->post();//
      	$forderwhere['paytime'] = array('neq',0);
                $forderwhere['uid'] = $this->uid;
          		//if($this->uid == 392||$this->uid == 398||$this->uid == 23850){
                 // return returnjson(1001,'','请先支付!');
        $face_order = Db::name('face_order')->where($forderwhere)->find();
        if(!$face_order){

             return returnjson(1001,'','请先支付!');
        }
               // }
      	if(empty($post['order_no'])){
      	    return returnjson(1001,'','参数缺失');
      	}
     // return returnjson(1001,'','太晚了，早点休息哦');
      	$myorder = '';
      	$myorder = Db::name('face_order')->where(['uid'=>$this->uid,'status'=>1,'is_auth'=>1])->find();
      	if(!$myorder){
        	//return returnjson(1001,'','请前去付款');
        }
      	$len = strlen($this->uid);
        $uid = $this->getRandom(32 - $len).$this->uid;
      	$data['version'] = '1.0.0';
      	$data['wbappid'] = 'IDAieejR';

      	$data['order_no'] = $post['order_no'];
      	$data['api_ticket'] = $this->get_tengxun_ticket_n($uid);
      	$data['nonceStr'] = $this->getRandom(32);
      	$res = $this->get_sign($data);

        $data1['sign'] = $res;
      	$data1['version'] = '1.0.0';
      	$data1['app_id'] = 'IDAieejR';
      	$data1['nonce'] = $data['nonceStr'];

        $data1['order_no'] = $post['order_no'];

        $o = "";
        foreach ( $data1 as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $data = substr($o,0,-1);
      	$url = 'https://idasc.webank.com/api/server/sync?'.$data;
        $res = $this->request_get($url);
      $reslut = array();
      	$reslut = json_decode($res,true);
        //-------------------------------------------
        Db::name('face_log')->insert(['uid'=>$this->uid,'addtime'=>time()]);
        //return $res;


        //-------------------------------------------------
        if($reslut['code']==0){
            $user_model= new \app\wxapp\model\User;

            $user = $user_model->where(['id'=>$this->uid])->field('identityid,realname,is_auth,level,pid')->find();


               // $face_order = Db::name('face_order')->where($forderwhere)->update(['is_auth'=>1]);
              	if($user['pid']){
                	$user_model->where(['id'=>$user['pid']])->setInc('invate_num',1);
                }
              	//if(!$face_order){
                	// Db::rollback();
                   // return returnjson(1001,'','认证失败!');
                //}

              	Db::startTrans();
              	$user_model->where(['id'=>$this->uid])->update(['is_auth'=>1]);
                $common = new Common();

                if(false === $common->userChangeLevel($this->uid)){
                    Db::rollback();
                    return returnjson(1001,'','认证失败');
                }
                Db::commit();
        	return returnjson(1000,array('result'=>1),'验证通过');
        }else{
            return returnjson(1001,array('result'=>0),'验证未通过');
        }
    }

    //活体检测 获取token
    function get_acctoken(){
       /* $token = Db::name('face_token')->where(['id'=>1])->field('updatetime,access_token')->find();
        $uptime = 2592000 + $token['updatetime'];
        if(time()<$uptime){
            return $token['access_token'];
        }
        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $post_data['grant_type']       = 'client_credentials';
        $post_data['client_id']      = '2dfgWGhO6t8BGuoc68c5ccs5';
        $post_data['client_secret'] = '9dXylI1q4aqF3l5RiQTjWEHOj1Ds7siS';
        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);

        $res = $this->request_post($url, $post_data);
        $data = json_decode($res,true);
        $data['updatetime'] = time();
        Db::name('face_token')->where(['id'=>1])->update($data);
        return $data['access_token'];*/
        //return returnjson(1000,json_decode($res),'已提交');
        //var_dump($res);

    }
    //公安验证活体检测api
    function faceverify(){
       /* $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();//
        if($this->uid == 0) {
            return returnjson(1100,'','该用户已在其他设备登陆');
        }
        $image = $post['image'];
        if(empty($image)) {
            return returnjson(1001,'','参数不能为空');
        }

        $user_model= new \app\wxapp\model\User;
        $user = $user_model->where(['id'=>$this->uid])->field('identityid,realname,is_auth,level,pid')->find();
        if($user['is_auth']==1){
            return returnjson(1001,'','您已验证通过,无需再次验证!');
        }
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $count = Db::name('face_log')->where(['uid'=>$this->uid])->where(['addtime'=>['between',[$beginToday,$endToday]]])->count();

        if($count>=3){
            return returnjson(1001,'','今日已超过验证次数!');
        }
        $token1 = $this->get_acctoken();
        $url = 'https://aip.baidubce.com/rest/2.0/face/v3/person/verify?access_token=' . $token1;
        $bodys['image'] = $image;
        $bodys['image_type'] = 'BASE64';
        //$bodys['image_type'] = 'URL';
        $bodys['id_card_number'] = $user['identityid'];
        $bodys['name'] = $user['realname'];
        $bodys = json_encode($bodys);

        $res = $this->request_post($url, $bodys);
        Db::name('face_log')->insert(['uid'=>$this->uid,'addtime'=>time()]);
        //return $res;
        $reslut = json_decode($res,true);
        if($reslut['error_code']==0){
            if($reslut['result']['score']>=80){
              	$studentMake = $this->studentMake();
                $user_model->where(['id'=>$this->uid])->update(['is_auth'=>1]);
                $forderwhere['paytime'] = array('neq',0);
                $forderwhere['uid'] = $this->uid;
                $face_order = Db::name('face_order')->where($forderwhere)->update(['is_auth'=>1]);
              	if($user['pid']){
                	$user_model->where(['id'=>$user['pid']])->setInc('invate_num',1);
                }
              	if(!$face_order){
                	 Db::rollback();
                    return returnjson(1001,'','认证失败');
                }


                $common = new Common();
                Db::startTrans();
                if(false === $common->userChangeLevel($this->uid)){
                    Db::rollback();
                    return returnjson(1001,'','认证失败');
                }
                Db::commit();
                return returnjson(1000,'','验证通过');
            }else{
                return returnjson(1001,'','未通过验证');
            }
        }else{
            return returnjson(1001,'','未通过验证!');
        }*/
    }
	/*
     * 生成学号
     */
    public function studentMake() {
        $student_no = get_rand_char(8);
        $user_model = new \app\wxapp\model\User;
        if($user_model->where('student_no',$student_no)->find()){
            return $this->studentMake();
        }else{
            return $student_no;
        }
    }

    function request_post($url = '', $param = '') {
        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        $curl = curl_init();//初始化curl
        curl_setopt($curl, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($curl, CURLOPT_HEADER, 0);//设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_POST, 1);//post提交方式

        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($curl);//运行curl
        curl_close($curl);

        return $data;
    }

	function request_get($url = ''){
      	if (empty($url)) {
            return false;
        }
    	//初始化
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        //执行并获取HTML文档内容
        $output = curl_exec($ch);

        //释放curl句柄
        curl_close($ch);

        //打印获得的数据
        return $output;
    }


    //退出登录
    public function outlogin(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $user_model = new \app\wxapp\model\User;
        $user_model->where('id', $this->uid)->update(['token'=>'']);
        return returnjson(1000,'','已退出登录');
    }

    /*
     * 邀请贡献值奖励
     */
    public function invateReward() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $colliers = new Colliers();
        $user_model = new \app\wxapp\model\User();
        $colliers = $colliers->where('type',6)->find();
        if($colliers) {
            $dedication = $colliers['contribution'];
        }else{
            $dedication = 0;
        }
        $userinfo = $user_model->field('invate_num,dedication_value,pid')->where('id',$this->uid)->find();
        $systemInfo = $this->systeminfo;
        if(empty($userinfo['pid'])){
            $userinfo['pid'] = 0;
        }
      	$invate_num = $user_model->where(['pid'=>$this->uid,'is_auth'=>1])->count();
        $url =  'https://'.$_SERVER['HTTP_HOST'].'/wxapp/Login/h5_register?p_id='.$this->uid;
      	 $common_model = new common();
        $res = $common_model->isMaxOrMin($this->uid,1,1);
        $data = [
            'dedication'=>$dedication,
            'invateCount'=>$res['total_num'],
            'all_dedicat'=>$userinfo['dedication_value'],
            'inviteimg'=>$systemInfo['inviteimg'],
            'introduce'=>$systemInfo['introduce'],
            'pid' =>$userinfo['pid'],
            'link' => $url
        ];
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 我的二维码
     * @param string $url
     * @return string
     */
    public function myErQrcode() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $data = [
            'name'=>$this->userInfo['name'],
            'student_no'=>$this->userInfo['student_no'],
            'headimg'=>$this->userInfo['headimg']
        ];
        $qrcode_img = $this->userInfo['qrcode_img'];
        if(!empty($qrcode_img)) {
            $data['qrcode_img'] = $qrcode_img;
            return returnjson(1000,$data,'获取成功');
        }
        include '../vendor/phpqrcode/phpqrcode.php';
        $Qr = new \phpqrcode\QRcode();
        $level = 'H';
        $size = 10;
        $filename = $this->uid .'myqrcode'.'.jpg';
        $outfile = "qrcode/".$filename;
        ob_start();
        $url = GetCurUrl()."/wxapp/login/h5_register/p_id/".$this->uid;
        $Qr->png($url,$outfile,$level,$size,0);
        ob_end_clean();

        $qrcodeUrl = uploadLocalToOss($outfile, "qrcode",$filename);
        unlink($outfile);
        // 保存二维码图片路径
        $user_model = new \app\wxapp\model\User();
        $user_model->where('id',$this->uid)->update(['qrcode_img'=>$qrcodeUrl]);
        $data['qrcode_img'] = $qrcodeUrl;
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 我的班级  我邀请的人
     */
    public function myInvateList($page = 1) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $start = ($page - 1) * $this->num;
        $user_model = new \app\wxapp\model\User();
        $where = ['pid'=>$this->uid];
        $limit = $start.','.$this->num;
        $data = $user_model->getApiUserList($where,$limit);
        return returnjson(1000,$data,'获取成功');
    }

    /*
     *  我的-个人主页-我看到的
     */
    public function myIndexInfo() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user_model = new \app\wxapp\model\User();
        $tutorFollow_model = new \app\wxapp\model\TutorFollow();
        $userInfo = $user_model->field('headimg,name,student_no,signature')->where('id',$this->uid)->find();
        $gzCount = $tutorFollow_model->where('tutor_id',$this->uid)->count();
        $beGzCount = $tutorFollow_model->where('uid',$this->uid)->count();
        $userInfo['gzCount'] = $gzCount;
        $userInfo['beGzCount'] = $beGzCount;
        return returnjson(1000,$userInfo,'获取成功');
    }

    /*
    *  我的-个人主页-别人看到的
    */
    public function heIndexInfo() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user_id = input('uid'); // 别人的id
        $user_model = new \app\wxapp\model\User();
        $tutorFollow_model = new \app\wxapp\model\TutorFollow();
        $userInfo = $user_model->field('headimg,name,student_no,signature')->where('id',$user_id)->find();
        $gzCount = $tutorFollow_model->where('tutor_id',$user_id)->count();
        $beGzCount = $tutorFollow_model->where('uid',$user_id)->count();
        $userInfo['gzCount'] = $gzCount;
        $userInfo['beGzCount'] = $beGzCount;
        // 是否关注
        if($tutorFollow_model->where(['tutor_id'=>$user_id,'uid'=>$this->uid])->find()){
            $userInfo['is_follow'] = 1;
        }else{
            $userInfo['is_follow'] = 0;
        }
        return returnjson(1000,$userInfo,'获取成功');
    }

    /*
     * 我的-个人主页-我看到的
     */
    public function myCourses() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $order_model = new Orders();
        $page = input('page') ? input('page') : 1;
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        $where = ['o.uid'=>$this->uid,'o.status'=>['in',[1,3]]];
        return $order_model->getLearnCourse($where,$limit);
    }

    /*
     * 我的-个人主页-别人看到的
     */
    public function heCourses() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user_id = input('uid'); // 别人的id
        $order_model = new Orders();
        $page = input('page');
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        $where = ['o.uid'=>$user_id,'o.status'=>['in',[1,3]]];
        return $order_model->getLearnCourse($where,$limit);
    }

    /*
    * 我的-认证导师个人主页-我看到的
    */
    public function myTeacherInfo() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $teacher_model = new Teachers();
        $teacherFollow = new TeacherFollow();
        $where = ['uid'=>$this->uid];
        $teacherInfo = $teacher_model->field('headimg,name')->where($where)->find();
        $beGzCount = $teacherFollow->where(['teacher_id'=>$this->uid])->count();
        $gzCount = $teacherFollow->where(['uid'=>$this->uid])->count();
        $teacherInfo['beGzCount'] = $beGzCount;
        $teacherInfo['gzCount'] = $gzCount;
        return returnjson(1000,$teacherInfo,'获取成功');
    }

    /*
     * 我的-认证导师个人主页-我看到的 课程列表
     */
    public function myWorks() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $course_model = new Course();
        $teacher_model = new Teachers();
        $page = input('page');
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        $teacher_id = $teacher_model->where('uid',$this->uid)->value('id');
        $where = ['teacher_id'=>$teacher_id];
        return $course_model->getTutorCourse($where,$limit);
    }

    /*
    * 我的-认证导师个人主页-别人看到的
    */
    public function heTeacherInfo() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $user_id = input('uid');
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $teacher_model = new Teachers();
        $teacherFollow = new TeacherFollow();
        $where = ['uid'=>$user_id];
        $teacherInfo = $teacher_model->field('headimg,name')->where($where)->find();
        $beGzCount = $teacherFollow->where(['teacher_id'=>$user_id])->count();
        $gzCount = $teacherFollow->where(['uid'=>$this->uid])->count();
        $teacherInfo['beGzCount'] = $beGzCount;
        $teacherInfo['gzCount'] = $gzCount;
        if($teacherFollow->where(['teacher_id'=>$user_id,'uid'=>$this->uid])->find()){
            $teacherInfo['is_follow'] = 1;
        }else{
            $teacherInfo['is_follow'] = 0;
        }
        return returnjson(1000,$teacherInfo,'获取成功');
    }

    /*
     * 我的-认证导师个人主页-我看到的 课程列表
     */
    public function heWorks() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user_id = input('uid');
        $course_model = new Course();
        $teacher_model = new Teachers();
        $page = input('page');
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        $teacher_id = $teacher_model->where('uid',$user_id)->value('id');
        $where = ['teacher_id'=>$teacher_id];
        return $course_model->getTutorCourse($where,$limit);
    }

    /*
     * 我的学分明细
     */
    public function myScoreInfo() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user_model = new \app\wxapp\model\User();
        $creditSource = new CreditSource();
        $userInfo = $user_model->field('learning_power,dedication_value,honor_value,score')->where('id',$this->uid)->find();
        $creditSource = $creditSource->field('score,id')->where(['uid'=>$this->uid,'status'=>1])->order('addtime desc')->find();
        if($creditSource) {
            $userInfo['newSource'] = $creditSource;
        }else{
            $userInfo['newSource'] = array('score'=>'0.0000','id'=>0);
        }
        $notices = Db::name('notice')->field('id,name')->select();
        $count = Db::name('notice')->field('id,name')->count();
        $userInfo['content1'] = $notices;
        if($count>2 && 1==2){
            $r1 = (rand(0,$count-1));
            $r2 = (rand(0,$count-1));
            while($r1 == $r2){
                $r2 = (rand(0,$count-1));
            }
            $userInfo['content1'] = $notices[$r1]['name'];
            $userInfo['content2'] = $notices[$r2]['name'];
        }else{
            //$userInfo['content1'] = '你对学习的坚持，我们看得到!';
            //$userInfo['content2'] = '听完50条音频，拓宽你的知识领域。';
        }

        return returnjson(1000,$userInfo,'获取成功');
    }

    /*
     * 学分明细
     */
    public function scoreLog() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $creditSource = new CreditSource();
        $user_model = new \app\wxapp\model\User();
        $frozenScore = $user_model->where('id',$this->uid)->value('score');
        $effectScore = $creditSource->where(['uid'=>$this->uid,'status'=>0])->sum('score');
        $eclectic = "0.00";
        $data = ['frozenScore'=>$frozenScore,'effectScore'=>$effectScore,'eclectic'=>$eclectic];
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 学分明细情况-2
     */
    public function scoreLogList() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $search = input();
        if($search['datetype'] == 0) {
            list($startTime, $endTime) = Time::month();
        }else  if($search['datetype'] == 1){
            $date = explode('-',$search['date']);
            $timeArr = getMonthBeginAndEnd($date[0], $date[1],0);
            $startTime = $timeArr['startTime'];
            $endTime = $timeArr['endTime'];
        }else if($search['datetype'] == 2) {
            $arrDate = explode(',',$search['date']);
            $startTime = strtotime($arrDate[0]);
            $endTime = strtotime($arrDate[1]);
        }
        $where = ['addtime'=>['between',[$startTime,$endTime]],'status'=>1];
        if(isset($search['type'])) {
            if($search['type']) {
                if($search['type']==6){
                    $search['type'] =10;
                }
                $where['type'] = $search['type'];
            }
        }
        if(isset($search['min']) && isset($search['max'])) {
            $min = floatval($search['min']);
            $max = floatval($search['max']);
            if($max < $min) {
                return returnjson(1001,'','最小值不能大于最大值');
            }
            $where['score'] = ['between',[$min,$max]];
        }
        $where['uid'] = $this->uid;
        $creditSource = new CreditSource();
        $page = $search['page'];
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        return $creditSource->getApiScoreLogList($where,$limit);
    }


    /*
     * 学习力 -1
     */
    public function learnPower() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $learnPower = new LearnPowerLog();
        $user_model = new \app\wxapp\model\User();
      	 $common_model = new common();
        $res = $common_model->isMaxOrMin($this->uid,1);
        $frozenLearningPower = $user_model->where('id',$this->uid)->value('learning_power');
        $effectPower = $learnPower->where(['uid'=>$this->uid,'status'=>0])->sum('value');
      	$userInfo = $user_model->where('id',$this->uid)->find();
      	if(empty($userInfo['bonus_learn_power'])){
        	$userInfo['bonus_learn_power'] = 0;
        }

        $data = ['frozenLearningPower'=>$frozenLearningPower,'effectPower'=>$effectPower,'basicsLearning'=>$userInfo['learning_power'],'additionLearning'=>$userInfo['bonus_learn_power'],'comLearning'=>$res['all_power'],'smallComLearning'=>$res['min_dedication_value']];
        return returnjson(1000,$data,'获取成功');
    }


    /*
     * 学习力明细
     */
    public function learnPowerLog() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $search = input();
        if($search['datetype'] == 0) {
            list($startTime, $endTime) = Time::month();
        }else  if($search['datetype'] == 1){
            $date = explode('-',$search['date']);
            $timeArr = getMonthBeginAndEnd($date[0], $date[1],0);
            $startTime = $timeArr['startTime'];
            $endTime = $timeArr['endTime'];
        }else if($search['datetype'] == 2) {
            $arrDate = explode('-',$search['date']);
            $startTime = strtotime($arrDate[0]);
            $endTime = strtotime($arrDate[1]);
        }
        $where = ['addtime'=>['between',[$startTime,$endTime]],'status'=>1];
        if(isset($search['type'])) {
            if($search['type']) {
                $where['type'] = $search['type'];
            }
        }
        if(isset($search['min']) && isset($search['max'])) {
            $min = floatval($search['min']);
            $max = floatval($search['max']);
            if($max < $min) {
                return returnjson(1001,'','最小值不能大于最大值');
            }
            $where['value'] = ['between',[$min,$max]];
        }
        $where['uid'] = $this->uid;
        $learnPower = new LearnPowerLog();
        $page = $search['page'];
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        return $learnPower->getApiLearnPowerList($where,$limit);
    }

    /*
     *  用户贡献值数据
     */
    public function dedicationDetail() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user_model = new \app\wxapp\model\User();
        $data = $user_model->where('id',$this->uid)->value('dedication_value');
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 贡献值明细
     */
    public function dedicationLog() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $search = input();
        if($search['datetype'] == 0) {
            list($startTime, $endTime) = Time::month();
        }else  if($search['datetype'] == 1){
            $date = explode('-',$search['date']);
            $timeArr = getMonthBeginAndEnd($date[0], $date[1],0);
            $startTime = $timeArr['startTime'];
            $endTime = $timeArr['endTime'];
        }else if($search['datetype'] == 2) {
            $arrDate = explode('-',$search['date']);
            $startTime = strtotime($arrDate[0]);
            $endTime = strtotime($arrDate[1]);
        }
        $where = ['addtime'=>['between',[$startTime,$endTime]]];
        if(isset($search['type'])) {
            if($search['type']) {
                $where['type'] = $search['type'];
            }
        }
        if(isset($search['min']) && isset($search['max'])) {
            $min = floatval($search['min']);
            $max = floatval($search['max']);
            if($max < $min) {
                return returnjson(1001,'','最小值不能大于最大值');
            }
            $where['value'] = ['between',[$min,$max]];
        }
        $where['uid'] = $this->uid;
        $dedicationLog = new DedicationLog();
        $page = $search['page'];
        if($page==0){
            $page = 1;
        }
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;

        return $dedicationLog->getApiDedicationList($where,$limit);
    }

    /*
     * 荣誉值详情
     */
    public function hornerLogDetail() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user_model = new \app\wxapp\model\User();
        $data = $user_model->where('id',$this->uid)->value('honor_value');
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 荣誉值明细
     */
    public function hornerList() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $search = input();
        $where = [];
        if($search['datetype'] == 0) {
            list($startTime, $endTime) = Time::month();
        }else  if($search['datetype'] == 1){
            $date = explode('-',$search['date']);
            $timeArr = getMonthBeginAndEnd($date[0], $date[1],0);
            $startTime = $timeArr['startTime'];
            $endTime = $timeArr['endTime'];
        }else if($search['datetype'] == 2) {
            $arrDate = explode('-',$search['date']);
            $startTime = strtotime($arrDate[0]);
            $endTime = strtotime($arrDate[1]);
        }
        $where = ['addtime'=>['between',[$startTime,$endTime]]];
        if(isset($search['type'])) {
            if($search['type']) {
                $where['type'] = $search['type'];
            }
        }
        $where['uid'] = $this->uid;
        $hornorLog = new HonorLog();
        $page = $search['page'];
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        return $hornorLog->getApiHornorList($where,$limit);
    }


    /*
     * 学分详情
     */
    public function scoreDetail($id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $creditSource = new CreditSource();
        $order_model = new Orders();
        $creditInfo = $creditSource->field('value,addtime,note,type,pay_type,score,status')->where('id',$id)->find();
        $creditInfo['addtime'] = date('Y-m-d H:i:s',$creditInfo['addtime']);
        $creditInfo['status'] = '已完成';
        if($creditInfo['pay_type'] == 1) {
            $creditInfo['pay_type'] = '现金购买';
        }else{
            $creditInfo['pay_type'] = '学分购买';
        }
        switch ($creditInfo['type']) {
            case 1:
                $creditInfo['typeTxt'] = '学习收入';
                $section_model = new Sectiones();
                $creditInfo['courseName'] = $section_model->where('id',$creditInfo['value'])->value('name');
                $creditInfo['order_id'] = '';
                $creditInfo['course_id'] = '';
                break;
            case 2:
                $creditInfo['typeTxt'] = '文章赞赏';
                $creditInfo['courseName'] = '';
                $creditInfo['order_id'] = '';
                $creditInfo['course_id'] = '';
                break;
            case 3:
                $creditInfo['typeTxt'] = '兑入';
                $creditInfo['courseName'] = '';
                $creditInfo['order_id'] = '';
                $creditInfo['course_id'] = '';
                break;
            case 4:
                $creditInfo['typeTxt'] = '兑出';
                $creditInfo['courseName'] = '';
                $creditInfo['order_id'] = '';
                $creditInfo['course_id'] = '';
                break;
            case 5:
                $creditInfo['typeTxt'] = '课程购买';
                $course_model = new Course();
                $orderInfo = $order_model->field('order_id,course_id')->where('id',$creditInfo['value'])->find();
                $courseName = $course_model->where('id',$orderInfo['course_id'])->value('name');
                $creditInfo['courseName'] = $courseName;
                $creditInfo['order_id'] = $orderInfo['order_id'];
                $creditInfo['course_id'] = $orderInfo['course_id'];
                unset($creditInfo['value']);
                break;
            default:
                $data = [];
                break;
        }
        return returnjson(1000,$creditInfo,'获取成功');
    }

    /*
     * 学分详情
     */
    public function scoreDetailIos($id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $creditSource = new CreditSource();
        $order_model = new Orders();
        $creditInfo = $creditSource->field('value,addtime,note,type,pay_type,score,status')->where('id',$id)->find();

        $creditInfo['addtime'] = date('Y-m-d H:i:s',$creditInfo['addtime']);
        $creditInfo['status'] = '已完成';
        if($creditInfo['pay_type'] == 1) {
            $creditInfo['pay_type'] = '现金购买';
        }else{
            $creditInfo['pay_type'] = '学分购买';
        }
        switch ($creditInfo['type']) {
            case 1:
                $creditInfo['typeTxt'] = '学习收入';
                $section_model = new Sectiones();
                $creditInfo['courseName'] = $section_model->where('id',$creditInfo['value'])->value('name');
                $creditInfo['order_id'] = '';
                $creditInfo['course_id'] = '';
                break;
            case 2:
                $creditInfo['typeTxt'] = '文章赞赏';
                $creditInfo['courseName'] = '';
                $creditInfo['order_id'] = '';
                $creditInfo['course_id'] = '';
                break;
            case 3:
                $creditInfo['typeTxt'] = '兑入';
                $creditInfo['courseName'] = '';
                $creditInfo['order_id'] = '';
                $creditInfo['course_id'] = '';
                break;
            case 4:
                $creditInfo['typeTxt'] = '兑出';
                $creditInfo['courseName'] = '';
                $creditInfo['order_id'] = '';
                $creditInfo['course_id'] = '';
                break;
            case 5:
                $creditInfo['typeTxt'] = '课程购买';
                $course_model = new Course();
                $orderInfo = $order_model->field('order_id,course_id')->where('id',$creditInfo['value'])->find();
                $courseName = $course_model->where('id',$orderInfo['course_id'])->value('name');
                $creditInfo['courseName'] = $courseName;
                $creditInfo['order_id'] = $orderInfo['order_id'];
                $creditInfo['course_id'] = $orderInfo['course_id'];
                unset($creditInfo['value']);
                break;
            default:
                $data = [];
                break;
        }
        $credit = [
            ['title'=>'学分','detail'=>$creditInfo['score']],
            ['title'=>'订单号','detail'=>$creditInfo['order_id']],
            ['title'=>'类型','detail'=>$creditInfo['typeTxt']],
            ['title'=>'支付方式','detail'=>$creditInfo['pay_type']],
            ['title'=>'时间','detail'=>$creditInfo['addtime']],
            ['title'=>'状态','detail'=>$creditInfo['status']],
            ['title'=>'备注','detail'=>$creditInfo['note']]
        ];
        return returnjson(1000,$credit,'获取成功');
    }

    /*
     * 我的收藏
     */
    public function myCollection() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $page = input('page');
        $type = input('type');
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        if($type == 1) {  // 课程
            $courseBehavior = new CourseBehavior();
            $where = ['uid'=>$this->uid,'type'=>2];
            return $courseBehavior->getList($where,$limit);
        }else if($type == 2) { // 文章
            $articleBehavior = new KnowledgeArticleBehav();
            $where = ['uid'=>$this->uid,'type'=>2];
            return $articleBehavior->getList($where,$limit);
        }else {
            return returnjson(1001,'','非法类型');
        }

    }

    /*
     *
     */
    public function myTeam() {

    }

    public function sharePoster() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $postUrl = "https://cx.hanshiqiang.com/uploads/20200513/a9334eb6787f6fac989847bf7d760256.png";
        $colliers = new Colliers();
        $value = $colliers->where('type',6)->value('contribution');
        $data = ['posterUrl'=>$postUrl,'value'=>$value];
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 我的班级
     */
    public function inviteClass() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user_model = new \app\wxapp\model\User();
        $level1 = $user_model->where('id',$this->uid)->value('level');
		$myinfo = $user_model->where('id',$this->uid)->find();
        $childCount = $user_model->where('pid',$this->uid)->where('is_auth',1)->count();
        $common_model = new common();
        $res = $common_model->isMaxOrMin($this->uid,1,1);
      $res1 = $common_model->isMaxOrMin($this->uid,1);
        if($res['is_max']==1){
            $sameGrade = $res['pnum'];
            $otherGrade = $res['other'];
        }else{
            $sameGrade = $res['other'];
            $otherGrade = $res['pnum'];
        }
        $pid  = $myinfo['pid'];
        if($pid){
            $puserInfo = $user_model->where('id',$pid)->field('id,name,headimg')->find();
            $noAuthNum = $user_model->where('pid',$this->uid)->where('is_auth',0)->count();

        }else{
            $puserInfo =array();

        }
        $level = new level();
        $mylevel = $level->where('value',$level1)->value('name');
        $invite_class_content = Db::name('system')->where('id',1)->value('invite_class_content');
        $data = ['level'=>$mylevel,'childCount'=>$res['direct'],'sameGrade'=>$sameGrade,'otherGrade'=>$otherGrade,'sumContribution'=>$myinfo['dedication_value'],'comContribution'=>$res['minshequnD'],'alumnusSum'=>$res['total_num'],'authenticatedNumber'=>$res1['total_num'],'puserInfo'=>$puserInfo,'invite_class_content'=>$invite_class_content,'authNum'=>$childCount,'noAuthNum'=>$noAuthNum];
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 我的下级
     */
    public function myChildren() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $page = input('page') ? input('page') : 1;
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        $user_model = new \app\wxapp\model\User();
        $level = new level();
        if(!empty(input('keyword'))){
            $keyword = input('keyword');

            $userList = $user_model->field('id,name,headimg,score,is_auth,level')->where('name|student_no|tel','=',$keyword)->where('pid',$this->uid)->limit($limit)->select();
        }else{
            $userList = $user_model->field('id,name,headimg,score,is_auth,level')->where('pid',$this->uid)->limit($limit)->select();
        }

      $common_model = new common();

        if($userList) {
            foreach ($userList as $k=>$val) {
                if($val['is_auth'] == 0) {
                    $is_auth = '未实名';
                }else{
                    $is_auth = '已实名';
                }
              	//$count = $user_model->where(['pid'=>$val['id'],'is_auth'=>1])->count();
              $res = $common_model->isMaxOrMin($val['id'],1,1);
              $userList[$k]['score'] = $res['total_num'];
                $mylevel = $level->where('value',$val['level'])->value('name');
                $userList[$k]['levelname'] = $mylevel;
                $userList[$k]['is_auth'] = $is_auth;
            }
        }
        return returnjson(1000,$userList,'获取成功');
    }

    /*
     * 海报
     */
    public function createqr() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $post_temp = new PosterTemp();
        $postUser = new PosterUser();
        $posterList = $post_temp->where('status',1)->select();
        $urls = [];
        foreach ($posterList as $k=>$val) {
            $posterUserInfo = $postUser->where(['uid'=>$this->uid,'poster_id'=>$val['id']])->find();
            if($val['type'] == 0) {
                $imgUrl = $this->creditPoster($this->uid,$val['id'],$val['type']);
                $data = ['uid'=>$this->uid,'poster_img'=>$imgUrl,'poster_id'=>$val['id']];
                $postUser->insert($data);
                if($imgUrl) {
                    $urls[] = $imgUrl;
                }
            }else{
                if($posterUserInfo){
                    $urls[] = $posterUserInfo['poster_img'];
                }else{
                    $imgUrl = $this->creditPoster($this->uid,$val['id'],$val['type']);
                    $data = ['uid'=>$this->uid,'poster_img'=>$imgUrl,'poster_id'=>$val['id']];
                    $postUser->insert($data);
                    if($imgUrl) {
                        $urls[] = $imgUrl;
                    }
                }
            }
        }
        return returnjson(1000,$urls,'获取成功');
    }

    public function creditPoster($uid = 0,$poster_id = 0,$tempType = 0){
        $post_temp = new PosterTemp();
        $user_model = new \app\wxapp\model\User();
        $courseLearn = new CourseLearnLog();
        $section_model = new Sectiones();
        $colliers = new Colliers();
        $posterImg = $post_temp->where('id',$poster_id)->value('url');
        $posterText = $post_temp->where('id',$poster_id)->value('note');
        require_once '../vendor/phpqrcode/phpqrcode.php';
        $fontfile = '../vendor/topthink/think-captcha/assets/zhttfs/3.ttf';   //苹方黑体-极细-简
        //$fontsize = 12;
       // $fontsize1 = 14;
      $fontsize = 32;
        $fontsize1 = 30;
        // 第一步: 生成二维码
        $Qr = new \phpqrcode\QRcode();
        $value = GetCurUrl()."/wxapp/login/h5_register/p_id/".$uid;
        $errorCorrectionLevel = 'H';//容错级别
        $matrixPointSize = 7;//生成图片大小
        $newQrcodeUrl = "qrcode/".$uid.'qrcode.png';
        $Qr->png($value, $newQrcodeUrl, $errorCorrectionLevel, $matrixPointSize, 2);

        // 第二步 创建画布
        $im = imagecreatetruecolor(552,1194);
        $color =  imagecolorallocate($im,255,255,255);
        imagefill($im,0,0,$color);
        $textArray = explode(',',$posterText);
        if($tempType == 0) {
            // 第三部 将模板与二维码合并
            $QR = $posterImg;  //原图像
            $logo = $newQrcodeUrl; //要处理的图形

            if ($logo !== FALSE) {
                $QR = imagecreatefromstring(file_get_contents($QR));         //读取原图像
                $logo = imagecreatefromstring(file_get_contents($logo));    //读取二维码图像
                $QR_width = imagesx($QR);   //原图像宽度
                $QR_height = imagesy($QR);  //原图像高度
                $logo_width = imagesx($logo);  //二维码图片宽度
                $logo_height = imagesy($logo);  //二维码图片高度

                $logo_qr_width = 73; //头像的宽度
                $logo_qr_height = 73; //头像高度
                $from_width = 40; //距离左边距的宽度
                $from_height = 360; //距离左边距的宽度

                //重新组合图片并调整大小
                imagecopyresampled($QR, $logo, $from_width, $from_height, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);
                $lasefilename = 'qrcode/'.$uid.rand(100,999).'.png';//原图
                imagepng($QR, $lasefilename);
            }

            // 第四步  写入文字
            $userInfo = $user_model->field('headimg,name,regetime')->where('id',$uid)->find();
            $file = $lasefilename;
            $info = getimagesize($file);  //获取图片信息
            //获取图片扩展名
            $type = image_type_to_extension($info[2],false);
            //动态的把图片导入内存中
            $fun = "imagecreatefrom{$type}";
            $image = $fun($file);
            $col = imagecolorallocatealpha($image,255,255,255,0);
            $bluecolor = imagecolorallocate($image,255,255,255); // 文字颜色
            // $image, $size, $angle, $x, $y, $color, $fontfile, $text
            $height  = 370;
            foreach ($textArray as $val) {
                $height+= 20;
                imagettftext($image,$fontsize,0,120,$height,$bluecolor,$fontfile,$val);
            }

            //第一个是画布资源 2 文字大小 3 旋转角度  4 x坐标  5  纵坐标
            //imagettftext($image,$fontsize,0,120,410,$color,$fontfile,'请与我一起精进');

            imagettftext($image,$fontsize1,0,120,170,$color,$fontfile,$userInfo['name']);
            // 学习天数
            $days = sprintf("%.1f",(time() - $userInfo['regetime']) / (3600 * 24));
            $logsList = $courseLearn->field('section_id')->where(['uid'=>$uid,'status'=>1])->select();
            $learnTime = 0;
            foreach ($logsList as $val) {
                $audiotime = $section_model->where('id',$val['section_id'])->value('audiotime');
                $learnTime += (int)$audiotime;
            }
            $learnTime = sprintf("%.1f",$learnTime / 60);
            // 赠送贡献值
            $value = $colliers->where('type',6)->value('contribution');
            imagettftext($image,$fontsize1,0,40,240,$color,$fontfile,'我已经来到每日才学'.$days.'天');
            imagettftext($image,$fontsize1,0,40,265,$color,$fontfile,'学习了'.$learnTime.'小时');
            imagettftext($image,$fontsize1,0,40,290,$color,$fontfile,'送你贡献值'.$value);
            imagettftext($image,$fontsize1,0,40,315,$color,$fontfile,'邀请你与我一起学财商');

            $perferimg = 'qrcode/'.$uid.rand(100,999).'.png';
            imagepng($image, $perferimg);
            unlink($file);

            // 第五步 将头像转换成圆形

            $headimg = $uid.'_headimg.png';
            $data = file_get_contents($userInfo['headimg']);
            $headimgUrl = "./headimg/".$headimg;
            file_put_contents($headimgUrl,$data);

            $yheadlogo = $headimgUrl;
            // 将头像处理成圆形
            list($imgg,$w) = yuan_img($headimgUrl);
            $cirHeadimg = 'headimg/'.$uid.rand(100,999).'.png';
            imagepng($imgg, $cirHeadimg);
            unlink($yheadlogo);  //删除原微信头像

            // 第六步  将头像和模板合并
            $QR2 = $perferimg; // 模板
            $qrlogo = $cirHeadimg;  //微信头像
            if ($qrlogo !== FALSE) {
                $QR2 = imagecreatefromstring(file_get_contents($QR2));//读取原图像
                $qrlogo = imagecreatefromstring(file_get_contents($qrlogo));//读取二维码图像
                $QR2_width = imagesx($QR2);//原图像宽度
                $QR2_height = imagesy($QR2);//原图像高度
                $logo2_width = imagesx($qrlogo);//二维码图片宽度
                $logo2_height = imagesy($qrlogo);//二维码图片高度
                $logo2_qr_width = 50; // 头像的宽度
                $logo2_qr_height = 50; //头像的高度
                $from2_width = 40; //距离左边距的宽度
                $from2_height = 140; //距离左边距的宽度
                //重新组合图片并调整大小
                imagecopyresampled($QR2, $qrlogo, $from2_width, $from2_height, 0, 0, $logo2_qr_width,$logo2_qr_height, $logo2_width, $logo2_height);
            }
            //输出图片
            $finalimg = 'headimg/'.$poster_id."_".$uid.time().'.png';//原图
            $filename = $poster_id."_".$uid.time().'.png';
            imagepng($QR2, $finalimg);
            unlink($perferimg);
            unlink($cirHeadimg);
            $url = uploadLocalToOss($finalimg, "user_poster",$filename);
            unlink($finalimg);
            return $url;
        }else{
            // 第三部 将模板与二维码合并
            $QR = $posterImg;  //原图像
            $logo = $newQrcodeUrl; //要处理的图形
            if ($logo !== FALSE) {
                $QR = imagecreatefromstring(file_get_contents($QR));         //读取原图像
                $logo = imagecreatefromstring(file_get_contents($logo));    //读取二维码图像
                $QR_width = imagesx($QR);   //原图像宽度
                $QR_height = imagesy($QR);  //原图像高度
                $logo_width = imagesx($logo);  //二维码图片宽度
                $logo_height = imagesy($logo);  //二维码图片高度

                ///$logo_qr_width = 60; //头像的宽度
                //$logo_qr_height = 60; //头像高度
               // $from_width = 25; //距离左边距的宽度
               // $from_height = 410; //距离左边距的高度
				$logo_qr_width = 180; //头像的宽度
                $logo_qr_height = 180; //头像高度
                $from_width = 60; //距离左边距的宽度
                $from_height = 1235; //距离左边距的高度
                //重新组合图片并调整大小
                imagecopyresampled($QR, $logo, $from_width, $from_height, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);
                $lasefilename = 'qrcode/'.$uid.rand(100,999).'.png';//原图
                imagepng($QR, $lasefilename);
            }

            // 第四步  写入文字
            $userInfo = $user_model->field('headimg,name')->where('id',$uid)->find();
            $file = $lasefilename;
            $info = getimagesize($file);  //获取图片信息
            //获取图片扩展名
            $type = image_type_to_extension($info[2],false);
            //动态的把图片导入内存中
            $fun = "imagecreatefrom{$type}";
            $image = $fun($file);
            $color = imagecolorallocatealpha($image,0,0,0,0);
            $bluecolor = imagecolorallocate($image,0,0,0); // 文字颜色
            // $image, $size, $angle, $x, $y, $color, $fontfile, $text
            //$height = 420;
          $height = 1260;
            foreach ($textArray as $val) {
                //$height+= 20;
              $height+= 60;
                imagettftext($image,$fontsize,0,300,$height,$bluecolor,$fontfile,$val);
            }
            //第一个是画布资源 2 文字大小 3 旋转角度  4 x坐标  5  纵坐标
            //imagettftext($image,$fontsize,0,110,460,$color,$fontfile,'你就不知道你多么的富有');
            $filename = $poster_id."_".$uid.time().'.png';
            $perferimg = 'headimg/'.$filename;
            imagepng($image, $perferimg);
            unlink($file);
            unlink($newQrcodeUrl); //删除二维码原图片
            // 上传到oss
            $url = uploadLocalToOss($perferimg,"user_poster", $filename);
            unlink($perferimg);
            return $url;
        }
    }


    /*
     * 链上学籍
     * @return \type
     */
    public function courseList(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $page = empty($post['page'])?1:$post['page'];
        $status = empty(input('status')) ? 1 : input('status');
        $order_model = new Orders();

        $start = ($page - 1) * 10;
        $limit = $start . ',' . 10;
        $orderInfo = $order_model->where(['uid'=>$this->uid])->where('status',$status)->field('course_id,paytime,status')->limit($limit)->order('status desc,paytime desc')->select();
        $list = array();
        if(!empty($orderInfo)){
            $course_model = new Course();
            foreach($orderInfo as $okey=>$oval){
                $cour = $course_model->where(['id'=>$oval['course_id']])->field('id,name,chapter_count,abstract,imgurl')->find();
                if(empty($cour['imgurl'])){
                    $cour['imgurl'] = '';
                }
                $list[$okey] = $cour;
                $list[$okey]['status'] = $oval['status'] == 1 ? '学习中' : '已过期';
                $list[$okey]['time'] = date('Y.m.d',$oval['paytime']);
            }
        }
        return returnjson(1000,$list,'获取成功');
    }
    /*
     * 财学堂已购课程
     * @return \type
     */
    public function cxCourseList(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $page = empty($post['page'])?1:$post['page'];
        $type = empty(input('type')) ? 1 : input('type');
        $order_model = new Orders();

        $start = ($page - 1) * 10;
        $limit = $start . ',' . 10;
        if($post['is_hidden_finish']==1){
            $orderInfo = $order_model->where(['uid'=>$this->uid])->where(['status'=>['in',[4]]])->where('course_type',1)->field('course_id,paytime,status')->limit($limit)->order('status desc,paytime desc')->select();
        }else{
            $orderInfo = $order_model->where(['uid'=>$this->uid])->where(['status'=>['in',[4,5,6]]])->where('course_type',1)->field('course_id,paytime,status')->limit($limit)->order('status desc,paytime desc')->select();
        }

        $list = array();
        if(!empty($orderInfo)){
            $course_model = new Course();
            foreach($orderInfo as $okey=>$oval){
                $cour = $course_model->where(['id'=>$oval['course_id']])->field('id,name,chapter_count,abstract,imgurl,teacher_id')->find();
                if(empty($cour['imgurl'])){
                    $cour['imgurl'] = '';
                }

                $list[$okey] = $cour;
                $list[$okey]['teacher_name'] = Db::name('teacher')->where('id',$cour['teacher_id'])->value('name');
                //
                $coursecount = Db::name('section')->where('c_id',$oval['course_id'])->where('is_delete',0)->count();
                $studuscount = Db::name('studus_section')->where('uid',$this->uid)->where('cid',$oval['course_id'])->count();

                if($studuscount==0){
                    $list[$okey]['status'] = '未学习';
                }
                if($coursecount==$studuscount){
                    $list[$okey]['status'] = '已学完';
                }
                if($studuscount==0||$coursecount==0){
                    $ratio = 0;
                }else{
                    $ratio = $studuscount/$coursecount;
                    $ratio = round($ratio,2)*100;
                }

                $list[$okey]['ratio'] = $ratio;
                $list[$okey]['time'] = date('Y.m.d',$oval['paytime']);
            }
        }
        return returnjson(1000,$list,'获取成功');
    }
    /*
     * 排行榜信息
     */
    public function rankSet() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $type = input('type');
        $type = empty($type) ? 'dedication' : $type;
        $user = new \app\wxapp\model\User();
        $startLevel = new StartLevel();
        $userInfo = $user->where('id',$this->uid)->field('headimg')->find();
        //$systemInfo = $this->systeminfo;
        $rankimg = $userInfo['headimg'];
        $user_model = new \app\wxapp\model\User();
        $userData = $user_model->getRankSetInfo($this->uid,$type);
     	 $data = $user_model->getRankList($type,1,$this->uid);
        $userData['rankimg'] = $data[0]['headimg'];
        return returnjson(1000,$userData,'获取成功');
    }

    /*
     * 今日排行榜
     * @return \type
     */
    public function rankList() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $type = input('type');
        $type = empty($type) ? 'dedication' : $type;
        $page = input('page') ? input('page') : 1;
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        $user_model = new \app\wxapp\model\User();
        $data = $user_model->getRankList($type,$limit,$this->uid);
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 排行榜点赞/取消点赞
     */
    public function userLike() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $likeUid = input('uid');
        $status = input('status');
        $userLike = new UserLike();
        $user_model = new \app\wxapp\model\User();
        Db::startTrans();
        if($status == 0) {  // 取消点赞
            if(!$userLike->where(['uid'=>$this->uid,'like_id'=>$likeUid])->delete()) {
                return returnjson(1001,'','取消错误');
            }
            if(false === $user_model->where('id',$likeUid)->setDec('like_num')) {
                Db::rollback();
                return returnjson(1001,'','取消错误');
            }
            Db::commit();
            return returnjson(1000,'','已取消');
        }
        if($userLike->where(['uid'=>$this->uid,'like_id'=>$likeUid])->find()) {
            return returnjson(1001,'','请勿重复点赞');
        }
        $data = ['uid'=>$this->uid,'like_id'=>$likeUid,'addtime'=>time()];
        if(!$userLike->insert($data)) {
            return returnjson(1001,'','错误');
        }
        if(false === $user_model->where('id',$likeUid)->setInc('like_num',1)) {
            Db::rollback();
            return returnjson(1001,'','点赞错误');
        }
        Db::commit();
        return returnjson(1000,'','已点赞');
    }

    //邀请好友消息
    public function friendMessage(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $page = input('page') ? $page = input('page') : 1;
        $message_model = new Message();
        $user_model = new \app\wxapp\model\User();
        $tutorFollow = new TutorFollow();
        $start = ($page - 1) * $this->num;
        $list = $message_model ->where(['uid'=>$this->uid,'type'=>2])->order('addtime desc')->limit($start,$this->num)->select();
        $messageReadLog = new MessageReadLog();
        $listdata = array();
        if(!empty($list)){
            $listdata = array();
            foreach($list as $key=>$value){
                $userinfo = $user_model->where(['id'=>$value['invate_uid']])->field('name,headimg')->find();
                $listdata[$key]['uid'] = $value['invate_uid'];
                $listdata[$key]['name'] = $userinfo['name'] ? $userinfo['name']:'';
                $listdata[$key]['headimg'] = $userinfo['headimg']?$userinfo['headimg']:'';
                $listdata[$key]['content'] = '你的好友'.$userinfo['name'].'通过邀请成功注册了每日财学APP。';
                $listdata[$key]['content1'] = '快来关注TA,和TA一起学习每日财学财商';
                $listdata[$key]['is_follow'] = 0;
                $follow = $tutorFollow->where(['uid'=>$this->uid,'tutor_id'=>$value['uid']])->find();
                if($follow){
                    $listdata[$key]['is_follow'] = 1;
                }
                $listdata[$key]['addtime'] = date('m月d号',$value['addtime']);
                if(!$messageReadLog->where(['type'=>$value['type'],'uid'=>$this->uid,'msg_id'=>$value['id']])->find()) {
                    $data = ['type'=>$value['type'],'uid'=>$this->uid,'msg_id'=>$value['id'],'addtime'=>time()];
                    $messageReadLog->insert($data);
                }
            }
        }
        return returnjson(1000,$listdata,'获取成功');
    }

    /*
   * 上传用户头像
   */
    public function editInfo() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $imgType = input('type');
        $file = request()->file('img');
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        file_put_contents("editimg.txt",";接收的type:".$imgType,FILE_APPEND);
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            $filename = $info->getSaveName();
            $file = 'uploads/'. $info->getSaveName();
            $url = uploadLocalToOss($file,"images", $filename);
            unlink($file);
            $preg = "/^http(s)?:\\/\\/.+/";
            if(preg_match($preg,$url)) {
                // 保存到数据库
                $user_mode = new \app\wxapp\model\User();
                if (strcmp(trim($imgType),"headimg" ) == 0) {
                    $data = ['headimg'=>$url];
                }else if(strcmp(trim($imgType),"wximg" )== 0) {
                    $data = ['wximg'=>$url];
                }else{
                    return returnjson(1001,'','缺少参数');
                }
                if(false !== $user_mode->where('id',$this->uid)->update($data)) {
                    return returnjson(1000,$url,'修改成功');
                }
                return returnjson(1001,'','修改失败');
            }else {
                return returnjson(1001,'','上传失败');
            }
        }
        return returnjson(1001,'','上传失败');
    }

    /*
     * 我的足迹
     */
    public function myFootprint() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        list($start, $end) = Time::today();
        $courseLearn = new CourseLearnLog();
        $section_model = new Sectiones();
        $user_model = new \app\wxapp\model\User();
        $logsList = $courseLearn->field('section_id,addtime')->where(['uid'=>$this->uid,'addtime'=>['between',[$start,$end]]])->select();
        if($logsList) {
            $learnTime = 0;
            foreach ($logsList as $val) {
                $audiotime = $section_model->where('id',$val['section_id'])->value('audiotime');
                $learnTime += (int)$audiotime;
            }
            // 连续天数
            $start += 86399;
            $end += 86399;
            $days = $this->continuDays($this->uid,$start,$end);
        }else{
            $days = 0;
            $learnTime = 0;
        }
        $logsList = $courseLearn->field('section_id')->where(['uid'=>$this->uid,'status'=>1])->select();
        $allLearnTime = 0;
        foreach ($logsList as $val) {
            $audiotime = $section_model->where('id',$val['section_id'])->value('audiotime');
            $allLearnTime += (int)$audiotime;
        }
        $allLearnHour = sprintf("%.1f",$allLearnTime / 60);
        $dedicationLog = new DedicationLog();
        $dedication = $dedicationLog->where(['uid'=>$this->uid,'addtime'=>['between',[$start,$end]]])->sum('value');
        $allDedication = $user_model->where('id',$this->uid)->value('dedication_value');
        $shareData = ['title'=>'我的足迹','link'=>'http://baidu.com','imges'=>'https://lovegirl-1256300440.cos.ap-shanghai.myqcloud.com/teacher/20200609/15916322102759.png'];
        $data = ['learnTime'=>$learnTime."分钟",'days'=>$days.'天','allLearnTime'=>$allLearnTime."小时",'dedication'=>"+".$dedication,'allDedication'=>$allDedication,'shareData'=>$shareData];
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 足迹明细
     */
    public function myFootprintLog() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $page = input('page') ? input('page') : 1;
        $startPage = ($page - 1) * $this->num;
        $dedicationLog = new DedicationLog();
        $sql = "select FROM_UNIXTIME(addtime, '%Y-%m-%d') as datetime from dedication_log  where uid = ".$this->uid." group by datetime  order by datetime desc limit ".$startPage.",".$this->num;
        $list = Db::query($sql);
        if($list) {
            foreach ($list as $k=>$val) {
                $start = strtotime($val['datetime']);
                $end = $start + 86399;
                $daylist = $dedicationLog->where(['uid'=>$this->uid,'addtime'=>['between',[$start,$end]]])->order('addtime desc')->select();
                foreach ($daylist as $key=>$value) {
                  	if($value['type'] == 17){
                    	continue;
                    }
                    $res = $this->getTypeTxt($value['type'],$value['obj_id'],date('H:m',$value['addtime']));
                    $daylist[$key]['title'] = $res['title'];
                    $daylist[$key]['content'] = $res['content'];
                }
                $list[$k]['daylist'] = $daylist;
            }
        }
        return returnjson(1000,$list,'获取成功');
    }

    /*
     * 返回内容
     * 1每日才学 2 阅读文章 3点赞文章 4 分享 5 反馈意见 6邀请
     * 7 才学堂课堂刚学习  9 大社群新增一人 10 小社群新增一人
     * 11大社群新增一个学习力 12 小社群新增一个学习力
     *  14、课程阅读 15、课程点赞 16、课程分享
     */
    public function getTypeTxt($type = 0,$obj_id = 0,$date) {
        switch ($type) {
            case 1:
                $dayArticle = new Dayarticle();
                $info = $dayArticle->field('title')->where('id',$obj_id)->where('is_shelves',1)->find();
                $title = $date."每日财学";
                $content = "每日财学 | ".$info['title'];
                break;
            case 2:
                $title = $date."阅读了文章";
                $knowledgeArticle = new \app\index\model\Knowledge();
                $info = $knowledgeArticle->field('title')->where('id',$obj_id)->find();
                $content = "文章 | ".$info['title'];
                break;
            case 3:
                $title = $date."点赞了文章";
                $knowledgeArticle = new \app\index\model\Knowledge();
                $info = $knowledgeArticle->field('title')->where('id',$obj_id)->find();
                $content = "文章 | ".$info['title'];
                break;
            case 4:
                $title = $date."分享了文章";
                $knowledgeArticle = new \app\index\model\Knowledge();
                $info = $knowledgeArticle->field('title')->where('id',$obj_id)->find();
                $content = "文章 | ".$info['title'];
                break;
            case 5:
                $title = $date.'反馈意见';
                $content = "提交反馈意见";
                break;
            case 6:
                $title = $date."邀请了朋友";
                $content = "推荐朋友 | 一起进入每日财学学习财商知识";
                break;
            case 7:
                $order_model = new Orders();
                $course_model = new Course();
                $advanced_model = new Advanced();
                $orderInfo = $order_model->field('course_id,advanced_id')->where('id',$obj_id)->find();
                $info = $course_model->field('name')->where('id',$orderInfo['course_id'])->find();
                $advancedInfo = $advanced_model->field('name')->where('id',$orderInfo['advanced_id'])->find();
                $title = $date."购买了课程";
                $content = $advancedInfo['name'] ." | ".$info['name'];
                break;
             case 8:
                $title = $date."收藏了文章";
                $knowledgeArticle = new \app\index\model\Knowledge();
                $info = $knowledgeArticle->field('title')->where('id',$obj_id)->find();
                $content = "文章 | ".$info['title'];
                break;
            case 9:
                $title = $date."大社群新增一人";
                $content = "大社群新增一人";
                break;
            case 10:
                $title = $date."小社群新增一人";
                $content = "小社群新增一人";
                break;
            case 11:
                $title = $date."大社群新增一个学习力";
                $content = "大社群新增一个学习力";
                break;
            case 12:
                $title = $date."小社群新增一个学习力";
                $content = "小社群新增一个学习力";
                break;
            case 13: // 课程
                $course_model = new Course();
                $advanced_model = new Advanced();
                $info = $course_model->field('name,advanced_id')->where('id',$obj_id)->find();
                $advancedInfo = $advanced_model->field('name')->where('id',$info['advanced_id'])->find();
                $title = $date."收藏了课程";
                $content = $advancedInfo['name'] ." | ".$info['name'];
                break;
            case 14: // 课程
                $course_model = new Course();
                $advanced_model = new Advanced();
                $info = $course_model->field('name,advanced_id')->where('id',$obj_id)->find();
                $advancedInfo = $advanced_model->field('name')->where('id',$info['advanced_id'])->find();
                $title = $date."阅读了课程";
                $content = $advancedInfo['name'] ." | ".$info['name'];
                break;
            case 15:  //课程点赞
                $course_model = new Course();
                $advanced_model = new Advanced();
                $info = $course_model->field('name,advanced_id')->where('id',$obj_id)->find();
                $advancedInfo = $advanced_model->field('name')->where('id',$info['advanced_id'])->find();
                $title = $date."点赞了课程";
                $content = $advancedInfo['name'] ." | ".$info['name'];
                break;

            case 16:  //课程分享
                $course_model = new Course();
                $advanced_model = new Advanced();
                $info = $course_model->field('name,advanced_id')->where('id',$obj_id)->find();
                $advancedInfo = $advanced_model->field('name')->where('id',$info['advanced_id'])->find();
                $title = $date."分享了课程";
                $content = $advancedInfo['name'] ." | ".$info['name'];
                break;
            default:
                $title = '';
                $content = '';
                break;
        }
        return ['title'=>$title,'content'=>$content];
    }

    /*
     * 连续天数
     * @param int $uid 用户id
     * @param string $start 开始时间
     * @param string $end 结束时间
     */
    public function continuDays($uid = 0,$start = '',$end = '') {
        $courseLearn = new CourseLearnLog();
        $days = 1;
        if($courseLearn->field('section_id,addtime')->where(['uid'=>$uid,'addtime'=>['between',[$start,$end]]])->select()) {
            $days ++;
            $start += 86400;
            $end += 86400;
            return $this->continuDays($uid,$start,$end);
        }
        return $days;
    }
    //测试接口
    public function test(){
        $token = input('token');
        if(!empty($token)) {
            //$this->getUserInfo($token);
        }
        if($this->uid == 0) {
           // return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $common_model = new common();
        $res = $common_model ->minShequnD($this->uid,1,1);

        //return returnjson(1000,$res,'成功');
    }
    //获取贡献值二级
    public function get_dedication_two(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user = new \app\wxapp\model\User();
        $startLevel = new StartLevel();
        $userInfo = $user->where('id',$this->uid)->field('id,dedication_value')->find();
        $userInfo['time'] = date('Y.m.d',time());
        if($userInfo){   //is_auth 0未认证 1已提交审核 2已认证 3认证驳回
            $userInfo['task'] = Db::name('dedication_log')->where('uid',$this->uid)->where(['type'=>['in','1,2,3,4,5,6,7,8,13,14,15,16,20,21']])->sum('value');
            $userInfo['team'] = Db::name('dedication_log')->where('uid',$this->uid)->where('type',17)->sum('value');
            return returnjson(1000,$userInfo,'获取成功');
        }
        return returnjson(1001,'','获取失败');
    }
    //我的学分，学分二级
    public function get_score_two(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user = new \app\wxapp\model\User();
        $startLevel = new StartLevel();
        $userInfo = $user->where('id',$this->uid)->field('id,score')->find();

        if($userInfo){
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;

            $where['addtime'] = ['between',$beginToday.','.$endToday];
            $userInfo['income'] = Db::name('creditSource')->where('uid',$this->uid)->where('score','>',0)->sum('score');
            $userInfo['expend'] = Db::name('creditSource')->where('uid',$this->uid)->where('score','<',0)->sum('score');
            $userInfo['today'] = Db::name('creditSource')->where('uid',$this->uid)->where($where)->sum('score');
            $userInfo['study'] = Db::name('creditSource')->where('uid',$this->uid)->where('type',1)->sum('score');
            $userInfo['expert'] = 0;
            $userInfo['peters'] = Db::name('creditSource')->where('uid',$this->uid)->where(['type'=>['in','12,13']])->sum('score');;
            return returnjson(1000,$userInfo,'获取成功');
        }
        return returnjson(1001,'','获取失败');
    }
    //获取学分接口
    public function getSource(){
        $res = $this->detype();
        if($res===false){
            return returnjson(1001,'','数据错误');
        }
        $tltid = $res['id'];
        $tel = $res['tel'];
       $identityid = $res['identityid'];

        $userinfo = Db::name('user')->where('identityid',$identityid)->where('tel',$tel)->where('is_auth',1)->field('score,level,start_level,is_open_tlt')->find();
        if(empty($userinfo)){
            return returnjson(1001,'','无此用户');
        }else{
            if($userinfo['level']==0){
                $mylevel['service_charge'] = 100;
                $mylevel['name'] = 'LV0';
            }else{
                $level = new level();
                $mylevel = $level->where('value',$userinfo['level'])->field('name,service_charge')->find();
                //Db::name('userLevel')->where()->find();
            }
            $data['score'] = $userinfo['score'];
            $data['service_charge'] = $mylevel['service_charge'];
            $data['levelname'] = $mylevel['name'];
            $data['is_open_tlt'] =  $userinfo['is_open_tlt'];
            return returnjson(1000,$data,'获取成功');

        }
    }
    //开启置换
    public function open_tlt(){
        $res = $this->detype();
        if($res===false){
            return returnjson(1001,'','数据错误');
        }
        $tltid = $res['id'];
        $tel = $res['tel'];
       $identityid = $res['identityid'];
       $realname = $res['realname'];
        $userinfo = Db::name('user')->where('identityid',$identityid)->where('realname',$realname)->where('tel',$tel)->where('is_auth',1)->field('id,score,level,start_level,honor_value,is_open_tlt')->find();
        if(empty($userinfo)){
            return returnjson(1001,'','无此用户');
        }
        if($userinfo['is_open_tlt']==1){
            return returnjson(1000,'','开通成功');
        }
        $common = new Common();
        $opentlt = $userinfo['score'] - 1;
        Db::startTrans();
        $res = Db::name('user')->where('id',$userinfo['id'])->update(['score'=>$opentlt,'is_open_tlt'=>1]);
        if($res){
            $data =[
                'type'=>11,'uid'=>$userinfo['id'],'pay_type'=>0,'score'=>-1,
                'status'=>1,'note'=>"开通置换手续费",'value'=>0,'addtime'=>time()
                ];
            $creditres = $common->creditSource($data,$userinfo['id']);
            if(false === $creditres){
                Db::rollback();
                return returnjson(1001,'','兑换失败');
            }
        }else{
            Db::rollback();
            return returnjson(1001,'','开通失败');
        }
         Db::commit();
        return returnjson(1000,'','开通成功');
    }
    //兑换学分
    public function conversion(){
        $res = $this->detype();
        if($res===false){
            return returnjson(1001,'','数据错误');
        }
        $realname = $res['realname'];
        $identityid = $res['identityid'];
        $tel = $res['tel'];
        $type = $res['type'];
        $tltid = $res['id'];
        $score = $res['score'];

        $torderid = 0;
        $userinfo = Db::name('user')->where('identityid',$identityid)->where('realname',$realname)->where('tel',$tel)->where('is_auth',1)->field('id,score,level,start_level,honor_value,is_open_tlt')->find();

        if(empty($userinfo)){
            return returnjson(1001,'','无此用户');
        }
        if($userinfo['level'] == 0){
            return returnjson(1001,'','兑换失败');
        }
        $level = new level();
        $mylevel = $level->where('value',$userinfo['level'])->field('name,service_charge')->find();
        $common = new Common();
        if($type==1){
            if($userinfo['level'] <2){
                return returnjson(1004,'','等级不够，无法兑换');
            }
            $realratio = 10 - $mylevel['service_charge']/10;
            //手续费

            $service_charge = $score/$realratio * $mylevel['service_charge']/10;
            $totalscore = $score + $service_charge;
            //$totalscore = $score+$score*$mylevel['service_charge']/100;
           if($userinfo['honor_value']<$totalscore){
               return returnjson(1002,'','荣誉值额度不足');
           }
           if($userinfo['is_open_tlt']==0){
               if($totalscore+1>$userinfo['score']){
                    return returnjson(1003,'','学分不足');
                }
           }else{
               if($totalscore>$userinfo['score']){
                    return returnjson(1003,'','学分不足');
                }
           }


            Db::startTrans();
            if($userinfo['is_open_tlt']==0){
                $opentlt = $userinfo['score'] - ($totalscore+1);
               $res = Db::name('user')->where('id',$userinfo['id'])->update(['score'=>$opentlt,'is_open_tlt'=>1]);
           }else{
               $res = Db::name('user')->where('id',$userinfo['id'])->setDec('score',$totalscore);
           }


            if($res){

                if($userinfo['is_open_tlt']==0){
                    $data =[
                        'type'=>11,'uid'=>$userinfo['id'],'pay_type'=>0,'score'=>-1,
                        'status'=>1,'note'=>"开通置换手续费",'value'=>$torderid,'addtime'=>time()
                    ];
                    $creditres = $common->creditSource($data,$userinfo['id']);
                    if(false === $creditres){
                        Db::rollback();
                        return returnjson(1001,'','兑换失败');
                    }
                  // $openres = Db::name('user')->where('id',$userinfo['id'])->update(['is_open_tlt',1]);
                   // if(empty($res)){
                    //     Db::rollback();
                     //   return returnjson(1001,'','兑换失败');
                    //}
               }
                 $data1 =[
                    'type'=>4,'uid'=>$userinfo['id'],'pay_type'=>0,'score'=>"-".$totalscore,
                    'status'=>1,'note'=>"兑换TLT",'value'=>$torderid,'addtime'=>time(),'service_charge' => $service_charge,'ratio'=>$mylevel['service_charge']
                ];
                $creditres = $common->creditSource($data1,$userinfo['id']);
                if(false === $creditres){
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                if(false === $common->honorLog($userinfo['id'],10,$torderid,-$totalscore)) {
                    Db::rollback();
                    return returnjson(1001,'','购买失败');
                }
                if(!empty($service_charge)){
                    $mystartlevel = Db::name('start_level')->where('value',$userinfo['start_level'])->find();
                    //var_dump($userinfo['start_level']);exit;
                    if($mystartlevel['bonus']>0){
                        $bonus_source = $service_charge * $mystartlevel['bonus'];
                        $uids = Db::name('user')->where(['is_auth'=>1,'start_level'=>$userinfo['start_level']])->field('id')->select();

                        $uidscount = count($uids);
                        $uidss = '';
                        if($uidscount>0){
                            foreach($uids as $uv){
                                $uidss .=','.$uv['id'];
                            }
                        }
                        $uidss= trim($uidss,',');
                        //var_dump($uidss);exit;
                        $csbdata = [
                                'uids' =>$uidss,
                                'start_level' => $userinfo['start_level'],
                                'source' => $service_charge,

                                'num' => $uidscount,
                                'addtime' => time(),
                                'csid' => $creditres,
                            ];
                        $csres = Db::name('creditSourceBonus')->insert($csbdata);
                        if($csres){

                        }else{
                            Db::rollback();
                            return returnjson(1001,'','兑换失败');
                        }
                    }
                }
                Db::commit();
                return returnjson(1000,'','兑换成功');
            }else{
                Db::rollback();
                return returnjson(1001,'','兑换失败');
            }
        }elseif($type==2){
            Db::startTrans();
            $res = Db::name('user')->where('id',$userinfo['id'])->setInc('score',$score);
            if($res){
                $data =[
                    'type'=>3,'uid'=>$userinfo['id'],'pay_type'=>0,'score'=>"+".$score,
                    'status'=>1,'note'=>"TLT兑换学分",'value'=>$torderid,'addtime'=>time()
                ];
                if(false === $common->creditSource($data,$userinfo['id'])){
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                if(false === $common->honorLog($userinfo['id'],2,$torderid,$score)) {
                    Db::rollback();
                    return returnjson(1001,'','购买失败');
                }
                Db::commit();
                return returnjson(1000,'','兑换成功');
            }else{
                 Db::rollback();
                return returnjson(1001,'','兑换失败');
            }
        }
    }

    //校验手机有没有
    public function check_tel(){
        $res = $this->detype();
        if($res===false){
            return returnjson(1001,'','数据错误');
        }
        $tel = $res['tel'];

        $user = Db::name('user')->where(['tel'=>$tel])->find();
        if($user['is_auth'] == 0){
            return returnjson(1001,'','无此用户');
        }
        if(empty($user['id'])){
            return returnjson(1001,'','无此手机');
        }else{
            $data['headimg'] =  $user['headimg'];
            $data['name'] =  $user['name'];
            $data['tel'] =  $user['tel'];
            $data['level'] =  $user['level'];
            $data['is_open_tlt'] =  $user['is_open_tlt'];
            $levelname = Db::name('userLevel')->where('value',$user['level'])->value('name');
            $data['levelname'] =  $levelname;
            return returnjson(1000,$data,'有');
        }
    }
    //校验身份证有没有
    public function check_identityid(){
        $res = $this->detype();
        if($res===false){
            return returnjson(1001,'','数据错误');
        }
        $realname = $res['realname'];
        $identityid = $res['identityid'];
        $tel = $res['tel'];
        $user = Db::name('user')->where(['identityid'=>$identityid,'realname'=>$realname,'tel'=>$tel,'is_auth'=>1])->find();
        if($user['is_auth'] == 0){
            return returnjson(1001,'','无此用户');
        }
        if(empty($user['id'])){
            return returnjson(1001,'','无此用户');
        }else{
            $data['headimg'] =  $user['headimg'];
            $data['name'] =  $user['name'];
            $data['tel'] =  $user['tel'];
            $data['level'] =  $user['level'];
            $data['is_open_tlt'] =  $user['is_open_tlt'];
            $levelname = Db::name('userLevel')->where('value',$user['level'])->value('name');
            $data['levelname'] =  $levelname;
            return returnjson(1000,$data,'有');
        }
    }

    /**
 * 公钥解密
 */
    private function detype(){
        $addr = $_SERVER['REMOTE_ADDR'];
        $system = Db::name('system')->where('id',1)->field('tlt_white,tlt_public')->find();
        $tlt_white = $system['tlt_white'];
        $whites = explode(',',$tlt_white);
        if(!in_array($addr,$whites)){

            return false;
        }
        $encrypted = input('post.data');

        if(empty($encrypted)){
            return false;
        }

        //原始数据解码
        $decrypted = "sha1";

        //获取公钥
        $pu_key = file_get_contents('test.cer');

        //私钥加密的内容通过公钥可用解密出来
        openssl_public_decrypt(base64_decode($encrypted),$decrypted,$pu_key);

        $api = $system['tlt_public'];
        $domain  =  strstr ($decrypted ,  '&sign');
        if(empty($domain)){
            return false;
        }
        $domain_  =  strstr ($decrypted ,  '&sign',true);
        $sign = substr($domain,6);

        $md5srt = md5($domain_.$api);
        if($md5srt!=$sign){
            return false;
        }
        $array = explode('&',$domain_);
        if(empty($array)){
            return false;
        }
        $return = array();
        foreach($array as $v){
            $two = explode('=',$v);
            $return[$two[0]] = $two[1];
        }
        //var_dump($return);exit;
        return $return;
    }






    /**
     * 生成公钥和私钥并保存
     */
    public function generate(){
        $config = array(
            "digest_alg" => "sha512",//加密方式
            "private_key_bits" => 2048, //加密长度
            "private_key_type" => OPENSSL_KEYTYPE_RSA,//加密类型
        );
        $res = openssl_pkey_new($config);//创建公钥和私钥

        //获取私钥
        openssl_pkey_export($res, $privateKey);
        //保存密钥到文件
        file_put_contents('./Uploads/test.pfx',$privateKey);

        //获取公钥
        $pubblic = openssl_pkey_get_details($res);
        $pubblicKey = $pubblic['key'];
        file_put_contents('./Uploads/test.cer',$pubblicKey);
    }

}

