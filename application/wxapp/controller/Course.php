<?php
namespace app\wxapp\controller;
use app\index\model\Advanced;
use app\index\model\Sectiones;
use app\wxapp\controller\Base;
use app\index\model\Advertisment;
use app\wxapp\model\CourseBehavior;
use app\wxapp\model\CourseLearnLog;
use app\wxapp\model\Teachers;

class Course extends Base{

    /*
     * 才学堂首页
     * @return false|mixed|string|\type
     */
    public function index() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        // 轮播图
        $adver_model = new Advertisment();
        $adverData = $adver_model->field('imgurl,id,link,idvalue,type')->select();
        // 分类
        $advanced_model = new Advanced();
        $advancedData = $advanced_model->field('id,name,imgurl')->where(['type'=>2,'is_delete'=>0])->order('sort')->limit(4)->select();

        // 推荐课程
        $course_model = new \app\index\model\Course();
        $section_model = new Sectiones();
        $teacher_model = new \app\index\model\Teacher();
        $tjCourseData = $course_model->field('id,teacher_id,name')->where('is_tj',1)->where('is_delete',0)->limit(3)->select();
        if($tjCourseData) {
            foreach ($tjCourseData as $k=>$val) {
                $sectionList = $section_model->field('name')->where('c_id',$val['id'])->limit(2)->select();
                $tjCourseData[$k]['sectionlist'] = $sectionList;
                $teacherImg = $teacher_model->where('id',$val['teacher_id'])->value('headimg');
                $tjCourseData[$k]['teacherimg'] = $teacherImg;
            }
        }else{
            $tjCourseData = [];
        }
        // 每次一课
        $dayCourse = $course_model->field('id,advanced_id,name,abstract,samll_imgurl,people_num')->where(['id'=>99])->find();
        if($dayCourse) {
            $advancedInfo = $advanced_model->field('chapter_count,pay_type,value')->where(['id'=>$dayCourse['advanced_id'],'is_delete'=>0])->find();
            $payTypes = explode(',',$advancedInfo['pay_type']);
            if(in_array(2,$payTypes)) {
                $dayCourse['pay_type'] = 2;
            }else{
                $dayCourse['pay_type'] = 1;
            }
            $dayCourse['chapter_count'] = $advancedInfo['chapter_count'];
            $dayCourse['value'] = $advancedInfo['value'];
        }else{
            $dayCourse = (object)array();
        }

