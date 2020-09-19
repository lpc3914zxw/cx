<?php


namespace app\wxapp\controller;

use app\common\Common;
use app\index\model\Advanced;
use app\index\model\Course;
use app\index\model\Sectiones;
use app\wxapp\controller\Base;
use app\wxapp\model\Colliers;
use app\wxapp\model\CompulsoryCourse;
use app\wxapp\model\CourseLearnLog;
use app\wxapp\model\CreditSource;
use app\wxapp\model\DedicationLog;
use app\wxapp\model\Orders;
use app\wxapp\model\Sign;
use app\wxapp\model\StartLevel;
use app\wxapp\model\Teachers;
use think\helper\Time;
use  think\Db;
/**
 * 学财商模块
 * Class Xcscourse
 * @package app\index\controller
 */
class Xcscourse extends Base
{
    
    /*
     * 财商的觉醒
     * 课程详情
     * @param int $id
     */
    public function courseDetail($id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $xcsCourse = new Course();
        $advanced_model = new Advanced();
        if(!$xcsCourse->where(['id'=>$id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','该课程已被删除');
        }
        $where['id'] = $id;
        $data = $xcsCourse->getApiData($where,$id);
        $data['shareLink'] = $data['shareLink'].$this->uid;
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 财务自由之路
     */
    public function advanceCourse() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $advenced = new Advanced();
        $list = $advenced->where(['type'=>1,'is_delete'=>0])->order('sort asc')->select();
        return returnjson(1000,$list,'获取成功');
    }

    /*
     * 获取下一进阶课程
     * @param int $advance_id 进阶id
     */
    public function getAdvanceCourse($advance_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $advenced = new Advanced();
        if(!$advenced->where(['id'=>$advance_id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','该进阶已被删除');
        }
        $course_model = new Course();
        $section_model = new Sectiones();
        $learnLog_model = new CourseLearnLog();
        $teacher_model = new Teachers();
        $firstAdvanced = $advenced->where(['type'=>1,'is_delete'=>0])->order('sort asc')->find();
        $csAdvanced_id = $advenced->where(['type'=>3,'is_delete'=>0])->value('id');

        if($advance_id != $firstAdvanced['id'] &&  $advance_id != $csAdvanced_id) {  // 进阶一  或者财商的觉醒
            // 验证上一个进阶是否开启并且达到开放条件
            $preAdvenced = $advenced->where(['id'=>['lt',$advance_id],'is_delete'=>0])->order('sort asc')->find();
            $courseList = $course_model->field('id')->where(['advanced_id'=>$preAdvenced['id'],'is_delete'=>0])->select();
            $courseIds = [];
            foreach ($courseList as $val) {
                $courseIds[] = $val['id'];
            }
            $sectionCount = $section_model->where(['c_id'=>['in',$courseIds],'is_delete'=>0])->count();
            $learnCount = $learnLog_model->where(['course_id'=>['in',$courseIds],'uid'=>$this->uid])->count();
//            if($learnCount < $sectionCount) {
//                return returnjson(1001,'','请先完成上一进阶课程');
//            }
            $start_level = $this->userInfo['start_level'];
            if($preAdvenced['open_tj'] != 0 && $preAdvenced['open_tj'] > $start_level) {
                $levelText = $this->getStartLevel($preAdvenced['open_tj']);
                return returnjson(1001,'','该进阶课程'.$levelText."开放");
            }

        }
        $map = ['advanced_id'=>$advance_id,'is_delete'=>0];
        $list = $course_model->getApiAdvancedCourse($map,$this->uid);
        return returnjson(1000,$list,'获取成功');
    }

    public function getStartLevel($level) {
        switch ($level) {
            case 1:
                return '一星以上';
                break;
            case 2:
                return '二星以上';
                break;
            case 3:
                return '三星以上';
                break;
            case 4:
                return '四星以上';
                break;
            default:
                return '';
                break;
        }
    }

    /*
     * 获取课程信息
     * @param string $token
     * @param int $course_id
     */
    public function getCourseInfo($course_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $course_model = new Course();
        if(!$course_model->where(['id'=>$course_id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','该课程已被删除');
        }
        $learnLog_model = new CourseLearnLog();
        $teacher_model = new Teachers();
        $order_model = new Orders();
        $advanced_model = new Advanced();
        if($order_model->where(['uid'=>$this->uid,'course_id'=>$course_id,'status'=>1])->find()) {
            $learnedCount = $learnLog_model->where(['course_id'=>$course_id,'uid'=>$this->uid])->count();
            $courseInfo = $course_model->field('id,name,advanced_id,people_num,teacher_id,deadline,reward,abstract')->where('id',$course_id)->find();
            $teacherInfo = $teacher_model->field('name,headimg')->where('id',$courseInfo['teacher_id'])->find();
            $chapter_count = $advanced_model->where('id',$courseInfo['advanced_id'])->value('chapter_count');
          	$reward = $advanced_model->where('id',$courseInfo['advanced_id'])->value('reward');
            $learnPer =  round($learnedCount / $chapter_count, 2);
            $courseInfo['learnPer'] = $learnPer;
            $courseInfo['teacher_name'] = $teacherInfo['name'];
            $courseInfo['teacher_img'] = $teacherInfo['headimg'];
            $courseInfo['chapter_count'] = $chapter_count;
          	$courseInfo['reward'] = 	$reward;
            $paytime = $order_model->where(['uid'=>$this->uid,'course_id'=>$course_id,'status'=>1])->value('paytime');
            $days = intval((time() - $paytime) / 86400);
            $courseInfo['days'] = ($courseInfo['deadline'] - $days) < 0 ? 0 : $courseInfo['deadline'] - $days;
            $courseInfo['unlock'] = "从".date('Y年m月d日',$paytime)."开始解锁";
            $courseInfo['shareLink'] = GetCurUrl()."/wxapp/Xcscourse/h5_curriculumlist?c_id=".$course_id.'&muid='.$this->uid;
            return returnjson(1000,$courseInfo,'获取成功');
        }
        return returnjson(1001,'','请前去购买该课程');
    }

    /*
     * 签到
     */
    public function doSign($course_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $section_model = new Sectiones();
        $sign_model = new Sign();
        $learnLog = new CourseLearnLog();
        $course_model = new Course();
        $order_model = new \app\wxapp\model\Orders();
        list($start, $end) = Time::today();
        $sectionCount = $section_model->where('c_id',$course_id)->count();
        $courseInfo = $course_model->where('id',$course_id)->find();
        Db::startTrans();
        $orderInfo = $order_model->where(['course_id'=>$course_id,'uid'=>$this->uid,'status'=>1])->find();
        $orderCount = $order_model->where(['course_id'=>$course_id,'uid'=>$this->uid,'status'=>1])->count();
        if($orderCount == 0) {
            $order_id = time().rand(1000,9999);
            $data = [
                'order_id'=>$order_id,
                'course_id'=>$course_id,
                'uid'=>$this->uid,
                'deadline'=>$courseInfo['deadline'],
                'pay_type'=>3,
                'paytime'=>time(),
                'status'=>1,
                'addtime'=>time(),
            ];
            if(!$order_model->insert($data)) {
                Db::rollback();
                return returnjson(1001,'','签到失败');
            }
        }
        $days = intval((date('d',time()) - date('d',$orderInfo['paytime'])) / 86400); // 学习该课程的天数
//        if($days == $courseInfo['deadline'] || $days > $courseInfo['deadline']) {
//            return returnjson('1001','','该课程有效期已过');
//        }
        if($sign_model->where('addtime', 'between', [$start, $end])->find()){
            return returnjson(1001,'','今天已签到，明天再来吧');
        }
        // 判断是第几天签到
        $learnedCount = $learnLog->where(['uid'=>$this->uid,'course_id'=>$course_id])->count(); // 已经解锁的数量
        if($sectionCount > $learnedCount) {  // 签到数量小于章节数量
            $sectionInfo = $section_model->where('c_id',$course_id)->order('id asc')->limit($learnedCount,1)->find();
            $learnData = [
                'section_id' => $sectionInfo['id'],
                'uid' => $this->uid,
                'course_id'=>$course_id,
                'addtime' => time()
            ];
            if(!$learnLog->insert($learnData)) {
                Db::rollback();
                return returnjson(1001,'','签到失败');
            }
        }
        $data = [
            'uid'=>$this->uid,
            'addtime'=>time()
        ];
        if(!$sign_model->insert($data)) {
            Db::rollback();
            return returnjson(1001,'','签到失败');
        }
        Db::commit();
        return returnjson(1000,'','签到成功');
    }

    /*
     *已购课程列表
     * @param int $course_id
     * @param int $page
     */
    public function buyedCourse($course_id = 0,$page = 1) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $section_model = new Sectiones();
        $order_model = new Orders();
        $where = ['c_id'=>$course_id,'is_delete'=>0];
        $orderInfo = $order_model->where(['course_id'=>$course_id,'uid'=>$this->uid,'status'=>1])->find();
        $list = $section_model->getApiSectionList($where,$this->uid);
        return returnjson(1000,$list,'获取成功');
    }
    
    //h5课程详情页面
    public function h5_curriculum(){
        $input = input('get.');
        $xcsCourse = new Course();
        $advanced_model = new Advanced();
        $where['id'] = $input['c_id'];
        $data = $xcsCourse->getApiData($where,$input['c_id']);
        $url =  'https://'.$_SERVER['HTTP_HOST'].'/wxapp/Login/h5_register?p_id='.$input['muid'];
        //$data['sectionList'] = (array)$data['sectionList'];
        $this->assign('url',$url);
      	//var_dump($data);exit;
        $this->assign('data',$data);
        //echo "<pre>";
        //print_r($data['sectionList']);exit;
        return $this->fetch();
    }
    //h5课程列表页面
    public function h5_curriculumlist(){
        $input = input('get.');
        //var_dump($input);exit;
        $course_model = new Course();
        $learnLog_model = new CourseLearnLog();
        $teacher_model = new Teachers();
        
        
        $courseInfo = $course_model->field('id,name,people_num,chapter_count,teacher_id,deadline,reward')->where('id',$input['c_id'])->find();
        $teacherInfo = $teacher_model->field('name,headimg')->where('id',$courseInfo['teacher_id'])->find();
        
        $courseInfo['teacher_name'] = $teacherInfo['name'];
        $courseInfo['teacher_img'] = $teacherInfo['headimg'];
        $section_model = new Sectiones();
        
        $where = ['c_id'=>$input['c_id'],'is_delete'=>0];
//        $start = ($page - 1) * $this->num;
//        $limit = $start . ',' . $this->num;
        //$orderInfo = $order_model->where(['course_id'=>$course_id,'uid'=>$this->uid,'status'=>1])->find();
        //if($orderInfo['pay_type'] == 1 || $orderInfo['pay_type'] == 2) {  // 如果是学分购买或者现金购买，再此需要解锁课程
        
            //$this->unlockCourse($course_id,$this->uid);
        //}
        $list = $section_model->getApiSectionList($where);
        $this->assign('courseInfo',$courseInfo);
        $this->assign('list',$list);
        $this->assign('muid',$input['muid']);
        $url =  'https://'.$_SERVER['HTTP_HOST'].'/wxapp/Login/h5_register?p_id='.$input['muid'];
        $this->assign('url',$url);
        return $this->fetch();
    }
    //h5文章详情页面
    public function h5_articel(){
        $input = input('get.');
        return $this->fetch();
    }

    /*
     * 算力银行
     * @return false|mixed|string|\type
     */
    public function colliersBank() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $colliers = new Colliers();
        $colliers_note = $this->systeminfo['colliers_note'];
        return $colliers->getList($this->uid,$colliers_note);
    }

    /*
     * 领取学分
     */
    public function getScore() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $colliers = new Colliers();
        list($start, $end) = Time::today();
        $dedicationLog = new DedicationLog();
        $creditSource = new CreditSource();
        if($creditSource->where(['uid'=>$this->uid,'type'=>6,'addtime'=>['between',[$start,$end]]])->find()) {
            return returnjson(1001,'','今日已领取');
        }
        $data = $colliers->field('id')->where(['p_id'=>0])->order('id')->limit(5)->select();
        $totalValue = 0;
        foreach ($data as $k=>$val) {
            $children = $colliers->field('id,contribution,max')->where('p_id', $val['id'])->select();
            $cids = [];
            $strCids = '';
            if ($children) {
                foreach ($children as $value) {
                    $cids[] = $value['id'];
                }
            } else {
                $cids[] = $val['id'];
            }
            $strCids = implode(',', $cids);
            $totalValue += $dedicationLog->where(['uid'=>$this->uid,'type'=>['in',($cids)],'addtime'=>['between',[$start,$end]]])->sum('value');
        }
        if(floatval($totalValue) > 45) {
            $getScore = 1;
        }else if(floatval($totalValue) > 88) {
            $getScore = 3;
        }else if(floatval($totalValue) > 138) {
            $getScore = 7;
        }else{
            $getScore = 0;
        }
        if(floatval($totalValue) > 0) {
            $data =[
                'type'=>6,'uid'=>$this->uid,'pay_type'=>0,'score'=>"+".$getScore,
                'status'=>1,'note'=>'完成算力银行任务领取','value'=>'','addtime'=>time()
            ];
            $common = new Common();
            if(false === $common->creditSource($data,$this->uid)){
                return returnjson(1001,'','领取失败');
            }
            return returnjson(1001,'','领取成功');
        }
        return returnjson(1001,'','未能领取');
    }
}