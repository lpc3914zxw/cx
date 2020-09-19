<?php


namespace app\wxapp\controller;

use app\index\model\Advanced;
use app\index\model\Course;
use app\index\model\LearningPowerLog;
use app\index\model\Sectiones;
use app\index\model\TaskOptions;
use app\index\model\Tasks;
use app\wxapp\controller\Base;
use app\wxapp\model\CourseBehavior;
use app\wxapp\model\CourseLearnLog;
use app\wxapp\model\CreditSource;
use app\wxapp\model\Orders;
use app\wxapp\model\PulsLearnPowerLog;
use app\wxapp\model\StartLevel;
use app\wxapp\model\TaskResult;
use app\wxapp\model\Teachers;
use think\Db;
use app\common\Common;
/**
 * 章节控制器
 * Class Xcscourse
 * @package app\index\controller
 */
class Section extends Base
{
    /*
    * 获取课程课时
    * 获取课程课时
    */
    public function getSectionList($c_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $section_model = new Sectiones();
        $data = $section_model->field('name')->where(['is_delete'=>0,'c_id'=>$c_id])->order('id')->limit(0,8)->select();
        return returnjson(1000,$data,'获取成功');
    }


    /*
     * 获取章节信息
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
        if(!$section_model->where(['is_delete'=>0,'id'=>$s_id])->find()) {
            return returnjson(1001,'','该课时已被删除');
        }
        $course_model = new Course();
        $teacher_model = new Teachers();
        $learnLog = new CourseLearnLog();
        $order_model = new \app\wxapp\model\Orders();
        $adver_model = new Advanced();
        $data = $section_model->where('id',$s_id)->find();
        $courseInfo = $course_model->field('teacher_id,advanced_id,id')->where('id',$data['c_id'])->find();
        $course_name = $course_model->where('id',$data['c_id'])->value('name');
        $teacherImg = $teacher_model->where('id',$courseInfo['teacher_id'])->value('headimg');
        $data['image'] = $teacherImg;
        $preSection = $section_model->field('id')->where(['id'=>['lt',$s_id],'c_id'=>$data['c_id']])->order('id desc')->find();
        $nextSection = $section_model->field('id')->where(['id'=>['gt',$s_id],'c_id'=>$data['c_id']])->order('id')->find();

        $paytime = $order_model->where(['uid'=>$this->uid,'course_id'=>$courseInfo['id'],'status'=>1])->value('paytime');
        $days = intval((time() - $paytime) / 86400);
        $deadline = $adver_model->where('id',$courseInfo['advanced_id'])->value('deadline');
        $syDays = $deadline - $days;
        if($syDays < 1) {  // 超过有效期，解锁
            // 判断是否已经解锁
            if(!$learnLog->where(['uid'=>$this->uid,'section_id'=>$s_id])->find()) {
                $data = ['section_id'=>$s_id,'uid'=>$this->uid,'course_id'=>$data['c_id'],'addtime'=>time()];
                $learnLog->insert($data);
            }
        }

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
      $learn_ = $learnLog->where(['uid'=>$this->uid,'section_id'=>$s_id])->find();
        if($learn_) {
          	if($learn_['unlocktime']>time()){
            	$data['lock'] = 1;
            	$data['lock_msg'] = '未解锁';
            }else{
            	$data['lock'] = 0;
            	$data['lock_msg'] = '已解锁';
            }
            
        }else{
            $data['lock'] = 1;
            $data['lock_msg'] = '未解锁';
        }
        // 是否收藏点赞
        $behavior = new CourseBehavior();
        if($behavior->where(['type'=>1,'uid'=>$this->uid,'s_id'=>$s_id])->find()){
            $data['is_like'] = 1;
        }else{
            $data['is_like'] = 0;
        }
        if($behavior->where(['type'=>2,'uid'=>$this->uid,'s_id'=>$s_id])->find()){
            $data['is_collection'] = 1;
        }else{
            $data['is_collection'] = 0;
        }
        $data['course_img'] = $teacherImg;
        $data['course_name'] = empty($course_name)?'':$course_name;
        $data['addtime'] =  date('Y-m-d',$data['addtime']);
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 阅读课时
     */
    public function readSectionDetail($s_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        // 获取贡献值
        $common_model = new Common();
        $res = $common_model->dedicationLog($this->uid,14,$s_id,'阅读课程');

        Db::startTrans();
        $msg = '';
        if(false !== $res) {
            if($res === 0) {
                $msg = "阅读课程奖励已达上线";
            }else{
                $msg = '阅读课程获得'.$res.'贡献值';
            }
        }
        Db::commit();
        return returnjson('1000','',$msg);
    }