        // 各分类课程 取四条数据
        $advancedList = $advanced_model->field('id,name,pay_type,value,chapter_count')->where(['type'=>2,'is_delete'=>0])->select();
        foreach ($advancedList as $k=>$val) {
            $payTypes = explode(',',$val['pay_type']);
            if(in_array(2,$payTypes)) {
                $pay_type = 2;
            }else{
                $pay_type = 1;
            }
            $courseList = $course_model->field('name,abstract,samll_imgurl,people_num')
                        ->where('advanced_id',$val['id'])->order('sort')->limit(4)->select();
            foreach ($courseList as $key=>$value) {
                $courseList[$key]['pay_type'] = $pay_type;
                $courseList[$key]['value'] = $val['value'];
                $courseList[$key]['chapter_count'] = $val['chapter_count'];
            }
            unset($advancedList[$k]['pay_type']);
            unset($advancedList[$k]['value']);
            unset($advancedList[$k]['chapter_count']);
            $advancedList[$k]['courselist'] = $courseList;
        }
        $data = ['adverData'=>$adverData,'advancedData'=>$advancedData,'tjCourseData'=>$tjCourseData,'dayCourse'=>$dayCourse,'advancedList'=>$advancedList];
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 课程分类
     */
    public function courseCate() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $advenced = new Advanced();
        $list = $advenced->field('id,name')->where(['type'=>2,'is_delete'=>0])->order('sort asc')->select();
        return returnjson(1000,$list,'获取成功');
    }

    /*
     * 获取分类下的课程
     * @param int $cate_id 分类id
     */
    public function getCourse($cate_id = 0) {
        $course_model = new \app\index\model\Course();
        $advenced = new Advanced();
        if(!$advenced->where(['id'=>$cate_id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','该分类课程已被删除');
        }
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $map = ['advanced_id'=>$cate_id];
        $list = $course_model->getCourseList($map,$this->uid);
        return returnjson(1000,$list,'获取成功');
    }

    /*
     * 推荐课程-已购页面
     */
    public function getCourseInfo($course_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $course_model = new \app\index\model\Course();
        if(!$course_model->where(['id'=>$course_id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','该课程已被删除');
        }
        $learnLog_model = new CourseLearnLog();
        $teacher_model = new Teachers();
        $order_model = new \app\wxapp\model\Orders();
        $advanced_model = new Advanced();
        if($order_model->where(['uid'=>$this->uid,'course_id'=>$course_id,'status'=>1])->find()) {
            $learnedCount = $learnLog_model->where(['course_id'=>$course_id,'uid'=>$this->uid])->count();
            $courseInfo = $course_model->field('id,name,people_num,advanced_id,teacher_id')->where('id',$course_id)->find();
            $teacherInfo = $teacher_model->field('name,headimg')->where('id',$courseInfo['teacher_id'])->find();
            $chapter_count = $advanced_model->where('id',$courseInfo['advanced_id'])->value('chapter_count');
            $learnPer =  round($learnedCount / $chapter_count, 2);
            $courseInfo['learnPer'] = $learnPer;
            $courseInfo['chapter_count'] = $chapter_count;
            $courseInfo['teacher_name'] = $teacherInfo['name'];
            $courseInfo['teacher_img'] = $teacherInfo['headimg'];
            $paytime = $order_model->where(['uid'=>$this->uid,'course_id'=>$course_id,'status'=>1])->value('paytime');
            $courseInfo['shareLink'] = GetCurUrl()."/wxapp/Xcscourse/h5_curriculumlist?c_id=".$course_id.'&muid='.$this->uid;
            return returnjson(1000,$courseInfo,'获取成功');
        }
        return returnjson(1001,'','请前去购买该课程');
    }

    /*
     *课程-章节列表
     * @param int $course_id
     * @param int $page
     */
    public function sectionList($course_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $order_model = new \app\wxapp\model\Orders();
        if(!$order_model->where(['course_id'=>$course_id,'uid'=>$this->uid,'status'=>1])->find()) {
            return returnjson(1001,'','请先购买该课程');
        }
        $section_model = new Sectiones();
        $where = ['c_id'=>$course_id,'is_delete'=>0];
        $list = $section_model->getCourseSectionList($where,$this->uid);
        return returnjson(1000,$list,'获取成功');
    }


    /*
     * 课时详情 s_id 课时id
     */
    public function getSectionDetail($s_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $section_model = new Sectiones();
        $course_model = new \app\index\model\Course();
        $teacher_model = new Teachers();
        if(!$section_model->where(['is_delete'=>0,'id'=>$s_id])->find()) {
            return returnjson(1001,'','该课时已被删除');
        }
        $data = $section_model->field('id,c_id,audiourl,audiotime,audiosize,name,content,addtime,like_num,collection_num')->where('id',$s_id)->find();
        $courseInfo = $course_model->field('teacher_id,advanced_id,id')->where('id',$data['c_id'])->find();
        $course_name = $course_model->where('id',$data['c_id'])->value('name');
        $teacherImg = $teacher_model->where('id',$courseInfo['teacher_id'])->value('headimg');
        $data['image'] = $teacherImg;
        $preSection = $section_model->field('id')->where(['id'=>['lt',$s_id],'c_id'=>$data['c_id']])->order('id desc')->find();
        $nextSection = $section_model->field('id')->where(['id'=>['gt',$s_id],'c_id'=>$data['c_id']])->order('id')->find();

        if($preSection) {
            $data['preSectionId'] = $preSection['id'];
        }else{
            $data['preSectionId'] = 0;
        }
        if($nextSection) {
            $data['nextSectionId'] = $nextSection['id'];
        }else{
            $data['nextSectionId'] = 0;
        }
        // 是否收藏点赞
        $behavior = new CourseBehavior();
        if($behavior->where(['type'=>1,'uid'=>$this->uid])->find()){
            $data['is_like'] = 1;
        }else{
            $data['is_like'] = 0;
        }
        if($behavior->where(['type'=>2,'uid'=>$this->uid])->find()){
            $data['is_collection'] = 1;
        }else{
            $data['is_collection'] = 0;
        }
        $data['course_img'] = $teacherImg;
        $data['course_name'] = empty($course_name)?'':$course_name;
        $data['addtime'] =  date('Y-m-d',$data['addtime']);
        return returnjson(1000,$data,'获取成功');
    }

}

