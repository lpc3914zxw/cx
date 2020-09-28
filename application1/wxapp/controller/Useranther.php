<?php
namespace app\wxapp\controller;
use app\common\Common;
use app\index\model\PosterTemp;
use app\wxapp\model\Collection;
use app\wxapp\model\Colliers;
use app\wxapp\model\CourseLearnLog;
use app\wxapp\model\CreditSource;
use app\wxapp\model\DayarticleCard;
use app\wxapp\model\DedicationLog;
use app\wxapp\model\LearnPowerLog;
use app\wxapp\model\PosterUser;
use app\wxapp\model\TeacherFollow;
use app\wxapp\model\Teachers;
use app\wxapp\model\Tutor;
use app\wxapp\model\Message;
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
use app\index\model\Advanced;

use think\Db;
class Useranther extends Base{
    /*
     * 每日金句
     * @return \type
     */
    public function dayarticle() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $day = new \app\wxapp\model\Dayarticle();
        $dayarticle = $day->where(['addtime'=>['between',[$beginToday,$endToday]]])->find();
        if(empty($dayarticle)){
            $dayarticle = $day->order('addtime desc')->find();
        }
        if($dayarticle){   //is_auth 0未认证 1已提交审核 2已认证 3认证驳回
            $dayarticle['addtime'] = date('Y/m/d',$dayarticle['addtime']);
            return returnjson(1000,$dayarticle,'获取成功');
        }
        return returnjson(1000,'','无数据');
    }
    /*
     * 金句列表
     * @return \type
     */
    public function dayarticleList() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $page = empty(input('page'))?1:input('page');
        $type = input('type');
        if(empty($page)){
            return returnjson(1001,'','参数缺失！');
        }
        $start = ($page - 1) * 5;
        $limit = $start.','.'5';
        $Day= new \app\wxapp\model\Dayarticle();
        $where = [];
        if(!empty($type)){
            $where['type'] = $type;
        }
        $dayarticle = $Day->where($where)->order('addtime desc')->limit($limit)->select();
        if($dayarticle){   //is_auth 0未认证 1已提交审核 2已认证 3认证驳回
            foreach($dayarticle as $key=>$value){
                $dayarticle[$key]['addtime'] = date('Y/m/d',$value['addtime']);
            }
        }
        return returnjson(1000,$dayarticle,'获取成功');
    }
    //我的学长
    public function myMentor(){
         $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user = new \app\wxapp\model\User();
        $pid  = $user->where('id',$this->uid)->value('pid');
        if($pid){
            $userInfo = $user->where('id',$pid)->field('id,name,headimg,student_no,signature,wechat,wximg,tel')->find();
          $userInfo['phone'] = $userInfo['tel'];
            if(!$userInfo['signature']){
                $userInfo['signature'] = '';
            }
            if(!$userInfo['wximg']){
                $userInfo['wximg'] = '';
            }
        }else{
            $userInfo = "";
            
        }
        
        
        return returnjson(1000,$userInfo,'获取成功');
    }
    
    //我的班级-同学详情
    public function schoolmateDetail(){
         $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        //用户id
        $id  = input('id');
        $user = new \app\wxapp\model\User();
        
        if($id){
            $userInfo = $user->where('id',$id)->field('id,name,level,wechat,tel,is_auth,start_level,dedication_value,learning_power,honor_value,regetime,class_dedication')->find();
            
           
            if($userInfo){   //is_auth 0未认证 1已提交审核 2已认证 3认证驳回
                $level = new level();
                $mylevel = $level->where('value',$userInfo['level'])->field('name')->find();
                if($userInfo['level']){
                   $userInfo['level_name'] = $mylevel['name'];
                }
                if(empty($userInfo['signature'])){
                    $userInfo['signature'] = '';
                }
                $userInfo['levelname'] = $mylevel['name'];
                if($userInfo['start_level']){
                   $userInfo['start_levelname'] = $mystartlevel = Db::name('start_level')->where(['value'=>$userInfo['start_level']])->value('name');
                }else{
                    $userInfo['start_levelname'] = '无';
                }
                if($userInfo['is_auth']==1){
                   $userInfo['is_auth'] = '已实名';
                }else{
                    $userInfo['is_auth'] = '未实名';
                }
                $userInfo['addtime'] = date('Y-m-d H:i:s',$userInfo['regetime']);
                
                $common_model = new Common();
               
                $userInfo['class_learning_power'] = $userInfo['class_dedication'];
                
                
            }else{
                $userInfo = '';
            }
        }else{
            return returnjson(1001,'','参数缺失！');
        }
        
        
        return returnjson(1000,$userInfo,'获取成功');
    }

    /*
     * 分享卡片- 上传图片
     */
    public function uploadShareImg() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $id = input('id');
        $file = request()->file('img');
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            $filename = $info->getSaveName();
            $file = 'uploads/'. $info->getSaveName();
            $url = uploadLocalToOss($file,"images", $filename);
            unlink($file);
            $day = new \app\wxapp\model\Dayarticle();
            $dayarticle = $day->where(['id'=>$id])->field('imgurl,title,content,id')->find();
          	$dayarticle['imgurl']  = $url;
            if($dayarticle){
                $res = $this->makeQrcodeImg($this->uid,$dayarticle);
                $dayarticle['cardImg'] = $res['cardImg'];
                $dayarticle['qrcode'] = $res['qrcode'];
                $dayarticle['url'] = 'http://www.baidu.com';
                return returnjson(1000,$dayarticle,'获取成功');
            }
        }
    }

    //生成金句分享卡片
    public function createShare($id){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        //用户id
        $id  = input('id');
        $day = new \app\wxapp\model\Dayarticle();
        $articleCard = new DayarticleCard();
        $dayarticle = $day->where(['id'=>$id])->field('imgurl,title,content,id')->find();
        if($dayarticle){
            $dayarticle['url'] = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Login/h5_register?p_id='.$this->uid;
            $cardInfo = $articleCard->where(['uid'=>$this->uid,'article_id'=>$id])->find();
            if($cardInfo) {
                $dayarticle['img'] = $cardInfo['imgurl'];
                return returnjson(1000,$dayarticle,'获取成功');
            }
            $res = $this->makeQrcodeImg($this->uid,$dayarticle);
            $dayarticle['cardImg'] = $res['cardImg'];
            $dayarticle['qrcode'] = $res['qrcode'];
            return returnjson(1000,$dayarticle,'获取成功');
        }
        return returnjson(1000,'','无数据');
    }

    /*
     * 生成金句卡片
     */
    public function makeQrcodeImg($uid = 0,$dayarticle = []) {
        require_once '../vendor/phpqrcode/phpqrcode.php';
        $fontfile = '../vendor/topthink/think-captcha/assets/zhttfs/3.ttf';   //苹方黑体-极细-简
        $fontsize = 30;
        $fontsize1 = 14;
        // 第一步: 生成二维码
        $Qr = new \phpqrcode\QRcode();
        $value = GetCurUrl()."/wxapp/login/h5_register/p_id/".$uid;
        $errorCorrectionLevel = 'M';//容错级别
        $matrixPointSize = 7;//生成图片大小
        $newQrcodeUrl = "dayarticle/".$uid.'_dayarticle.png';
        $Qr->png($value, $newQrcodeUrl, $errorCorrectionLevel, $matrixPointSize, 2);

        //读取顶部背景图
        $backfile = $dayarticle['imgurl'];
        //获取图片信息
        $backimginfo = getimagesize($backfile);
        //获取图片扩展名
        $type = image_type_to_extension($backimginfo[2],false);
        //动态的把图片导入内存中
        $fun = "imagecreatefrom{$type}";
        $backimage = $fun($backfile);
        $newbackimg = 'dayarticle/'.time().rand(100,999).'.png';
        imagepng($backimage, $newbackimg);  // 生成新的背景图
        // 将背景图与模板合成
        $backimg_src = $newbackimg; //微信图片本地保存地址  //微信圆形图片
        $QR0 = "static/images/cardbg.png";  //底板图
        $logo0 = $backimg_src; //要处理的图形

        if ($logo0 !== FALSE) {
            $QR0 = imagecreatefromstring(file_get_contents($QR0));//读取原图像
            $logo0 = imagecreatefromstring(file_get_contents($logo0));//读取二维码图像
            $QR0_width = imagesx($QR0);//原图像宽度
            $QR0_height = imagesy($QR0);//原图像高度
            $logo0_width = imagesx($logo0);//二维码图片宽度
            $logo0_height = imagesy($logo0);//二维码图片高度

            //$logo0_qr_width = 457; //二维码的宽度
            //$logo0_qr_height = 269; //二维码高度
          $logo0_qr_width = 1300; //二维码的宽度
            $logo0_qr_height = 655; //二维码高度
            //$from0_width = 0; //距离左边距的宽度
            //$from0_height = 0; //距离左边距的宽度
			$from0_width = 0; //距离左边距的宽度
            $from0_height = 0; //距离左边距的宽度
            //重新组合图片并调整大小
            imagecopyresampled($QR0, $logo0, $from0_width, $from0_height, 0, 0, $logo0_qr_width,$logo0_qr_height, $logo0_width, $logo0_height);
            $newbackmoban = "moban_".time().rand(100,999).'.png';//原图
            imagepng($QR0, $newbackmoban);
            unlink($backimg_src);   //删除背景图
        }

        // 二维码 背景图合成
        $QR = $newbackmoban;  //原图像
        $logo = $newQrcodeUrl; //要处理的图形

        if ($logo !== FALSE) {
            $QR = imagecreatefromstring(file_get_contents($QR));//读取原图像
            $logo = imagecreatefromstring(file_get_contents($logo));//读取二维码图像
            $QR_width = imagesx($QR);//原图像宽度
            $QR_height = imagesy($QR);//原图像高度
            $logo_width = imagesx($logo);//二维码图片宽度
            $logo_height = imagesy($logo);//二维码图片高度

            $logo_qr_width = 200; //二维码的宽度
            $logo_qr_height = 200; //二维码高度
            $from_width = 100; //距离左边距的宽度
            $from_height = 1230; //距离顶边距的宽度

            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_height, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);
            $lasefilename = "qrcode_moban_".time().rand(100,999).'.png';//原图
            imagepng($QR, $lasefilename);
            unlink($newbackmoban); //删除原模板图片
        }

        //写入
        $file = $lasefilename;
        $titlelength = mb_strlen($dayarticle['title'],'UTF8');
        //获取图片信息
        $info = getimagesize($file);
        //获取图片扩展名
        $type = image_type_to_extension($info[2],false);
        //动态的把图片导入内存中
        $fun = "imagecreatefrom{$type}";
        $image = $fun($file);
        //指定字体颜色
        //指定字体内容
        //给图片添加文字
        //$color = array(255,243,173,1);
      $color = array(255,243,200,1);
        $fontsize = 30;
        $col = imagecolorallocatealpha($image,$color[0],$color[1],$color[2],$color[3]);
        $hcolor = imagecolorallocate($image,0,0,0); // 文字颜色
        //文字的横坐标 开始值
        $mobaninfo = imagecreatefromstring(file_get_contents($dayarticle['imgurl']));//读取原图像
        $moban_width = imagesx($mobaninfo);//原图像宽度 //宽度256  11个字宽
        $bluecolor = imagecolorallocate($image,0,0,0); // 文字颜色

        $font_file = '../vendor/topthink/think-captcha/assets/zhttfs/3.ttf';   //苹方黑体-极细-简
        imagettftext($image,40,0,100,750,$bluecolor,$font_file,$dayarticle['title']);
        $contentStyle = array("color" => array(0,0,0), "fontsize" =>32, "width" => 960, "left" => 100, "top" => 800, "hang_size" => 70);
        //这里我只用它做测量高度，把参数false改为true就是绘制了。
        $this->draw_txt_to($image, $contentStyle, $dayarticle['content'], true);
        //imagettftext($image,$fontsize,0,$x,120,$hcolor,$fontfile,$string);//写入邀请  参数说明:

        //第一个是画布资源 2 文字大小 3 旋转角度  4 x坐标  5  纵坐标
        imagettftext($image,28,0,350,1310,$hcolor,$font_file,'财商知识就在财学');
        imagettftext($image,28,0,350,1370,$hcolor,$font_file,'请与我一起精进');
        //写入邀请  参数说明: 第一个是画布资源 2 文字大小 3 旋转角度  4 x坐标  5  纵坐标
        $perferimg = time().rand(100,999).'.png';
        imagepng($image, 'dayarticle/'.$perferimg);
        unlink($file);
        $cardImg = uploadLocalToOss('dayarticle/'.$perferimg,"dayarticle", $perferimg);
        unlink('dayarticle/'.$perferimg);
        $qrcodeImg = uploadLocalToOss($newQrcodeUrl,"dayarticle", $uid.'_dayarticle.png');
        unlink($newQrcodeUrl); //删除微信头像圆形图片
        $articleCard = new DayarticleCard();
        $data = ['article_id'=>$dayarticle['id'],'imgurl'=>$cardImg,'uid'=>$uid,'addtime'=>time()];
        //$articleCard->insert($data);
        return ['cardImg'=>$cardImg,'qrcode'=>$qrcodeImg];
    }

    /**
     * 文字自动换行算法
     * @param $card 画板
     * @param $pos 数组，top距离画板顶端的距离，fontsize文字的大小，width宽度，left距离左边的距离，hang_size行高
     * @param $str 要写的字符串
     * @param $iswrite  是否输出，ture，  花出文字，false只计算占用的高度
     * @return int 返回整个字符所占用的高度
     */
    function draw_txt_to($card, $pos, $str, $iswrite){
        $_str_h = $pos["top"];
        $fontsize = $pos["fontsize"];
        $width = $pos["width"];
        $margin_lift = $pos["left"];
        $hang_size = $pos["hang_size"];
        $temp_string = "";
        $font_file = '../vendor/topthink/think-captcha/assets/zhttfs/3.ttf';   //苹方黑体-极细-简
        $tp = 0;
        $font_color = imagecolorallocate($card, $pos["color"][0], $pos["color"][1], $pos["color"][2]);
        for ($i = 0; $i < mb_strlen($str); $i++) {
            $box = imagettfbbox($fontsize, 0, $font_file, $temp_string);
            $_string_length = $box[2] - $box[0];
            $temptext = mb_substr($str, $i, 1);
            $temp = imagettfbbox($fontsize, 0, $font_file, $temptext);
            if ($_string_length + $temp[2] - $temp[0] < $width) {//长度不够，字数不够，需要
                //继续拼接字符串。
                $temp_string .= mb_substr($str, $i, 1);
                if ($i == mb_strlen($str) - 1) {//是不是最后半行。不满一行的情况
                    $_str_h += $hang_size;//计算整个文字换行后的高度。
                    $tp ++;//行数
                    if ($iswrite) {//是否需要写入，核心绘制函数
                        imagettftext($card, $fontsize, 0, $margin_lift, $_str_h, $font_color, $font_file, $temp_string);
                    }
                }
            } else {//一行的字数够了，长度够了。
                //打印输出，对字符串零时字符串置null
                $texts = mb_substr($str, $i, 1);//零时行的开头第一个字。
                // 判断默认第一个字符是不是符号；
                $isfuhao = preg_match("/[\\pP]/u", $texts) ? true : false;//一行的开头这个字符，是不是标点符号
                if ($isfuhao) {     //如果是标点符号，则添加在第一行的结尾
                    $temp_string .= $texts;
                     // 判断如果是连续两个字符出现，并且两个丢失必须放在句末尾的，单独处理
                    $f = mb_substr($str, $i + 1, 1);
                    $fh = preg_match("/[\\pP]/u", $f) ? true : false;
                    if ($fh) {
                        $temp_string .= $f;
                        $i ++;
                    }
                } else {
                    $i --;
                }
                $tmp_str_len = mb_strlen($temp_string);
                $s = mb_substr($temp_string, $tmp_str_len-1, 1);//取零时字符串最后一位字符
                if ($this->is_firstfuhao($s)) {//判断零时字符串的最后一个字符是不是可以放在见面
                    //讲最后一个字符用“_”代替。指针前移动一位。重新取被替换的字符。
                    $temp_string=rtrim($temp_string,$s);
                    $i--;
                }
                // 计算行高，和行数。
                $_str_h += $hang_size;
                $tp ++;
                if ($iswrite) {
                    imagettftext($card, $fontsize, 0, $margin_lift, $_str_h, $font_color, $font_file, $temp_string);
                }
               // 写完了改行，置null该行的临时字符串。
                $temp_string = "";
            }
        }
        return $tp * $hang_size;
    }
        //矫正贡献值
    public function correct(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $user = new \app\wxapp\model\User();
        
        $userInfo = $user->where('id',$this->uid)->field('id,dedication_value,power_dedication_small,learning_power')->find();
        $userInfo['power_dedication_small'] = $userInfo['power_dedication_small'] + $userInfo['learning_power'];
        return returnjson(1000,$userInfo,'更新成功');
    }        
    function is_firstfuhao($str){
        $fuhaos = array("\"", "“", "'", "<", "《",);
        return in_array($str, $fuhaos);
    }

    //测试接口
    public function test(){
        $user_model = new \app\wxapp\model\User();
        //获取邀请人最多的两个社群
       // $max_invate_nums = $user_model->field('id')->where(['parentids'=>'0,'])->order('invate_num desc')->limit(2)->select();
        //获取小社群的全部id
        
        //获取我的全部上级
        /*$parentids = $user_model -> where(['id'=>$this->uid])->field('parentids,pid');
        if(!empty($parentids)){
            if($parentids['parentids']){
                $parentids_ = explode(',',$parentids['parentids']);
                foreach($parentids_ as $pk=>$pv){
                    if($pv==0){
                        
                    }else{
                        
                    }
                }
            }
            
        }*/
        
        return returnjson(1000,$max_invate_nums,'获取成功');
    }
  
      //校验学分支付密码
    public function checkPass(){
		$token = input('token');
        $password = input('password');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,0,'该用户已在其他设备登陆');
        }
      	$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
     	 //$where['addtime'] = ['between',[$beginToday.','.$endToday]];
      	$count = Db::name('checkpass')->where('uid',$this->uid)->where('addtime','between',$beginToday.','.$endToday)->count();
      	
      	if($count>=15){
        	return returnjson(1001,0,'您已多次输错，请明天再试');
        }
      	$user = new \app\wxapp\model\User();
        $userinfo = $user->where('id',$this->uid)->find();
      	if(empty($userinfo['pay_password'])){
        	return returnjson(1000,1,'未设置支付密码');
        }
      	$pass = splice_password($password, $userinfo['pay_salt']);
      	if($pass!=$userinfo['pay_password']){
          Db::name('checkpass')->insert(['uid'=>$this->uid,'addtime'=>time()]);
          return returnjson(1001,0,'密码不正确');
        }
      	Db::name('checkpass')->where('uid',$this->uid)->delete();
      	return returnjson(1000,0,'验证通过');
      
    }
  public function updateVersion(){
        $token = input('token');
        $password = input('password');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,0,'该用户已在其他设备登陆');
        }
    	$system = Db::name('system')->where('id',1)->field('iosdown,is_constraint,iosversion,content')->find();
        return returnjson(1000,array('is_constraint'=>$system['is_constraint'],'version'=>$system['iosversion'],'url'=>$system['iosdown'],'content'=>$system['content']),'成功');
    }
  	public function giveOut(){
  	    
  	    ini_set ("memory_limit","-1");
    $common = new Common();
      	
      $common->giveOut();
    }
    //发放购买课程贡献值
    public function giveOut_course(){
  	    
  	    ini_set ("memory_limit","-1");
    $common = new Common();
      	
      $common->giveOut_course();
    }
    public function giveOut_long(){
  	    
  	    ini_set ("memory_limit","-1");
    $common = new Common();
      	
      $common->giveOut_long();
    }
    public function giveOut_long_primary(){
  	    
  	    ini_set ("memory_limit","-1");
    $common = new Common();
      	
      $common->giveOut_long_primary();
    }
    public function reissue_21_de(){
  	    
  	    ini_set ("memory_limit","-1");
    $common = new Common();
      	
      $common->reissue_21_de();
    }
    
  //自动执行未送基础包的用户
    public function compensation(){exit;
      //echo 111111111;exit;
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,0,'该用户已在其他设备登陆');
        }
       /* $ustr = Db::name('text')->where('id',1)->find();
      	$ustr = explode(',',$ustr['text']);
     // echo "<pre>";
      //print_r($ustr);exit;
      foreach($ustr as $k => $v){
        
      	 Db::startTrans();
          $common = new Common();
      	//$documentRoot = $_SERVER['DOCUMENT_ROOT'];
//file_put_contents($documentRoot.'/log_0806__1.txt',print_r(date('Y-m-d H:i:s',time()),true),FILE_APPEND);
          if(false === $common->userChangeLevel($v)){
                    Db::rollback();
                    echo '失败'.$v."<br>";
           }
          Db::commit();
      }
      echo 'chengg';exit;
      exit;*/
        $users = Db::name('user')->alias('ua')->join('face_order fo','ua.id=fo.uid','left')->where('ua.is_auth',1)->where('fo.status',1)->field('ua.id')->select();
        //$count = Db::name('user')->alias('ua')->join('face_order fo','ua.id=fo.uid','left')->where('ua.is_auth',1)->where('fo.status',1)->field('ua.id')->count();
        $str = '';
        foreach($users as $ukey=>$uval){
            $data['uid'] = $uval['id'];
            $data['course_id'] = 2;
            $data['status'] = 1;
            //$data['pay_type'] = 0;
            $has = Db::name('order')->where($data)->find();
            $I = 0;
            if($has){
                
                continue;
            }
           $I = $I+1;
           // Db::startTrans();
          	$str .= $uval['id'].',';
            //$common = new Common();
                
                //if(false === $common->userChangeLevel($uval['id'])){
                 //   Db::rollback();
                  //  return returnjson(1001,'','认证失败');
               // }
               // Db::commit();
        }
      	$text = trim($str,',');
      	$data1['text'] = $text;
     	Db::name('text')->insert($data1);
        echo 455555666;exit;
        
    }
    
    public function class_dedication_count(){exit;
        ini_set ("memory_limit","-1");
        $token = input('token');
        if(!empty($token)) {
            //$this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            //return returnjson(1100,0,'该用户已在其他设备登陆');
        }
        //exit;
        
        $common_model = new Common();
        $user = new \app\wxapp\model\User();
        //$users  = $user->where('is_puls',0)->limit(500)->field('id')->select();
        $users  = $user->where('id',748)->limit(500)->field('id')->select();
        foreach($users as $k=>$v){
            
            $res = $common_model->isMaxOrMin($v['id'],1);
            $p = Db::name('user')->where('id',$v['id'])->value('is_puls');
            if($p == 3){
                continue;
            }
            //echo $res['total_num'];exit;
              //$num = $v['class_dedication']+$res['total_num'];
              Db::name('user')->where('id',$v['id'])->update(['class_dedication'=>$res['total_num'],'is_puls'=>3]);
              
        }
        echo '执行完成';
        
    }
    public function class_dedication_count_t(){exit;
        ini_set ("memory_limit","-1");
        $token = input('token');
        if(!empty($token)) {
            //$this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            //return returnjson(1100,0,'该用户已在其他设备登陆');
        }
        //exit;
        $common_model = new Common();
        $user = new \app\wxapp\model\User();
        $users  = $user->where('is_puls',0)->limit(1000,500)->field('id')->select();
        //$users  = $user->where('id',2970)->limit(500)->field('id')->select();
        foreach($users as $k=>$v){
            
            $res = $common_model->isMaxOrMin($v['id'],1);
            $p = Db::name('user')->where('id',$v['id'])->value('is_puls');
            if($p == 1){
                continue;
            }
            //echo $res['total_num'];exit;
              //$num = $v['class_dedication']+$res['total_num'];
              Db::name('user')->where('id',$v['id'])->update(['class_dedication'=>$res['total_num'],'is_puls'=>1]);
              
        }
        echo '执行完成';
        
    }
    public function class_dedication_count_tr(){exit;
        ini_set ("memory_limit","-1");
        $token = input('token');
        if(!empty($token)) {
            //$this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            //return returnjson(1100,0,'该用户已在其他设备登陆');
        }
        //exit;
        $common_model = new Common();
        $user = new \app\wxapp\model\User();
        $users  = $user->where('is_puls',0)->limit(1500,500)->field('id')->select();
        //$users  = $user->where('id',2970)->limit(500)->field('id')->select();
        foreach($users as $k=>$v){
            
            $res = $common_model->isMaxOrMin($v['id'],1);
            $p = Db::name('user')->where('id',$v['id'])->value('is_puls');
            if($p == 1){
                continue;
            }
            //echo $res['total_num'];exit;
              //$num = $v['class_dedication']+$res['total_num'];
              Db::name('user')->where('id',$v['id'])->update(['class_dedication'=>$res['total_num'],'is_puls'=>1]);
              
        }
        echo '执行完成';
        
    }
    public function class_dedication_count_fo(){exit;
        ini_set ("memory_limit","-1");
        $token = input('token');
        if(!empty($token)) {
            //$this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            //return returnjson(1100,0,'该用户已在其他设备登陆');
        }
        //exit;
        $common_model = new Common();
        $user = new \app\wxapp\model\User();
        $users  = $user->where('is_puls',0)->limit(2000,500)->field('id')->select();
        //$users  = $user->where('id',2970)->limit(500)->field('id')->select();
        foreach($users as $k=>$v){
            
            $res = $common_model->isMaxOrMin($v['id'],1);
            $p = Db::name('user')->where('id',$v['id'])->value('is_puls');
            if($p == 1){
                continue;
            }
            //echo $res['total_num'];exit;
              //$num = $v['class_dedication']+$res['total_num'];
              Db::name('user')->where('id',$v['id'])->update(['class_dedication'=>$res['total_num'],'is_puls'=>1]);
              
        }
        echo '执行完成';
        
    }
    public function class_dedication_count_fa(){exit;
        ini_set ("memory_limit","-1");
        $token = input('token');
        if(!empty($token)) {
            //$this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            //return returnjson(1100,0,'该用户已在其他设备登陆');
        }
        //exit;
        $common_model = new Common();
        $user = new \app\wxapp\model\User();
        $users  = $user->where('is_puls',1)->limit(2500,500)->field('id')->select();
        //$users  = $user->where('id',2970)->limit(500)->field('id')->select();
        foreach($users as $k=>$v){
            
            $res = $common_model->isMaxOrMin($v['id'],0);
            $p = Db::name('user')->where('id',$v['id'])->value('is_puls');
            if($p == 1){
                continue;
            }
            //echo $res['total_num'];exit;
              //$num = $v['class_dedication']+$res['total_num'];
              Db::name('user')->where('id',$v['id'])->update(['class_dedication'=>$res['total_num'],'is_puls'=>1]);
              
        }
        echo '执行完成';
        
    }
    public function class_dedication_count_sx(){exit;
        ini_set ("memory_limit","-1");
        $token = input('token');
        if(!empty($token)) {
            //$this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            //return returnjson(1100,0,'该用户已在其他设备登陆');
        }
        //exit;
        $common_model = new Common();
        $user = new \app\wxapp\model\User();
        $users  = $user->where('is_puls',0)->limit(3000,500)->field('id')->select();
        //$users  = $user->where('id',2970)->limit(500)->field('id')->select();
        foreach($users as $k=>$v){
            
            $res = $common_model->isMaxOrMin($v['id'],1);
            $p = Db::name('user')->where('id',$v['id'])->value('is_puls');
            if($p == 1){
                continue;
            }
            //echo $res['total_num'];exit;
              //$num = $v['class_dedication']+$res['total_num'];
              Db::name('user')->where('id',$v['id'])->update(['class_dedication'=>$res['total_num'],'is_puls'=>1]);
              
        }
        echo '执行完成';
        
    }
    public function class_dedication_count_sv(){exit;
        ini_set ("memory_limit","-1");
        $token = input('token');
        if(!empty($token)) {
            //$this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            //return returnjson(1100,0,'该用户已在其他设备登陆');
        }
        //exit;
        $common_model = new Common();
        $user = new \app\wxapp\model\User();
        $users  = $user->where('is_puls',0)->limit(3500,500)->field('id')->select();
        //$users  = $user->where('id',2970)->limit(500)->field('id')->select();
        foreach($users as $k=>$v){
            
            $res = $common_model->isMaxOrMin($v['id'],1);
            $p = Db::name('user')->where('id',$v['id'])->value('is_puls');
            if($p == 1){
                continue;
            }
            //echo $res['total_num'];exit;
              //$num = $v['class_dedication']+$res['total_num'];
              Db::name('user')->where('id',$v['id'])->update(['class_dedication'=>$res['total_num'],'is_puls'=>1]);
              
        }
        echo '执行完成';
        
    }
    
    public function unlock(){
        exit;
        $all = Db::name('course_learn_log')->where('unlocktime','lt',time())->where('status',0)->select();
        return returnjson(1000,$all,'');
    }
    public function cc(){
        //$query = Db::query('select u.id,count(c.course_id) as cc from user as u left join course_learn_log as c on u.id=c.uid where c.status=0 and c.course_id=2 group by c.uid having cc =1');
        //$query = Db::name('user')->alias('u')->join('course_learn_log c','c.uid=u.id')->field('u.id,count(c.course_id)')->where('c.course_id',2)->where('count(c.course_id)','gt',1)->select();
        $i = 0;
        $ids = '';
        $alluser = Db::name('user')->where('is_auth',1)->field('id')->select();
        foreach($alluser as $auk=>$auv){
            $hascount = Db::name('course_learn_log')->where('course_id',2)->where('uid',$auv['id'])->count();
            if($hascount>0){
                $ccount = Db::name('course_learn_log')->where('course_id',2)->where('uid',$auv['id'])->where('status',0)->count();
                if($ccount == 0){
                    $i = $i+1;
                    $ids .= $auv['id'].',';
                }
            }
            
        }
        //$documentRoot = $_SERVER['DOCUMENT_ROOT'];
//file_put_contents($documentRoot.'/log_0819.txt',print_r($ids,true),FILE_APPEND);
        //Db::name('unlock_text')->insert(['ids'=>$ids,'addtime'=>time()]);
        var_dump($i);exit;
        //$query = Db::name('order')->where('course_id',2)->where('status',['eq',2],['eq',3],'or')->select();
        foreach($query as $v){
            //$this->callbacks($v['uid']);
            //echo $v['uid'];echo '-->';
            //$this->callbacks(548);
        }
        //$this->callbacks(27227);
        
    }
    public function get_unnecessary_unlock(){
        //$alluser = Db::name('user')->where('is_auth',1)->field('id')->select();
        $i = 0;
        $ids = '';
        $alluser = Db::name('user')->where('is_auth',1)->field('id')->select();
        foreach($alluser as $auk=>$auv){
            $hascount = Db::name('course_learn_log')->where('course_id',2)->where('uid',$auv['id'])->count();
            if($hascount>0){
                $ccount = Db::name('course_learn_log')->where('course_id',2)->where('uid',$auv['id'])->where('status',0)->count();
                if($ccount == 0){
                    $i = $i+1;
                    $ids .= $auv['id'].',';
                }
            }
            
        }
        //$documentRoot = $_SERVER['DOCUMENT_ROOT'];
//file_put_contents($documentRoot.'/log_0819.txt',print_r($ids,true),FILE_APPEND);
        //Db::name('unlock_text')->insert(['ids'=>$ids,'addtime'=>time()]);
        var_dump($i);exit;
    }
    //返回多余的学分
    public function callbacks($id){
        exit;
        //var_dump($query);exit;
        //exit;
        //$id = 39363;
        $all = Db::name('user')->where('id',$id)->field('id,tel,pid,score')->select();
        
        foreach($all as $akey=>$aval){
            $swhere = array(
                    'type'=>5,
                    'uid'=>$aval['id']
                    
            );
            $someorder = Db::name('creditSource')->where($swhere)->where('addtime','between','1597593600,1597734000')->select();
            Db::startTrans();
            try {
            if(!empty($someorder)){
                foreach($someorder as $sk=>$sv){
                    
                    $backscore = abs($sv['score']);
                    
                    
                    Db::name('user')->where('id',$aval['id'])->setInc('score',$backscore);
                    Db::name('creditSource')->where('id',$sv['id'])->delete();
                    Db::name('order')->where('uid',$aval['id'])->where('course_id',$sv['value'])->delete();
                    $thiscs = Db::name('creditSource')->where('value',$sv['value'])->where('uid',$aval['id'])->where('type',1)->sum('score');
                    Db::name('user')->where('id',$aval['id'])->setDec('score',$thiscs);
                    Db::name('creditSource')->where('value',$sv['value'])->where('uid',$aval['id'])->where('type',1)->delete();
                    
                    $thissection = Db::name('section')->where('c_id',$sv['value'])->where('is_delete',0)->field('id')->select();
                    $s_ids = '';
                    foreach($thissection as $thisval){
                        $s_ids .= ','.$thisval['id'];
                    }
                    $s_ids = trim($s_ids,',');
                    $taskids = Db::name('task')->where('section_id','in',$s_ids)->field('id')->select();
                    $t_ids = '';
                    foreach($taskids as $taval){
                        $t_ids .= ','.$taval['id'];
                    }
                    $t_ids = trim($t_ids,',');
                    Db::name('task_result')->where('uid',$aval['id'])->where('task_id','in',$t_ids)->delete();
                    Db::name('course_learn_log')->where('course_id',$sv['value'])->where('uid',$aval['id'])->delete();
                    
                }
            }
            //exit;
            //查询所有有效订单
            $allorder = Db::name('order')->where('uid',$aval['id'])->where('status','neq',0)->field('id,course_id,uid,paytime,status')->select();
            
            foreach($allorder as $okey=>$oval){
                
                $onelock = Db::name('course_learn_log')->where('unlocktime','between','1597593600,1597680000')->where('course_id',$oval['course_id'])->where('uid',$aval['id'])->order('id asc')->select();
                
                $onecount = count($onelock);
                //echo $onecount;
                if($onecount > 1){
                    $ss_ids = '';
                    foreach($onelock as $onkey=> $onval){
                        //if($onkey!=0){
                            $ss_ids .= ','.$onval['section_id'];
                        //}
                    }
                    $taskids = Db::name('task')->where('section_id','in',$ss_ids)->field('id')->select();
                    $t_ids = '';
                    foreach($taskids as $taval){
                        $t_ids .= ','.$taval['id'];
                    }
                    $t_ids = trim($t_ids,',');
                    //echo $t_ids;
                    Db::name('course_learn_log')->where('unlocktime','between','1597593600,1597734000')->where('course_id',$oval['course_id'])->where('uid',$aval['id'])->where('id','neq',$onelock[0]['id'])->delete();
                    Db::name('task_result')->where('task_id','in',$t_ids)->where('uid',$aval['id'])->delete();
                    
                    Db::name('course_learn_log')->where('id',$onelock[0]['id'])->update(['status'=>0]);
                    $twolock = Db::name('creditSource')->where('addtime','between','1597593600,1597734000')->where('uid',$aval['id'])->where('type',1)->where('value',$oval['course_id'])->sum('score');
                    Db::name('creditSource')->where('addtime','between','1597593600,1597734000')->where('uid',$aval['id'])->where('type',1)->where('value',$oval['course_id'])->delete();
                    
                    //$twocount = count($twolock);
                    //$towcs = Db::name('creditSource')->where('course_id',$sv['value'])->where('addtime','between','1597680000,1597766400')->where('uid',$aval['id'])->where('type',1)->sum('score');
                    Db::name('user')->where('id',$aval['id'])->setDec('score',$twolock);
                    
                }
                $towlock = Db::name('course_learn_log')->where('unlocktime','between','1597680000,1597734000')->where('course_id',$oval['course_id'])->where('uid',$aval['id'])->order('id asc')->select();
                
                $twocount = count($towlock);
                //echo $onecount;
                if($twocount > 1){
                    $ss_ids = '';
                    foreach($twocount as $onkey=> $onval){
                        //if($onkey!=0){
                            $ss_ids .= ','.$onval['section_id'];
                        //}
                    }
                    $taskids = Db::name('task')->where('section_id','in',$ss_ids)->field('id')->select();
                    $t_ids = '';
                    foreach($taskids as $taval){
                        $t_ids .= ','.$taval['id'];
                    }
                    $t_ids = trim($t_ids,',');
                    //echo $t_ids;
                    Db::name('course_learn_log')->where('unlocktime','between','1597680000,1597734000')->where('course_id',$oval['course_id'])->where('uid',$aval['id'])->where('id','neq',$onelock[0]['id'])->delete();
                    Db::name('task_result')->where('task_id','in',$t_ids)->where('uid',$aval['id'])->delete();
                     //$documentRoot = $_SERVER['DOCUMENT_ROOT'];
//file_put_contents($documentRoot.'/log_0818_.txt',print_r(Db::name('task_result')->getLastSql(),true),FILE_APPEND);
                    Db::name('course_learn_log')->where('id',$onelock[0]['id'])->update(['status'=>0]);
                    $twolock = Db::name('creditSource')->where('addtime','between','1597680000,1597734000')->where('uid',$aval['id'])->where('type',1)->where('value',$oval['course_id'])->sum('score');
                    Db::name('creditSource')->where('addtime','between','1597680000,1597734000')->where('uid',$aval['id'])->where('type',1)->where('value',$oval['course_id'])->delete();
                    //echo $twolock;
                    //$twocount = count($twolock);
                    //$towcs = Db::name('creditSource')->where('course_id',$sv['value'])->where('addtime','between','1597680000,1597766400')->where('uid',$aval['id'])->where('type',1)->sum('score');
                    Db::name('user')->where('id',$aval['id'])->setDec('score',$twolock);
                    
                }
            }
            Db::commit();
            } catch (\Exception $e) {
                dump($e->getMessage()); //打印错误
            Db::rollback(); //同时回滚，将不会插入任何一条
            }
            
        }
        echo '执行完成';
    }
    
    public function supplyunlock(){
        exit;
        //$learnLog = new CourseLearnLog();
        /*$allun = Db::name('unlock_text')->where('id',1)->find();
        $allun['ids'] = trim($allun['ids'],',');
        $ids = explode(',',$allun['ids']);
        foreach($ids as $idv){
            Db::name('unlock')->insert(['uid'=>$idv,'addtime'=>time()]);
        }*/
        $course_id = 2;
        $allids = Db::name('unlock')->where('status',0)->limit(2000)->select();
        foreach($allids as $aik=>$aiv){
            $hascount = Db::name('course_learn_log')->where('course_id',2)->where('uid',$aiv['uid'])->count();
            if($hascount>0){
                $ccount = Db::name('course_learn_log')->where('course_id',2)->where('uid',$aiv['uid'])->where('status',0)->count();
                if($ccount == 0){
                   $mycll = Db::name('course_learn_log')->where('uid',$aiv['uid'])->where('course_id',2)->order('id','desc')->find();
                   if($mycll['status']==1){
                       $learnData = [];
                       $section_ = Db::name('section')->where(['id'=>$mycll['section_id']])->field('sort')->find();
                        $sectionList = Db::name('section')->where(['c_id'=>$course_id,'is_delete'=>0])->where('sort','gt',$section_['sort'])->order('sort asc')->find();
                        
                
                        $learnData['section_id'] = $sectionList['id'];
                        $learnData['uid'] = $aiv['uid'];
                        $learnData['course_id'] = $course_id;
                        $learnData['addtime'] = time();
                        $learnData['unlocktime'] = $mycll['unlocktime']+86400;
                        //var_dump($learnData);
                        Db::name('course_learn_log')->insert($learnData);
                        Db::name('unlock')->where('id',$aiv['id'])->update(['status'=>1]);
                   }
                    
                }
            }
        }
        exit;
        
        $order = Db::name('order')->where('course_id',$course_id)->where('status',['eq',2],['eq',3],'or')->select();
        var_dump($order);exit;
        foreach($order as $okey=>$ovalue){
            $mycll = Db::name('course_learn_log')->where('uid',$ovalue['uid'])->where('course_id',2)->order('id','desc')->find();
            //var_dump($ovalue);
            var_dump($mycll);
                //exit;
            if($mycll['status']==1){
                $learnData = [];
                $sectionList = $section_model->where(['c_id'=>$course_id,'is_delete'=>0])->where('sort','gt',$mycll['section_id'])->order('sort asc')->find();
                
                
                        $learnData['section_id'] = $sectionList['id'];
                        $learnData['uid'] = $ovalue['uid'];
                        $learnData['course_id'] = $course_id;
                        $learnData['addtime'] = time();
                        $learnData['unlocktime'] = $tomorrow_start_time;
                       // Db::name('course_learn_log')->insert($learnData);
            }else{
                Db::name('order')->where('id',$ovalue['id'])->update(['status'=>1]);
            }
        }
        //var_dump($order);exit;
    }
    //查询跳课解锁的
    public function supplyunlock_(){exit;
        //$learnLog = new CourseLearnLog();
        /*$allun = Db::name('unlock_text')->where('id',1)->find();
        $allun['ids'] = trim($allun['ids'],',');
        $ids = explode(',',$allun['ids']);
        foreach($ids as $idv){
            Db::name('unlock')->insert(['uid'=>$idv,'addtime'=>time()]);
        }*/
        $ids = '';
        $i =0;
        $course_id = 2;
        $allids = Db::name('user')->where('is_auth',1)->field('id as uid')->limit(0,10000)->select();
        //allids = Db::name('unlock')->where('status',1)->limit(10000)->select();
        $sorts = Db::name('section')->where('c_id',2)->where('is_delete',0)->field('id,sort')->order('sort','acs')->select();
        //var_dump($sorts);exit;
        foreach($allids as $aik=>$aiv){
            $orders = Db::name('order')->where('uid',$aiv['uid'])->where('status',1)->count();
            if($orders==0){
                continue;
            }
            $face_orders = Db::name('face_order')->where('uid',$aiv['uid'])->where('status',1)->count();
            if($face_orders<=0){
                continue;
            }
            $hascount = Db::name('course_learn_log')->where('course_id',2)->where('uid',$aiv['uid'])->count();
            
            if($hascount>0){
                
                $course_learn_log = Db::name('course_learn_log')->where('course_id',2)->where('uid',$aiv['uid'])->order('id','acs')->field('id,uid,status,section_id')->select();
                
                foreach($course_learn_log as $ck=>$cv){
                    //echo $cv['section_id'].'=>'.$sorts[$ck]['id'];exit;
                    if($cv['section_id']!=$sorts[$ck]['id']){
                        $i = $i+1;
                        $ids .= $aiv['uid'].',';
                        break;
                        
                    }
                }
            }
        }
        echo $ids;
        echo $i;
        exit;
        
        
        //var_dump($order);exit;
    }
    //获取发放了课程包没有解锁的
    
    public function get_unnecessary_unlock_(){
        exit;
        //$alluser = Db::name('user')->where('is_auth',1)->field('id')->select();
        $i = 0;
        $ids = '';
        $alluser = Db::name('order')->where('status',1)->field('id,uid,course_id')->where('course_id',2)->select();
        foreach($alluser as $auk=>$auv){
            $hascount = Db::name('course_learn_log')->where('course_id',$auv['course_id'])->where('uid',$auv['uid'])->count();
            if($hascount==0){
                //$ccount = Db::name('course_learn_log')->where('course_id',2)->where('uid',$auv['id'])->where('status',0)->count();
               // if($ccount == 0){
                    $i = $i+1;
                    $ids .= $auv['uid'].',';
                    Db::name('unnecessary_unlock_one')->insert(['uid'=>$auv['uid'],'addtime'=>time()]);
               // }
            }
            
        }
        //$documentRoot = $_SERVER['DOCUMENT_ROOT'];
//file_put_contents($documentRoot.'/log_0819.txt',print_r($ids,true),FILE_APPEND);
        //Db::name('unlock_text')->insert(['ids'=>$ids,'addtime'=>time()]);
        var_dump($ids);
        var_dump($i);exit;
    }
    //处理第一课没有送的
    public function unnecessary_unlock_(){
        //$alluser = Db::name('user')->where('is_auth',1)->field('id')->select();
        exit;
        $i = 0;
        $ids = '';
        $alluser = Db::name('order')->where('status',1)->field('id,uid,course_id,paytime')->where('course_id',2)->select();
        foreach($alluser as $auk=>$auv){
            $hascount = Db::name('course_learn_log')->where('course_id',$auv['course_id'])->where('uid',$auv['uid'])->count();
            if($hascount==0){
                
                $learnData = [];
                
                        $learnData['section_id'] = 1;
                        $learnData['uid'] = $auv['uid'];
                        $learnData['course_id'] = $auv['course_id'];
                        $learnData['addtime'] = time();
                        $learnData['unlocktime'] = $auv['paytime'];
                Db::name('course_learn_log')->insert($learnData);
                Db::name('unnecessary_unlock_one')->where('uid',$auv['uid'])->update(['status'=>1]);
                //var_dump($learnData);exit;
                //if($ccount == 0){
                    $i = $i+1;
                    $ids .= $auv['uid'].',';
                //}
            }
            
        }
        //$documentRoot = $_SERVER['DOCUMENT_ROOT'];
//file_put_contents($documentRoot.'/log_0819.txt',print_r($ids,true),FILE_APPEND);
        //Db::name('unlock_text')->insert(['ids'=>$ids,'addtime'=>time()]);
        var_dump($ids);
        var_dump($i);exit;
    }
    //修正小贡献
    public function amend_power_dedication(){
        ini_set ("memory_limit","-1");
        $common_model = new Common();$ids = '';$i= 0;
        //$all = Db::name('user')->limit(0,10000)->field('id,learning_power')->where('is_puls','neq',7)->select();
        //$all = Db::name('user')->limit(0,10000)->field('id,learning_power,class_dedication')->where('id','51140')->select();
        $all = Db::name('user')->limit(30000)->field('id,learning_power,class_dedication')->order('id','desc')->where('is_puls','neq',10)->select();
        foreach($all as $k=>$v){
            $res = $common_model->amend_power_dedication($v['id']);
            $res = $res + $v['learning_power'];
            //echo $res;exit;
            //Db::startTrans();
            //$ures = Db::name('user')->where('id',$v['id'])->update(['class_dedication'=>$res,'is_puls'=>7]);
           /* if($ures){
                Db::commit();
            }else{
                Db::rollback();
            }*/
            //if($res!=$v['class_dedication']){
                $ures = Db::name('user')->where('id',$v['id'])->update(['class_dedication'=>$res,'is_puls'=>10]);
                $i++;
                //$ids .= $v['id'].',';
            //}
        }
        
        
        
        //var_dump($ids);
        echo "<br>";echo $i;
    }
    //修正小贡献
    public function amend_power_dedication_small(){
        ini_set ("memory_limit","-1");
        $common_model = new Common();$ids = '';$i= 0;
        //$all = Db::name('user')->limit(0,10000)->field('id,learning_power')->where('is_puls','neq',7)->select();
        $all = Db::name('user')->limit(0,30000)->field('id,learning_power,class_dedication')->where('is_puls','neq',11)->select();
        //$all = Db::name('user')->limit(0,10000)->field('id,learning_power,class_dedication')->order('id','desc')->select();
        foreach($all as $k=>$v){
            $res = $common_model->minShequnD($v['id'],1,1);
            //$res = $res + $v['learning_power'];
            //echo $res;exit;
            //Db::startTrans();
            //$ures = Db::name('user')->where('id',$v['id'])->update(['class_dedication'=>$res,'is_puls'=>7]);
           /* if($ures){
                Db::commit();
            }else{
                Db::rollback();
            }*/
            
                $ures = Db::name('user')->where('id',$v['id'])->update(['power_dedication_small'=>$res,'is_puls'=>11]);
                $i++;
                //$ids .= $v['id'].',';
            
        }
        
        
        
        //var_dump($ids);
        echo "<br>";echo $i;
    }
    
    //全球手续费分红
    public function CreditSourceBonus(){
        ini_set ("memory_limit","-1");
        $token = input('token');
        if(!empty($token)) {
            //$this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            //return returnjson(1100,0,'该用户已在其他设备登陆');
        }
        //exit;
        $common = new Common();
        //$startlevel = Db::name('startLevel')->where('is_delete')->field('id,value,bonus')->select(); 
       $allsource = Db::name('CreditSourceBonus')->where('status',0)->sum('source');
       $userone = Db::name('user')->where('is_auth',1)->where('start_level',1)->field('id')->select();
       $usertwo = Db::name('user')->where('is_auth',1)->where('start_level',2)->field('id')->select();
       $userthree = Db::name('user')->where('is_auth',1)->where('start_level',3)->field('id')->select();
       $userfour = Db::name('user')->where('is_auth',1)->where('start_level',4)->field('id')->select();
       $onecount = count($userone);
       $twocount = count($usertwo);
       $threecount = count($userthree);
       $fourcount = count($userfour);
       if($onecount>0){
           $onestartlevel = Db::name('startLevel')->where('is_delete',0)->where('value',1)->value('bonus');
           $onebonus = $allsource * ($onestartlevel/100);
           $oneper = round($onebonus/$onecount,4);
           //echo $oneper;echo '-->'.$onebonus;echo "<br>";
           Db::startTrans();
           foreach($userone as $onekey=>$oneval){
               
               if(!Db::name('user')->where('id',$oneval['id'])->setInc('score',$oneper)){
                    //echo Db::name('user')->getLastSql();
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                $data =[
                    'type'=>10,'uid'=>$oneval['id'],'pay_type'=>0,'score'=>"+".$oneper,
                    'status'=>1,'note'=>"置换手续费分红",'value'=>0,'addtime'=>time()
                ];
                $creditres = $common->creditSource($data,$oneval['id']);
                if(false === $creditres){
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                
           }
           $logdata = [
                        'uids' =>0,
                        'start_level' => 1,
                        'source' => $onebonus,
                        'status' =>1,
                        'num' => $onecount,
                        'addtime' => time(),
                        'sendtime' => time(),
                        'persource' => $oneper
                                
                        ];
           
           if(!Db::name('CreditSourceBonusLog')->insert($logdata)){
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
           Db::commit();
       }
       if($twocount>0){
           $twostartlevel = Db::name('startLevel')->where('is_delete',0)->where('value',2)->value('bonus');
           $twobonus = round($allsource * ($twostartlevel/100),4);
           $twoper = round($twobonus/$twocount,4);
           
           Db::startTrans();
           foreach($usertwo as $twokey=>$twoval){
               
               if(!Db::name('user')->where('id',$twoval['id'])->setInc('score',$twoper)){
                   // echo 1;
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                $data =[
                    'type'=>10,'uid'=>$twoval['id'],'pay_type'=>0,'score'=>"+".$twoper,
                    'status'=>1,'note'=>"置换手续费分红",'value'=>0,'addtime'=>time()
                ];
                $creditres = $common->creditSource($data,$twoval['id']);
                if(false === $creditres){
                    //echo 2;
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                
           }
           //echo 33333333;
           $logdata = [
                        'uids' =>0,
                        'start_level' => 2,
                        'source' => $twobonus,
                        'status' =>1,
                        'num' => $twocount,
                        'addtime' => time(),
                        'sendtime' => time(),
                        'persource' => $twoper
                                
                        ];
           
           if(!Db::name('CreditSourceBonusLog')->insert($logdata)){
               //echo 4444444;
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                //echo Db::name('CreditSourceBonusLog')->getLastSql();
            Db::commit();    
       }
       
       if($threecount>0){
           $threestartlevel = Db::name('startLevel')->where('is_delete',0)->where('value',3)->value('bonus');
           $threebonus = $allsource * ($threestartlevel/100);
           $threeper = round($threebonus/$threecount,4);
          // echo $threeper;echo '-->'.$threebonus;echo "<br>";
          Db::startTrans();
           foreach($userthree as $threekey=>$threeval){
               
               if(!Db::name('user')->where('id',$threeval['id'])->setInc('score',$threeper)){
                    //echo Db::name('user')->getLastSql();
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                $data =[
                    'type'=>10,'uid'=>$threeval['id'],'pay_type'=>0,'score'=>"+".$threeper,
                    'status'=>1,'note'=>"置换手续费分红",'value'=>0,'addtime'=>time()
                ];
                $creditres = $common->creditSource($data,$threeval['id']);
                if(false === $creditres){
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                
           }
           $logdata = [
                        'uids' =>0,
                        'start_level' => 3,
                        'source' => $threebonus,
                        'status' =>1,
                        'num' => $threecount,
                        'addtime' => time(),
                        'sendtime' => time(),
                        'persource' => $threeper
                                
                        ];
           
           if(!Db::name('CreditSourceBonusLog')->insert($logdata)){
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
            Db::commit();    
       }
       if($fourcount>0){
           $fourstartlevel = Db::name('startLevel')->where('is_delete',0)->where('value',4)->value('bonus');
           $fourbonus = $allsource * ($fourstartlevel/100);
           $fourper = round($fourbonus/$fourcount,4);
           //echo $fourper;echo '-->'.$fourbonus;echo "<br>";
           Db::startTrans();
           foreach($userfour as $fourkey=>$fourval){
               
               if(!Db::name('user')->where('id',$fourval['id'])->setInc('score',$fourper)){
                    //echo Db::name('user')->getLastSql();
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                $data =[
                    'type'=>10,'uid'=>$fourval['id'],'pay_type'=>0,'score'=>"+".$fourper,
                    'status'=>1,'note'=>"置换手续费分红",'value'=>0,'addtime'=>time()
                ];
                $creditres = $common->creditSource($data,$fourval['id']);
                if(false === $creditres){
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
                
           }
           $logdata = [
                        'uids' =>0,
                        'start_level' => 4,
                        'source' => $fourbonus,
                        'status' =>1,
                        'num' => $fourcount,
                        'addtime' => time(),
                        'sendtime' => time(),
                        'persource' => $fourper
                                
                        ];
           
           if(!Db::name('CreditSourceBonusLog')->insert($logdata)){
                    Db::rollback();
                    return returnjson(1001,'','兑换失败');
                }
            Db::commit();
       }
        Db::name('CreditSourceBonus')->where('status',0)->update(['status'=>1,'sendtime'=>time()]);
        //echo Db::name('CreditSourceBonus')->getLastSql();
            var_dump($allsource);
        echo '执行完成';
        
    }
  
}    