    /*
     * 下一课
     */
    public function nextSection($s_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $section_model = new Sectiones();
        $course_id = $section_model->where('id',$s_id)->value('c_id');
        $nextSection = $section_model->field('id')->where(['id'=>['gt',$s_id],'c_id'=>$course_id,'is_delete'=>0])->order('id')->find();
        if(empty($nextSection)) {
            return returnjson(1001,'','已经是最后一课了');
        }
        return returnjson(1000,$nextSection['id'],'获取成功');
    }

    /*
     * 上一节课
     */
    public function preSection($s_id = 0) {
        $section_model = new Sectiones();
        $course_id = $section_model->where('id',$s_id)->value('c_id');
        $nextSection = $section_model->field('id')->where(['id'=>['lt',$s_id],'is_delete'=>0,'c_id'=>$course_id])->order('id desc')->find();
        if(empty($nextSection)) {
            return returnjson(1001,'','已经是第一课了');
        }
        return returnjson(1000,$nextSection['id'],'获取成功');
    }

    /*
     * 点赞/ 取消点赞
     */
    public function doLike($s_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $section_model = new Sectiones();
        if(!$section_model->where(['id'=>$s_id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','该课程已删除');
        }
        $behavior = new CourseBehavior();
        if($behavior->where(['uid'=>$this->uid,'s_id'=>$s_id,'type'=>1])->find()){
            $behavior->where(['uid'=>$this->uid,'s_id'=>$s_id,'type'=>1])->delete();
            $section_model->where('id',$s_id)->setDec('like_num',1);
            $like_num = $section_model->where('id',$s_id)->value('like_num');
            $data = ['like_num'=>$like_num];
            $data = 0;
            return returnjson(1000,$data,'已取消点赞');
        }else{
            $data = [
                'uid'=>$this->uid,
                'type'=>1,
                's_id'=>$s_id,
                'addtime'=>time()
            ];
            $behavior->insert($data);
            $section_model->where('id',$s_id)->setInc('like_num',1);
            $like_num = $section_model->where('id',$s_id)->value('like_num');
            $data = ['like_num'=>$like_num];
            // 获取贡献值
            $common_model = new Common();
            $res = $common_model->dedicationLog($this->uid,15,$s_id,'课程点赞');
            if(false !== $res) {
                if($res == 0) {
                    $msg = '点赞奖励已达上限';
                }else{
                    $msg = '点赞获得'.$res.'贡献';
                }
                $data = 1;
                return returnjson('1000',$data,$msg);
            }
            return returnjson(1000,$data,'点赞成功');
        }
    }

    /*
     * 收藏/取消收藏
     */
    public function doCollection($s_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $section_model = new Sectiones();
        if(!$section_model->where(['id'=>$s_id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','该课程已删除');
        }
        $behavior = new CourseBehavior();
        if($behavior->where(['uid'=>$this->uid,'s_id'=>$s_id,'type'=>2])->find()){
            $behavior->where(['uid'=>$this->uid,'s_id'=>$s_id,'type'=>2])->delete();
            $section_model->where('id',$s_id)->setDec('collection_num',1);
            $collect_num= $section_model->where('id',$s_id)->value('collection_num');
            $data = ['collection_num'=>$collect_num];
            $data = 0;
            return returnjson(1000,$data,'已取消收藏');
        }else{
            $data = [
                'uid'=>$this->uid,
                'type'=>2,
                's_id'=>$s_id,
                'addtime'=>time()
            ];
            $behavior->insert($data);
            $section_model->where('id',$s_id)->setInc('collection_num',1);
            $collect_num= $section_model->where('id',$s_id)->value('collection_num');
            $data = ['collection_num'=>$collect_num];
            
            $common_model = new Common();
            $res = $common_model->dedicationLog($this->uid,13,$s_id,'课程收藏');
            $data = 1;
            if(false !== $res) {
                
                if($res == 0) {
                    $msg = '收藏奖励已达上限';
                }else{
                    $msg = '收藏课程获得'.$res.'贡献';
                }
            }
            return returnjson(1000,$data,$msg);
        }
    }

    /*
     * 播放音频完整页面
     */
    public function getSectionAudio($s_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆');
        }
        $section_model = new Sectiones();
        if(!$section_model->where(['id'=>$s_id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','该课时已删除');
        }
        $xcsCourse = new Course();
        $teacher_model = new Teachers();
        $data = $section_model->field('c_id,audiourl,name,is_delete')->where('id',$s_id)->find();
        if($data['is_delete'] == 1) {
            return returnjson(1000,'','该课程已下架');
        }
        $courseInfo = $xcsCourse->field('teacher_id,addtime,name')->where('id',$data['c_id'])->find();
        $teacherImg = $teacher_model->where('id',$courseInfo['teacher_id'])->value('headimg');
        $data['addtime'] = date('Y-m-d',$courseInfo['addtime']);
        $data['course_name'] = $courseInfo['name'];
        $data['teacher_img'] = $teacherImg;

        // 课程列表信息
        $list = $section_model->field('name,id,audiotime,audiourl,sort')->where(['c_id'=>$data['c_id'],'is_delete'=>0])->order('sort')->select();
        foreach ($list as $k=>$val) {
            $list[$k]['name'] = $val['sort'].'、'.$val['name'];
        }
        $sectionCount = $section_model->where(['c_id'=>$data['c_id'],'is_delete'=>0])->count();
        $data = ['sectionInfo'=>$data,'count'=>$sectionCount,'list'=>$list];
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 获取本课时所在课程的作业
     */
    public function getSectionTask($s_id = 0) {
        $task_model = new Tasks();
        $option_model = new TaskOptions();
        $section_model = new Sectiones();
        $xcsCourse = new Course();
      $advanced_model = new Advanced();
        $taskInfo = $task_model->where('section_id',$s_id)->find();
        if($task_model->where('section_id',$s_id)->count() == 0) {
            return returnjson(1001,'','该课程没有作业');
        }
        //  所得学分计算
        $c_id = $section_model->where('id',$s_id)->value('c_id');
        $courseInfo = $xcsCourse->field('reward,chapter_count,advanced_id')->where('id',$c_id)->find();
        $advanced = $advanced_model->where('id',$courseInfo['advanced_id'])->find();
        
        $getScore = number_format($advanced['reward'] / $advanced['deadline'],4);
        $optionlist = $option_model->where('task_id',$taskInfo['id'])->select();
        $sectionName = $section_model->where('id',$s_id)->value('name');
      	$task = Db::name('task')->where(['section_id'=>$s_id])->find();
      	$operationComplete = 1;
         if(!empty($task)){
            $task_result = Db::name('task_result')->where(['uid'=>$this->uid,'task_id'=>$task['id']])->find();
              	if($task_result){
                	$operationComplete = 2;
                }
         }
        $data = [
            'sectionName'=>$sectionName,
            'taskinfo'=>$taskInfo,
            'getScore'=>$getScore,
            'optionlist'=>$optionlist,
          	'operationComplete'=> $operationComplete
        ];
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 提交作业
     * @param int $task_id 作业id
     * @param string $optionids 选项 用逗号隔开的字符串
     */
    public function submitTask ($task_id = 0,$optionids = '') {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $optionids = trim($optionids,',');
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $option_model = new TaskOptions();
        $advanced_model = new Advanced();
        $section_model = new Sectiones();
        $task_model = new Tasks();
        $xcsCourse = new Course();
        $order_model = new \app\wxapp\model\Orders();
        $taskResult = new TaskResult();
        $learnLog = new CourseLearnLog();
        $user_model = new \app\wxapp\model\User();
        // 判断是否提交过作业
     // if($token!='606b934c883f065debbbd54c338f0767'){
      	if($taskResult->where(['uid'=>$this->uid,'task_id'=>$task_id])->find()) {
            return returnjson(1001,'','你已提交过作业，请勿重复提交');
        }
      //}
        
        $section_id = $task_model->where('id',$task_id)->value('section_id');
        if(!$section_model->where(['id'=>$section_id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','该课时已删除');
        }
      	//查看课程是否已解锁
      
      $ishas = $learnLog->where(['section_id'=>$section_id,'uid'=>$this->uid])->find();
          if($ishas){
            if($ishas['unlocktime']>time()){
                return returnjson(1001,'','作业未解锁');
            }
            
          }
        $c_id = $section_model->where('id',$section_id)->value('c_id');
        // 判断是否已经解锁  // 查看
        $preSection = $section_model->field('id')->where(['id'=>['lt',$section_id],'c_id'=>$c_id])->order('sort asc')->find();
        if($preSection) {
            // 查看上一节课是否完成作业
            $preLearnlog = $learnLog->where(['section_id'=>$preSection['id'],'uid'=>$this->uid])->find();
            if($preLearnlog['status'] == 0) {
                return returnjson(1001,'','请先完成上一节课时作业');
            }
        }
      
        $optionList = $option_model->where(['task_id'=>$task_id,'id'=>['in',[$optionids]]])->select();
      $optionList_ture = $option_model->where(['task_id'=>$task_id,'is_true'=>1])->select();
      	
        foreach ($optionList as $val) {
            if($val['is_true'] == 0) {
                return returnjson(1001,'','答案错误');
            }
          
          
        }
      
      //if($token=='606b934c883f065debbbd54c338f0767'){
        if(empty($optionList)){
        	return returnjson(1001,'','答案错误!');
        }
        $a = explode(',',$optionids);
        
        $count1 = count($a);
        $truecount = count($optionList_ture);
        
        if(!empty(input('version'))||!empty(input('isiOS'))){
        	if(input('version') >= '1.0.6'||!empty(input('isiOS'))){
            	if($count1!=$truecount){
                     return returnjson(1001,'','答案错误!');
                }
            }
        }
      	//exit;
     // }
        $advanced_id = $xcsCourse->where('id',$c_id)->value('advanced_id');
        $advancedInfo = $advanced_model->field('deadline,learn_power,reward,chapter_count,difficulty')->where('id',$advanced_id)->find();
        // 判断是否已过期 过期则不给予学分
        $paytime = $order_model->where(['uid'=>$this->uid,'course_id'=>$c_id,'status'=>1])->value('paytime');
        //$days = intval((date('d',time()) - date('d',$paytime)) / 86400); // 学习该课程的天数
        $days = intval((time() - $paytime) / 86400); // 学习该课程的天数
        // 提交答案
        $data = ['uid'=>$this->uid,'task_id'=>$task_id,'addtime'=>time()];
        Db::startTrans();
        if(!$taskResult->insert($data)) {
            Db::rollback();
            return returnjson(1001,'','服务器繁忙');
        }
        if(false === $learnLog->where(['section_id'=>$section_id,'uid'=>$this->uid])->update(['status'=>1])) {
            Db::rollback();
            return returnjson(1001,'','服务器繁忙');
        }
        $learnNum = $learnLog->where(['course_id'=>$c_id,'status'=>1,'uid'=>$this->uid])->count();
        $chapter_count = $advanced_model->where('id',$advanced_id)->value('chapter_count');
        if($learnNum == $chapter_count) {
            if(false === $order_model->where(['uid'=>$this->uid,'course_id'=>$c_id,'status'=>['in',[1,2]]])->update(['status'=>3])) {
                Db::rollback();
                return returnjson(1001,'','服务器繁忙');
            }
        }
        $common = new Common();
        $courseName = $xcsCourse->where('id',$c_id)->value('name');
        $sectionName = $section_model->where('id',$section_id)->value('name');
        if($days < $advancedInfo['deadline'] || $days == $advancedInfo['deadline']) {  // 未过期 并且 如果当前课程是最后一个课时可获得学习力
            $sectionIds = [];
            $sectionList = $section_model->field('id')->where('c_id',$c_id)->select();
            foreach ($sectionList as $val) {
                $sectionIds[] = $val['id'];
            }
            //来自自己学习的课程 学分计算  学分 = 课时（1/2...节课）x 难度系数 x 基础学习力
            $getScore = number_format($advancedInfo['learn_power']  * $advancedInfo['difficulty'],4);
            if($getScore > 0) {
                $data =[
                    'type'=>1,'uid'=>$this->uid,'pay_type'=>3,'score'=>$getScore,
                    'status'=>1,'note'=>"《".$courseName.'-'.$sectionName.'》—学习课程','value'=>$c_id,'addtime'=>time()
                ];
                file_put_contents('section.txt',"1、".$getScore,FILE_APPEND);
                if(false === $common->creditSource($data,$this->uid)) {
                    Db::rollback();
                    return returnjson(1001,'服务器繁忙','服务器繁忙');
                }
              if(!Db::name('user')->where('id',$this->uid)->setInc('score',$getScore)) {
                    Db::rollback();
                    return returnjson(1001,'服务器繁忙','服务器繁忙');
                }
              
                   
                // 加成荣誉值
//                $content = '学习课程《'.$sectionName.'》获得'.$getScore .'加成荣誉值';
//                if(false === $common->honorLog($this->uid,2,$getScore,$section_id,$content)){
//                    Db::rollback();
//                    return returnjson(1001,'系统错误');
//                }
            }
            $pid = $user_model->where('id',$this->uid)->value('pid');
            if($pid != 0&&1==2) {
                // 来自直推好友的加成：学分 = 课时（1/2...节课）x 难度系数（固定为0.33） x 加成学习力
                $addPower = number_format($advancedInfo['learn_power'] * 0.05 * 0.33,4);
                if($addPower > 0) {
                    $data =[
                        'type'=>1,'uid'=>$pid,'pay_type'=>3,'score'=>$addPower,
                        'status'=>1,'note'=>"《".$courseName.'-'.$sectionName.'》—直推好友加成','value'=>$c_id,'addtime'=>time()
                    ];
                    file_put_contents('section.txt',"2、".$addPower,FILE_APPEND);
                    if(false === $common->creditSource($data,$this->uid)) {
                        Db::rollback();
                        return returnjson(1001,'服务器繁忙','服务器繁忙');
                    }
                  	if(!Db::name('user')->where('id',$pid)->setInc('score',$addPower)) {
                        Db::rollback();
                        return returnjson(1001,'服务器繁忙','服务器繁忙');
                    }
                    // 加成荣誉值
//                $content = '学习课程《'.$sectionName.'》获得'.$addPower .'加成荣誉值';
//                if(false === $common->honorLog($pid,2,$addPower,$section_id,$content)){
//                    Db::rollback();
//                    return returnjson(1001,'系统错误');
//                }
                }
            }

        }else{
            // 学分
            $getScore = number_format($advancedInfo['difficulty'] * $advancedInfo['learn_power'],4);
            if($getScore > 0) {
                $data =[
                    'type'=>1,'uid'=>$this->uid,'pay_type'=>3,'score'=>$getScore,
                    'status'=>1,'note'=>"《".$courseName.''.$sectionName.'》—学习收入','value'=>$c_id,'addtime'=>time()
                ];
                file_put_contents('section.txt',"3、".$getScore,FILE_APPEND);
                if(false === $common->creditSource($data,$this->uid)) {
                    Db::rollback();
                    return returnjson(1001,'服务器繁忙','服务器繁忙');
                }
                // 荣誉值
//                if(false === $common->honorLog($this->uid,2,$getScore,$section_id)){
//                    Db::rollback();
//                    return returnjson(1001,'系统错误');
//                }
            }
        }
        $this->unlockCourse($c_id,$this->uid);

        // 分发贡献值
        if(false === $this->plusLearnPower($this->uid)) {
            Db::rollback();
            return returnjson(1001,'服务器繁忙','服务器繁忙');
        }
        Db::commit();
        return returnjson(1000,'','恭喜你完成本节课时');
    }


    public function plusLearnPower($uid = 0) {
        $common = new Common();
        $learnPowerLog = new LearningPowerLog();
        $pulsLearnPower = new PulsLearnPowerLog();
        $user_model = new \app\wxapp\model\User();
        $data = $pulsLearnPower->field('id,day,issued,credit,cid')->where(['uid'=>$uid,'status'=>0])->select();
      	$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
 		$endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
      	$where['lasttime'] = ['between',$beginToday.','.$endToday];
      $where['uid'] = $uid;
      	$today = $pulsLearnPower->where($where)->find();
        
      	if($today){
        	 return true;
        }
        if(count($data) > 0) {
            foreach ($data as $val) {
                $value = number_format(floatval($val['credit']) / $val['day'],4);
                $content = '加成学习力';
              
                $data = [
                    'uid'=>$uid,'type'=>1,'score'=>$value,'status'=>1,
                    'note'=>$content,'addtime'=>time(),'value'=>$val['cid'],'pay_type'=>3
                ];
              
              /*
              $data =[
                    'type'=>1,'uid'=>$this->uid,'pay_type'=>3,'score'=>$getScore,
                    'status'=>1,'note'=>"《".$courseName.'-'.$sectionName.'》—学习课程','addtime'=>time()
                ];
                file_put_contents('section.txt',"1、".$getScore,FILE_APPEND);
                */
              
                if(floatval($value) > 0) {
                  if(false === $common->creditSource($data,$uid)) {
                    Db::rollback();
                    return returnjson(1001,'服务器繁忙','服务器繁忙');
                  }
                  
                 if(!Db::name('user')->where('id',$uid)->setInc('score',$value)) {
                      Db::rollback();
                      return returnjson(1001,'服务器繁忙','服务器繁忙');
                  }
                  /*
                    if(!$learnPowerLog->insert($data)) {
                        Db::rollback();
                        return false;
                    }
                    if(false === $user_model->where('id',$uid)->setInc('learning_power',$value)) {
                        Db::rollback();
                        return false;
                    }*/
                   //if(false === $common->creditSource($data,$uid)) {
                   	//    Db::rollback();
                    //	return false;
                	//}
                }
              
                if($val['issued'] + 1 == $val['day']) {
                    $updateData = ['finishtime'=>time(),'status'=>1];
                }else{
                    $updateData = ['lasttime'=>time()];
                }
              
                if(false === $pulsLearnPower->where('id',$val['id'])->update($updateData)) {
                    Db::rollback();
                    return false;
                }
              
                if(false === $pulsLearnPower->where('id',$val['id'])->setInc('issued')) {
                    Db::rollback();
                    return false;
                }
            }
        }
        return true;
    }

    /*
    * 进入已购课程列表页面开始解锁操作
    */
    public function unlockCourse($course_id = 0,$uid = 0) {
      	//if(empty($this->uid)){
        //	$this->uid = $uid;
       // }
      	$user_model = new \app\wxapp\model\User();
        $startLevel = new StartLevel();
        $order_model = new \app\wxapp\model\Orders();
        $course_model = new Course();
        $section_model = new Sectiones();
        $learnLog = new CourseLearnLog();
        $courseInfo = $course_model->where('id',$course_id)->find();
        $orderCount = $order_model->where(['course_id'=>$course_id,'uid'=>$this->uid,'status'=>1])->count();
		
        if($orderCount > 0) {
          //判断有没有未完成的课。如果有不解锁课程，如果没有进行解锁
          $ishas = $learnLog->where(['course_id'=>$course_id,'uid'=>$this->uid,'status'=>0])->find();
          
          if($ishas){
            return;
          }
          
          //明天开始时间戳
          	$tomorrow_start_time = mktime(0,0,0,date('m'),date('d'),date('Y'))+86400;
            $paytime = $order_model->where(['uid'=>$uid,'course_id'=>$course_id,'status'=>1])->value('paytime');
            //$days = ceil((time() - $paytime)/ 86400) ; // 学习该课程的天数
			
            $start_level = $user_model->where('id',$uid)->value('start_level');
          $learnedCount = $learnLog->where(['uid'=>$uid,'course_id'=>$course_id])->count(); // 已经解锁的数量
            //if($start_level == 0) {
                // 会员等级 则一天解锁一节课
                $unlockCourseCount = $learnedCount + 1;
            //}else{
                // 学习周期加速比例 % 计算1天解锁的章节数
              //  $learn_accelerate = $startLevel->where('value',$start_level)->value('learn_accelerate');
               // $unlockCourseCount = floor(($days + 1 ) * $learn_accelerate / 100);
            //}
          //file_put_contents($documentRoot.'/log_han2.txt',print_r($unlockCourseCount,true),FILE_APPEND);
            // 查看今天是否已经解锁过
            
            $sectionCount = $section_model->where(['c_id'=>$course_id,'is_delete'=>0])->count();
            $todayUnlockCount = $unlockCourseCount - $learnedCount;  // 今天解锁的章节数量
           //echo $sectionCount;echo '------';echo $learnedCount;exit;
            if($sectionCount > $learnedCount) {  // 签到数量小于章节数量
                //  如果当天要解锁的数量大于剩余未解锁的数量则解锁剩余的数量
              
                $noUnlock = $sectionCount - $learnedCount;  
                $unlockNum= 0;
                if($noUnlock < $todayUnlockCount) {
                    $unlockNum = $noUnlock;
                }else{
                    $unlockNum = $todayUnlockCount;
                }
              //file_put_contents($documentRoot.'/log_han3.txt',print_r('解锁章节--》'.$unlockNum.'已解锁---->'.$learnedCount.'课程数-----》'.$sectionCount.'今天解锁章节-------->'.$todayUnlockCount,true),FILE_APPEND);
                if($learnedCount>$unlockNum){
                    //return;
                }
              	//$unlockNum = $learnedCount+1;
                $sectionList = $section_model->where(['c_id'=>$course_id,'is_delete'=>0])->order('sort asc')->limit($learnedCount,$unlockNum)->select();
                $learnData = [];
                
                if($sectionList) {
                    foreach ($sectionList as $k=>$val) {
                        $learnData['section_id'] = $val['id'];
                        $learnData['uid'] = $uid;
                        $learnData['course_id'] = $course_id;
                        $learnData['addtime'] = time();
                        $learnData['unlocktime'] = $tomorrow_start_time;
                        $learnLog->insert($learnData);
                    }
                }
            }
        }
        return true;
    }
}
