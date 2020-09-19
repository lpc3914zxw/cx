<?php
namespace app\wxapp\controller;
use app\index\model\Advanced;
use app\index\model\Sectiones;
use app\wxapp\model\CompulsoryCourse;
use app\index\model\Course;
use app\wxapp\model\MessageReadLog;
use app\wxapp\model\Orders;
use app\wxapp\model\Teachers;
use app\wxapp\controller\Base;
use app\common\Common;
use think\Db;
class Index extends Base {

    /*
     * 分享 4 文章  16 课程分享  1 每日才学  17 海报分享
     */
    public function shareObj($type = 1,$obj_id = '') {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        Db::startTrans();
        $common = new Common();
        $content = '';
        if($type == 4) {
            $content = '分享文章';
            // 荣誉值
            $common->honorLog($this->uid,5,$obj_id);
        }else if($type == 1) {
            $content = '金句分享';
            // 荣誉值
            if(false === $common->honorLog($this->uid,4,$obj_id)) {
                Db::rollback();
                return returnjson('1001','','分享失败');
            }
        }else if($type == 16) {
            $content = '课程分享';
        }else {
            return returnjson('1000','','分享成功');
        }

        $res = $common->dedicationLog($this->uid,$type,$obj_id,$content);
        $msg = '分享成功';
        if(false !== $res) {
            if($res == 0) {
                $msg = "今日".$content."贡献值获取已达上线";
            }else{
                $msg = "今日".$content.'获得'.$res.'贡献值';
            }
            Db::commit();
            return returnjson('1000','',$msg);
        }
        Db::rollback();
        return returnjson('1001','','分享失败');

    }

    public function test($str = '') {
        if(preg_match("/(\\d\\d)\\1+$/", $str)){  // AABBCCDD模式  12121212
            echo  'AABBCCDD模式';
        }
        if(preg_match("/^(?:(\\d\\d)\\1).*$/",$str)) {   // AB 模式
            echo  'AB模式';
        }
        if(preg_match("/^(?:(\\d)\\1).*$/",$str)) {   // AABB 模式
            echo  ",AABB模式";
        }
        if(preg_match("/^(?:(\\d\\d\\d)\\1).*$/",$str)) {
            echo ',ABCABC模式';
        }
        if(preg_match("/^(?:(\\d)\\1)+$/", $str)){  // AABBCCDD模式  22334455
            echo  'AABBCCDD模式';
        }
        if(preg_match("/^(?:(\\d)\\1\\1).*$/",$str)) {  // AAA 3A
            echo  ",3A模式";
        }
        if(preg_match("/(.*?(123)|(234)|(345)|(456)|(567)|(678)|(789).*)/",$str)) {  // ABC 3顺
            echo  ",3顺模式";
        }
        if(preg_match("/^(?:(\\d)\\1\\1\\1).*$/",$str)) {  // AAAA 模式
            echo  ",4A模式";
        }
        if(preg_match("/(.*?(1234)|(2345)|(3456)|(4567)|(5678)|(6789).*)/",$str)) {  //4顺
            echo ",4顺";
        }
        if(preg_match("/(.*?(12345)|(23456)|(34567)|(45678)|(56789).*)/",$str)) {  //5顺
            echo ",5顺";
        }
        if(preg_match("/^(?:(\\d)\\1\\1\\1\\1).*$/",$str)) {  // AAAAA 5A
            echo  ",5A模式";
        }
        if(preg_match("/^(?:(\\d)\\1\\1\\1\\1\\1).*$/",$str)) {  // AAAAA 6A
            echo  ",6A模式";
        }
        if(preg_match("/(.*?(123456)|(234567)|(345678)|(456789).*)/",$str)) {  //6顺
            echo ",6顺";
        }
        if(preg_match("/^(?:(\\d)\\1\\1\\1\\1\\1\\1).*$/",$str)) {  // AAAAA 7A
            echo  ",7A模式";
        }
        if(preg_match("/(.*?(1234567)|(2345678)|(3456789).*)/",$str)) {  //7顺
            echo ",7顺";
        }
        if(preg_match("/(.*?(520)|(521)|(920)|(921)|(1314)|(1711)|(9421).*)/",$str)) {  //爱情靓号
            echo ",爱情靓号";
        }

    }

