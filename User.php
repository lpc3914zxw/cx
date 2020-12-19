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
use app\service\SmsService;
class User extends Base{

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
        $userInfo = $user->where('id',$this->uid)->field('id,name,headimg,student_no,level,signature,score,wechat,tel,is_auth,is_overseas,email')->find();
        if($userInfo){   //is_auth 0未认证  1已认证
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
        $response = new SmsService();
        //echo $post['type'].$tel;exit;
        $res=$response->sendSms($tel,$code,3);

        $res = object_to_array($res);
        //var_dump($res);exit;
        if ($res['SendStatusSet'][0]['Code'] == 'Ok') {
            cache($tel,$code,1800);
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
             return returnjson(1001,'','验证码错误');
        }
        return returnjson(1000,'','验证通过');

    }

    public function updateInfo() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        if(empty($post['type'])||empty($post['param'])){
            return returnjson(1001,'','参数缺失');
        }
        $param = $post['param'];
        $user = new \app\wxapp\model\User;
        $userInfo = $user->where('id',$this->uid)->field('id,name,headimg,student_no,level,signature,score,wechat,tel,salt')->find();
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
            $param = splice_password($param,$userInfo['salt']);
        }elseif($post['type'] == 7){  // 微信号
            $key = 'wechat';
        }else if($param['type'] == 8) {  // 微信二维码
            $key = 'wximg';
        } else{
            return returnjson(1001,'','修改类型非法');
        }
        $res = $user->where('id',$this->uid)->setField($key,$param);
        //echo $user->getLastsql();
        $userInfo1 = $user->where('id',$this->uid)->field('id,name,headimg,student_no,level,signature,score,wechat,tel,is_auth')->find();
        if($userInfo1){   //is_auth 0未认证 1已提交审核 2已认证 3认证驳回
            $level = new level();
            $mylevel = $level->where('value',$userInfo1)->field('name')->find();
            if(empty($userInfo1['signature'])){
                $userInfo1['signature'] = '';
            }
            $userInfo1['levelname'] = $mylevel['name'];
        }
        if($res){
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
                return returnjson(1100,'该用户已在其他设备登陆');
            }
        }
        $user_model = new \app\wxapp\model\User;
        $userinfo = $user_model->where('id', $this->uid)->field('identityid,realname,is_auth')->find();
        if($userinfo['is_auth'] ==1){
            return returnjson(1001,'','您已实名认证！');
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
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $user_model = new \app\wxapp\model\User;
        $user_model->where('id', $this->uid)->update(['realname'=>$post['realname'],'identityid'=>$post['identityid']]);
        return returnjson(1000,'','已提交');
    }
    //活体检测 获取token
    function get_acctoken(){
        $token = Db::name('face_token')->where(['id'=>1])->field('updatetime,access_token')->find();
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
        return $data['access_token'];
        //return returnjson(1000,json_decode($res),'已提交');
        //var_dump($res);

    }
    //公安验证活体检测api
    function faceverify(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $post = Request::instance()->post();//

        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $token1 = $this->get_acctoken();
        $url = 'https://aip.baidubce.com/rest/2.0/face/v3/faceverify?access_token=' . $token1;
        $bodys = "{\"image\":\"sfasq35sadvsvqwr5q...\",\"image_type\":\"BASE64\",\"id_card_number\":\"110...\",\"name\":\"张三\",\"quality_control\":\"LOW\",\"liveness_control\":\"HIGH\"}";
        $res = $this->request_post($url, $bodys);
        return ($res);
        var_dump($res);
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
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $colliers = new Colliers();
        $user_model = new \app\wxapp\model\User();
        $colliers = $colliers->where('type',10)->find();
        if($colliers) {
            $dedication = $colliers['contribution'];
        }else{
            $dedication = 0;
        }
        $userinfo = $user_model->field('invate_num,dedication_value')->where('id',$this->uid)->find();
        $data = [
            'dedication'=>$dedication,
            'invateCount'=>$userinfo['invate_num'],
            'all_dedicat'=>$userinfo['dedication_value']
        ];
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 我的二维码
     * @param string $url
     * @return string
     */
    public function myErQrcode($url = '') {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆');
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
        $level = 'L';
        $size = 4;
        $filename = time().'myqrcode'.$this->uid .'.jpg';
        $outfile = ROOT_PATH ."public/qrcode/".$filename;
        ob_start();
        $Qr->png($url,$outfile,$level,$size,2);
        ob_end_clean();
        $qrcodeUrl = GetCurUrl().'/qrcode/'.$filename;
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $order_model = new Orders();
        $page = input('page');
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $user_model = new \app\wxapp\model\User();
        $creditSource = new CreditSource();
        $userInfo = $user_model->field('learning_power,dedication_value,honor_value,score')->where('id',$this->uid)->find();
        $creditSource = $creditSource->field('score,id')->where(['uid'=>$this->uid,'status'=>1])->order('addtime desc')->find();
        if($creditSource) {
            $userInfo['newSource'] = $creditSource;
        }else{
            $userInfo['newSource'] = [];
        }
        $userInfo['content1'] = '你对学习的坚持，我们看得到!';
        $userInfo['content2'] = '听完50条音频，拓宽你的知识领域。';
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
                 $where['type'] = $search['type'];
             }
         }
         if(isset($search['min']) && isset($search['max'])) {
             $min = floatval($search['min']);
             $max = floatval($search['max']);
             if($max < $min) {
                 return returnjson('1001','','最小值不能大于最大值');
             }
             $where['score'] = ['between',[$min,$max]];
         }
         $creditSource = new CreditSource();
         $page = $search['page'];
         $start = ($page - 1) * $this->num;
         $limit = $start.','.$this->num;
         return $creditSource->getApiScoreLogList($where,$limit);
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
            return returnjson(1100,'该用户已在其他设备登陆');
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
                $creditInfo['type'] = '学习收入';
                $section_model = new Sectiones();
                $creditInfo['courseName'] = $section_model->where('id',$creditInfo['value'])->value('name');
                break;
            case 2:
                $creditInfo['type'] = '文章赞赏';
                break;
            case 3:
                $creditInfo['type'] = '兑入';
                break;
            case 4:
                $creditInfo['type'] = '兑出';
                break;
            case 5:
                $creditInfo['type'] = '课程购买';
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
     * 我的收藏
     */
    public function myCollection() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $page = input('page');
        $type = input('type');
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        $collection_model = new Collection();
        $where = ['type'=>$type,'uid'=>$this->uid];
        return $collection_model->getList($where,$limit);

    }

    /*
     *
     */
    public function myTeam() {

    }




    //生成邀请海报
    public function createqr($pid  = 0) {

//        $imgpath = $info['images'];//商品头图
//        //return $imgpath;
//        include '../vendor/phpqrcode/phpqrcode.php';
//        $Qr = new \phpqrcode\QRcode();
//        $value = GetCurUrl()."/wxapp/login/h5_register/pid/".$pid;
//        $errorCorrectionLevel = 'L';//容错级别
//        $matrixPointSize = 4;//生成图片大小
//        $Qr->png($value, 'qrcode.png', $errorCorrectionLevel, $matrixPointSize, 2);
//        list($width, $height, $type, $attr) = getimagesize($imgpath);
//        $dheight = $height * 1.53;
//        //1、创建画布
//        $im = imagecreatetruecolor(400,612);//新建一个真彩色图像，默认背景是黑色，返回图像标识符。另外还有一个函数 imagecreate 已经不推荐使用。
//        //2、绘制所需要的图像
//        $color =  imagecolorallocate($im,255,255,255);
//        imagefill($im,0,0,$color);  //填充颜色画布
//        $fcolor = imagecolorallocatealpha($im,0,0,0,0);//字体颜色
//        $pcolor = imagecolorallocate($im,255,0,0);//现价的颜色
//        $icolor = imagecolorallocatealpha($im,0,0,0,80);//活动形式颜色
//        $ncolor = imagecolorallocatealpha($im,0,0,0,100);//备注颜色
//        $fontfile = '../vendor/topthink/think-captcha/assets/zhttfs/2.ttf';//文本字体-微软雅黑
//        $gname  = $info['name'];//商品名字
//        $text = autowrap(13,0,$fontfile, $gname,320); // 自动换行处理
//        //若文件编码为 GB2312 请将下行的注释去掉
//        //$text = iconv("GB2312", "UTF-8", $text);
//        //imagettftext($im, 12, 0, 10, 30, $white, "simsun.ttc", $text);
//        $len    = mb_strlen($gname,'UTF8');//字符长度
//        $first  = mb_substr($gname,0,25);//第一行
//        $second = mb_substr($gname,26,$len);//第二行
//        imagettftext($im,13,0,20,50, $fcolor, $fontfile, $text);
//        $ext = strrchr($imgpath,'.');
//        if($ext == '.jpg' || $ext == '.jpeg') {
//            $img = @imagecreatefromjpeg($imgpath);
//        }elseif ($ext == '.png') {
//            $img = @imagecreatefrompng($imgpath);
//        }
//        $qrcode = @imagecreatefrompng('qrcode.png');
//        if($width > 400) {
//            imagecopyresampled($im,$img,0,100,0, 0,400,300*$height/$width,$width,$height);//商品图
//        }else if($height > 300) {
//            imagecopyresampled($im,$img,(400-400*$width/$height)/2,100,0, 0,400*$width/$height,300,$width,$height);//商品图
//        }else {
//            imagecopyresampled($im,$img,(400-$width)/2,100,0,0,$width,$height,$width,$height);//商品图
//        }
//        imagecopy ($im, $qrcode, 10, 450, 0, 0, 150, 150);//二维码
//        $nprice = '现价:￥'.$info1['now_price'];
//        $oprice = '原价：￥'.$info1['original_price'];
//        imageline($im,230,494,290,494,$icolor);
//        $note  = '长按识别图中二维码';
//        imagettftext($im,25,0,160,475,$pcolor,$fontfile,$nprice);//写入现价
//        imagettftext($im,13,0,160,500,$icolor,$fontfile,$oprice);//写入原价或活动
//        imagettftext($im,17,0,160,540,$ncolor,$fontfile,$note);//写入备注
//        imageantialias($im, true);
//        //3、输出图像
//        $ename = time() .rand(0000,9999);
//        header("content-type: image/jpeg");
//        imagejpeg($im,'./'.$ename.'.jpg',100);//输出到页面。如果有第二个参数[,$filename],则表示保存图像
//        //4、销毁图像，释放内存
//        imagedestroy($im);
//        //unlink();//删除二维码原图
//        $base = base64EncodeImage('./'.$ename.'.jpg');
//        unlink('./'.$ename.'.jpg');//删除二维码原图
//        return $base;
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
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $page = empty($post['page'])?1:$post['page'];
        $order_model = new Orders();

        $start = ($page - 1) * 10;
        $limit = $start . ',' . 10;
        $orderInfo = $order_model->where(['uid'=>$this->uid])->where('status!=0')->field('course_id,paytime,status')->limit($limit)->order('status desc,paytime desc')->select();
        $list = array();
        if(!empty($orderInfo)){
             $course_model = new Course();
            foreach($orderInfo as $okey=>$oval){
                $cour = $course_model->where(['id'=>$oval['course_id']])->field('id,name,chapter_count,abstract,samll_imgurl')->find();
                if(empty($cour['samll_imgurl'])){
                    $cour['samll_imgurl'] = '';
                }
                $list[$okey] = $cour;
                $list[$okey]['status'] = $oval['status']==1?'学习中':'已过期';
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
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $type = input('type');
        $type = empty($type) ? 'dedication' : $type;
        $systemInfo = $this->systeminfo;
        $rankimg = $systemInfo['rankimg'];
        $user_model = new \app\wxapp\model\User();
        $userData = $user_model->getRankSetInfo($this->uid,$type);
        $userData['rankimg'] = $rankimg;
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
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $type = input('type');
        $type = empty($type) ? 'dedication' : $type;
        $page = input('page');
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        $user_model = new \app\wxapp\model\User();
        $data = $user_model->getRankList($type,$limit);
        return returnjson(1000,$data,'获取成功');
    }
}