    /*
     * 是否有未读信息
     * @return \type
     */
    public function hasMsg() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $message_model = new \app\index\model\Message();
        $messageReadLog = new MessageReadLog();
        $sysMsgCount = $message_model->getSysMsg($this->uid);
        $invMsgCount = $message_model->getInvMsg($this->uid);
        $systemMsgCount = $message_model->getSystemMsg($this->uid);
        $noReadNum = $sysMsgCount + $invMsgCount + $systemMsgCount;
        return returnjson(1000,$noReadNum,'获取成功');
    }

    /*
     * 获取免费课程信息
     */
    public function freeCourse() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $cscCourse_model = new Course();
        $compulsoryCourse = new CompulsoryCourse();
        $section_model = new Sectiones();
        $teacher_model = new Teachers();
        $compulsoryCourseInfo = $compulsoryCourse->find();
        $advanced_model = new Advanced();
        $user_model = new \app\wxapp\model\User();
        $advanced_id = $advanced_model->where(['type'=>3,'is_delete'=>0])->value('id');
        $courseInfo = $cscCourse_model->field('value,id,teacher_id,chapter_count,people_num')->where(['advanced_id'=>$advanced_id,'is_delete'=>0])->find();
        $order_model = new Orders();
      	
        if($courseInfo) {
            $teacherInfo = $teacher_model->field('name,introduction')->where('id',$courseInfo['teacher_id'])->find();
            $courseInfo['teacher_name'] = '';
            $courseInfo['teacher_intr'] = '';
            $courseInfo['name'] = $compulsoryCourseInfo['name'];
            $courseInfo['abstract'] = $compulsoryCourseInfo['abstract'];
            $count= $order_model->where(['course_id'=>$courseInfo['id'],'uid'=>$this->uid,'status'=>1])->count();
            if($count > 0) {
                $courseInfo['is_buy'] = 1;
            }else{
                $courseInfo['is_buy'] = 0;
            }
            $is_auth = $user_model->where('id',$this->uid)->value('is_auth');
            $courseInfo['is_auth'] = $is_auth;
            return returnjson(1000,$courseInfo,'获取成功');
        }
        return returnjson(1001,'','获取失败');
    }

    /*
     * 是否购买或者免费课程
     * @param int $uid
     */
    public function is_buyFreeCourse() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $cscCourse_model = new Course();
        $advanced_model = new Advanced();
        $advanced_id = $advanced_model->where(['type'=>3,'is_delete'=>0])->value('id');
        $courseInfo = $cscCourse_model->where(['advanced_id'=>$advanced_id,'is_delete'=>0])->find();
        if($courseInfo){
            $courseId = $courseInfo['id'];
            $order_model = new Orders();
            $orderInfo = $order_model->field('status,deadline,addtime')->where(['uid'=>$this->uid,'course_id'=>$courseId])->find();
            if($orderInfo){
                // 判断是否过期
                if($orderInfo['status'] == 2) {
                    $orderInfo['status'] = 2;
                }else{
                    if($orderInfo['addtime'] + 3600 * 24 * $orderInfo['deadline'] > time()) {
                        $orderInfo['status'] = 2;
                    }else{
                        $orderInfo['status'] = 1;
                    }
                }
                return returnjson(1000,$orderInfo,'已购买过');
            }
            return returnjson(1001,'','没有购买过');
        }
        return returnjson(1001,'','该课程已被删除');
    }

    /*
     * 学财商财务自由入口图片
     */
    public function xcsImg() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $system_model = new \app\wxapp\model\System();
        $img = $system_model->value('xcsimg');
        return returnjson(1000,$img,'获取成功');
    }

}

