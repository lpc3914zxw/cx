<?php

namespace app\common;
use app\index\model\Advanced;
use app\index\model\HonorLog;
use app\index\model\HonorSet;
use app\index\model\LearningPowerLog;
use app\wxapp\model\PulsLearnPowerLog;
use app\index\model\Levels;
use app\index\model\Sectiones;
use app\wxapp\model\Colliers;
use app\wxapp\model\CreditSource;
use app\wxapp\model\DedicationLog;
use app\wxapp\model\StartLevel;
use app\wxapp\model\User;
use think\helper\Time;
use think\Db;
use app\index\model\Course;
use app\wxapp\model\CourseLearnLog;


/**
 * 公共函数
 * Class Common
 * @package app\common
 */
class Common
{
    /*
    * 学分来源
    * @param array $data
    */
    public function creditSource($data = [],$uid = 0) {
        $creditSource = new CreditSource();
        $user_model = new User();
        $res = $creditSource->insert($data);
        if(!$res) {
            Db::rollback();
            return false;
        }
        $userId = Db::name('CreditSource')->getLastInsID();
        return $userId;
    }

    /*
     * 贡献值记录
     * @param int $uid
     * @param int $type  类型   1每日才学  2 阅读 3点赞 4 分享  5 反馈意见  6邀请  7 购买消费  8 收藏文章 9 大社群新增一人
     *  10 小社群新增一人  11大社群新增一个学习力  12 小社群新增一个学习力  13 课程收藏  14、课程阅读 15、课程点赞 16、课程分享
     * @param $obj_id 对象id  才学id  文章id  课程id
     * @param string $content
     */
    public  function dedicationLog($uid = 0,$type = 0,$obj_id = '',$content = '',$powerValue=0) {
        $user_model = new User();
        $collier_model = new Colliers();
        $dedicationLog = new DedicationLog();
      
        $collierInfo = $collier_model->field('max,contribution')->where('type',$type)->find();
      
        list($start, $end) = Time::today();
      
        $allDedica = $dedicationLog->where(['uid'=>$uid,'type'=>$type,'addtime'=>['between',[$start, $end]]])->sum('value');
      
        if($type == 9 || $type == 11) {
            return;
            $sq_type = 1;
        }else if($type == 10 || $type == 12) {
            return;
            $sq_type = 2;
        }else if($type == 6) {
            //return;
            $sq_type = 2;
        }else if($type == 17){
            $collierInfo['contribution'] = $powerValue * $collierInfo['contribution'];
            $sq_type = 0;
        }else{
            $sq_type = 0;
        }
        if($collierInfo['max'] == '' || $collierInfo['max'] < 0.01 || $collierInfo['max'] == null) {
            $dedicData = [
                'uid'=>$uid,
                'type'=>$type,
                'value'=>$collierInfo['contribution'],
                'obj_id'=>$obj_id,
                'sq_type'=>$sq_type,
                'content'=>$content,
                'addtime'=>time()
            ];
            if(!$dedicationLog->insert($dedicData)) {
                Db::rollback();
                return false;
            }
          
            if(false == $user_model->where('id',$uid)->setInc('dedication_value',$collierInfo['contribution'])) {
                Db::rollback();
                return  false;
            }
          
            return  $collierInfo['contribution'];
        }else{
            if($allDedica < $collierInfo['max']) {
                $dedicData = [
                    'uid'=>$uid,
                    'type'=>$type,
                    'value'=>$collierInfo['contribution'],
                    'obj_id'=>$obj_id,
                    'sq_type'=>$sq_type,
                    'content'=>$content,
                    'addtime'=>time()
                ];
                if(!$dedicationLog->insert($dedicData)) {
                    Db::rollback();
                    return false;
                }
              
                if(false == $user_model->where('id',$uid)->setInc('dedication_value',$collierInfo['contribution'])) {
                    Db::rollback();
                    return  false;
                }
              
                return  $collierInfo['contribution'];
            }
            return 0;
        }
    }

    /*
     * 社群新增一个学习力 对应的贡献值
     * @param int $uid
     * @param int $type  类型  1 1每日才学  2 阅读 3点赞 4 分享  5 反馈意见  6邀请  7 才学堂课堂刚学习
     * 9 大社群新增一人  10 小社群新增一人  11大社群新增一个学习力  12 小社群新增一个学习力   13才学堂课程消费1元
     * @param string $content
     * @param string $learn_power  新增学习力数量
     * @param string $bonus  社群  big 大社群  small 小社群
     */
    public  function bonusDedicationLog($uid = 0,$type = 0,$learn_power = '',$obj_id = '',$bonus = 'big') {
        $user_model = new User();
        $collier_model = new Colliers();
        $dedicationLog = new DedicationLog();
        $dedicationLog = new DedicationLog();
        $collierInfo = $collier_model->field('contribution')->where('type',$type)->find();
        if($bonus == 'big') {
            $content = "大社群新增一个学习力获得对应的贡献值";
        }else if($bonus == 'small'){
            $content = "小社群新增一个学习力获得对应的贡献值";
        }else{
            $content = '';
        }
        $value = floatval($learn_power * $collierInfo['contribution']);
        $dedicData = [
            'uid'=>$uid,
            'type'=>$type,
            'value'=>$value,
            'obj_id'=>$obj_id,
            'content'=>$content,
            'addtime'=>time()
        ];
        if(!$dedicationLog->insert($dedicData)) {
            Db::rollback();
            return false;
        }
        if(false === $user_model->where('id',$uid)->setInc('dedication_value',$value)) {
            Db::rollback();
            return  false;
        }
        return  true;
    }

    /*
     * 荣誉值记录
     * @param int $uid  用户id
     * @param int $type   类型  1  购买《财学堂》课程每支付1元, 2 买入1个学分, 3 直推实名好友,4 分享每日金句, 5 转发文章,6 兑换课程每支付1学分  9 赠送课程
     * @param string value 荣誉值
     * @param int $obj_id 对象id
     * @param int 荣誉值
     */
    public function honorLog($uid = 0,$type = 1,$obj_id = 0,$value = 0) {
     
        $honorlog = new HonorLog();
        $honorSet = new HonorSet();
        $user_model = new User();
        $data = [
            'uid'=>$uid,
            'type'=>$type,
            'addtime'=>time()
        ];
        $honorSetInfo = $honorSet->field('contribution,max')->where('type',$type)->find();
        list($start, $end) = Time::today();
        $allHornor = $honorlog->where(['uid'=>$uid,'type'=>$type,'addtime'=>['between',[$start, $end]]])->sum('value');
      	if($type == 9){
        	$honer_value = $value;
        }else{
          if($value != 0) {
              $honer_value = $honorSetInfo['contribution'] * $value;
          }else{
              $honer_value = $honorSetInfo['contribution'];
          }
        }
        
        //  如果不设上限并且没达上线
        if($honorSetInfo['max'] != 0 &&  $honorSetInfo['max'] != '' && ($honorSetInfo['max'] < $allHornor || $honorSetInfo['max'] == $allHornor)) {
            return true;
        }else{
            switch ($type) {
                case 1:      // 购买课程 : 学分金额=荣誉值数量
                    $course = new Course();
                    $advanced_model = new Advanced();
                    $courseInfo = $course->field('advanced_id,name')->where(['id'=>$obj_id])->find();
                    $content = '购买课程·《'.$courseInfo['name'].'》获得'.$honer_value .'荣誉值';
                    $data['content'] = $content;
                    $data['value'] = floatval($honer_value);
                    break;
                case 2:
                    $section_model = new Sectiones();
                    $sectionName = $section_model->where('id',$obj_id)->value('name');
                    $content = '买入学分获得'.$honer_value .'荣誉值';
                    $data['content'] = $content;
                    $data['value'] = floatval($honer_value);
                    break;
                case 3:
                    $content = '邀请一名同班同学获得'.$honer_value .'荣誉值';
                    $data['content'] = $content;
                    $data['value'] = floatval($honer_value);
                    break;
                case 4:
                    $content = '分享每日金句获得'.$honer_value .'荣誉值';
                    $data['content'] = $content;
                    $data['value'] = floatval($honer_value);
                    break;
                case 5:
                    $content = '转发文章获得'.$honer_value .'荣誉值';
                    $data['content'] = $content;
                    $data['value'] = floatval($honer_value);
                    break;
                case 6:
                    $content = '兑换课程获得'.$honer_value .'荣誉值';
                    $data['content'] = $content;
                    $data['value'] = floatval($honer_value);
                    break;
                case 9:
                	$course = new Course();
                    $advanced_model = new Advanced();
                    $courseInfo = $course->field('advanced_id,name')->where(['id'=>$obj_id])->find();
                    $content = '赠送课程·《'.$courseInfo['name'].'》获得'.$honer_value .'荣誉值';
                    $data['content'] = $content;
                    $data['value'] = floatval($honer_value);
                    
                    break;
                case 10:
                
                    $content = '学分兑换TLT消耗'.abs($honer_value) .'荣誉值';
                    $data['content'] = $content;
                    $data['value'] = floatval($honer_value);
                    
                    break;    
                default:
                    break;
            }
          
            if(!$honorlog->insert($data)) {
                Db::rollback();
                return false;
            }
            if(false === $user_model->where('id',$uid)->setInc('honor_value',$data['value'])) {
                Db::rollback();
                return false;
            }
        }
        return true;
    }

    /*
     * 学习力
     * @param int $uid  用户id
     * @param int $type  1 学习课程  2 兑换课程
     * @param string $obj_id 课时id 或者课程id
     * @return bool
     */
    public function learnPowerLog($uid = 0,$type = 0,$obj_id = '') {
        $learnPowerLog = new LearningPowerLog();
        $section_model = new Sectiones();
        $course_model = new Course();
        $advanced_model = new Advanced();
        $user_model = new User();
        $learning_power = $user_model->where('id',$uid)->value('learning_power');
        $sectionName = $section_model->where('id',$obj_id)->value('name');
        $content = '';
        //$c_id = $section_model->where('id',$obj_id)->value('c_id');
        $advanced_id = $course_model->where('id',$obj_id)->value('advanced_id');
        $powerValue = $advanced_model->where('id',$advanced_id)->value('learn_power');
      
        if($type == 1) {
            $content = '学习课程 ·《'.$sectionName."》";
            $difficulty = $advanced_model->where('id',$advanced_id)->value('difficulty');
            $powerValue = number_format($powerValue *  $difficulty,4);
        }else if($type == 2) {
            $courseName = $course_model->where('id',$obj_id)->value('name');
            $content = '兑换课程 ·《'.$courseName."》";
        }else if($type == 3) {
            $content = '加成学习力';
        }else if($type == 6) {
            $courseName = $course_model->where('id',$obj_id)->value('name');
            $content = '平台课程 ·《'.$courseName."》";
        }
        $data = [
            'uid'=>$uid,'type'=>$type,'value'=>$powerValue,'status'=>1,
            'obj_id'=>$obj_id,'content'=>$content,'addtime'=>time()
        ];
        if(!$learnPowerLog->insert($data)) {
            Db::rollback();
            return false;
        }
        //族系增加相应的贡献值
		if($type==2){
		    if(false === $this->learnPowerDedication($uid,$powerValue,$obj_id)){
                Db::rollback();
                return false;
            }
		}
        
      
        if(false === $user_model->where('id',$uid)->setInc('learning_power',$powerValue)) {
            Db::rollback();
            return false;
        }
      
        return true;
    }

    /*
     * 加成学习力
     * @param int $uid
     * @param int $type
     * @param string $obj_id
     */
    public function bonusLearnPower($uid = 0,$type = 0,$obj_id = '') {
        $learnPowerLog = new LearningPowerLog();
        $section_model = new Sectiones();
        $course_model = new Course();
        $advanced_model = new Advanced();
        $user_model = new User();
        $sectionName = $section_model->where('id',$obj_id)->value('name');
        $content = '';
        $c_id = $section_model->where('id',$obj_id)->value('c_id');
        $advanced_id = $course_model->where('id',$c_id)->value('advanced_id');
        $powerValue = $advanced_model->where('id',$advanced_id)->value('learn_power');
        if($type == 1) {
            $content = '学习课程 ·《'.$sectionName."》";
            $difficulty = $advanced_model->where('id',$advanced_id)->value('difficulty');
            $powerValue = number_format($powerValue *  $difficulty,4);
        }else if($type == 2) {
            $content = '班级同学兑换课程 ·《'.$sectionName."》";
        }
        $data = [
            'uid'=>$uid,'type'=>$type,'value'=>$powerValue,'status'=>1,
            'obj_id'=>$obj_id,'content'=>$content,'addtime'=>time()
        ];
        if(!$learnPowerLog->insert($data)) {
            Db::rollback();
            return false;
        }

        if(false === $user_model->where('id',$uid)->setInc('bonus_learn_power',$powerValue)) {
            Db::rollback();
            return false;
        }
        return true;
    }

    //每增加一个学习力给族系上级增加相应的贡献值
    public function learnPowerDedication($uid,$powerValue,$obj_id){
        if($powerValue<=0){
            return;
        }
        $user_model = new User();
        $userinfo = $user_model->where('id',$uid)->field('pid,parentids')->find();
		
        if(empty($userinfo['pid']) ||empty($userinfo['parentids'])){
            return;
        }
		if(empty($userinfo['parentids'])){
        	return;
        }
        if($userinfo['parentids'] == '0,'){
          	$gidata = [
            	'ids' =>$userinfo['pid'],
              	'type' =>17,
              	'content' =>'同年级同学增加学习力',
              	'obj_id' =>$obj_id,
              	'value' =>$powerValue,
              	'addtime' => time(),
              	'uid' => $uid
            ];
           	Db::name('give_out')->insert($gidata);
            //if(false === $this->dedicationLog($userinfo['pid'],17,$obj_id,$content = '同年级同学增加学习力',$powerValue)){
            //    return false;
           // }
          

        }else{
            $userinfo['parentids'] = trim($userinfo['parentids'],',');
            $parids = explode(',',$userinfo['parentids']);
          	$gidata = [
            	'ids' =>$userinfo['parentids'],
              	'type' =>17,
              	'content' =>'同年级同学增加学习力',
              	'obj_id' =>$obj_id,
              	'value' =>$powerValue,
              	'addtime' => time(),
              	'uid' => $uid
            ];
           	Db::name('give_out')->insert($gidata);
            foreach($parids as $key=>$val){
              $topuserinfo = $user_model->where('id',$parids[1])->field('pid')->find();
                if($val == 0){
                       $gidata = [
                            'ids' =>$topuserinfo['pid'],
                            'type' =>17,
                            'content' =>'同年级同学增加学习力',
                            'obj_id' =>$obj_id,
                            'value' =>$powerValue,
                            'addtime' => time(),
              				'uid' => $uid
                        ];
                        Db::name('give_out')->insert($gidata);
                    break;
                    //if(false === $this->dedicationLog($topuserinfo['pid'],17,$obj_id,$content = '同年级同学增加学习力',$powerValue)){

                     //   return false;
                   // }
                  
                }else{
                  	continue;
                    //if(false === $this->dedicationLog($val,17,$obj_id,$content = '同年级同学增加学习力',$powerValue)){

                      //  return false;
                   // }
                 
                }
            }
        }
    }
    /*
     * 生成加成学习力任务并增加学习力
     * @param int $uid
     * @param int $type
     * @param string $obj_id
     */
    public function pulsLearnPowerLog($uid = 0,$type = 0,$obj_id = '') {
        $learnPowerLog = new LearningPowerLog();
        $section_model = new Sectiones();
        $course_model = new Course();
        $advanced_model = new Advanced();
        $user_model = new User();
        $pid = $user_model->where('id',$uid)->value('pid');
        if(empty($pid)){
            return;
        }
        $PulsLearning_model = new PulsLearnPowerLog();
        $sectionName = $course_model->where('id',$obj_id)->value('name');
        $content = '';
        //$c_id = $section_model->where('id',$obj_id)->value('c_id');
        $advanced_id = $course_model->where('id',$obj_id)->value('advanced_id');
        $powerValue = $advanced_model->where('id',$advanced_id)->value('learn_power');
        $preward = $advanced_model->where('id',$advanced_id)->value('reward');
        if($powerValue<=0){
            return;
        }
        if($type == 1) {
            $content = '[加成]学习课程 ·《'.$sectionName."》";
            $difficulty = $advanced_model->where('id',$advanced_id)->value('difficulty');
            $powerValue = number_format($powerValue *  $difficulty,4);
        }else if($type == 2) {
            $content = '[加成]兑换课程 ·《'.$sectionName."》";
        }
        //加成学习力
        $pluspowerValue = $powerValue * 0.05;
        //学分
        $pcredit =$pluspowerValue *0.33*30;
        $data = [
            'uid'=>$pid,'type'=>3,'value'=>$pluspowerValue,'status'=>1,
            'obj_id'=>$obj_id,'content'=>$content,'addtime'=>time()
        ];
        if(!$learnPowerLog->insert($data)) {
            Db::rollback();
            return false;
        }

        $pulslearningdata = [
            'uid' => $pid,
            'content' => $content,
            'day' => 30,
            'addtime' =>time(),
            'cid' => $obj_id,
            'learning_power' => $pluspowerValue,
            'credit' =>$pcredit
        ];
        if(!$PulsLearning_model->insert($pulslearningdata)) {
            Db::rollback();
            return false;
        }
      	//$user_model->where('id',$pid)->update('bonus_learn_power',$pluspowerValue);
        if(!$user_model->where('id',$pid)->setInc('bonus_learn_power',$pluspowerValue)) {
            Db::rollback();
            return false;
        }
        return true;
    }


    /*
     * 用户升级等级
     * @param int $uid 当前用户id
     */
    public function userChangeLevel($uid = 0) {
      
        $level_model = new Levels();
        $user_model = new User();
        $advanced_model = new Advanced();
        $course_model = new Course();
        $userInfo = $user_model->where('id',$uid)->find();
        //$nextLevel = $userInfo['level'] + 1;
        $lwhere['value'] = array('>',$userInfo['level']);
        $nextLevel = $level_model ->where($lwhere)->order('value')->value('value');
      	
        // 当前用户等级升级
        if(false === $this->changeLevelOpt($uid,$nextLevel)) {
            Db::rollback();
            return false;
        }

        $pid = $user_model->where('id',$uid)->value('pid');
        // 上级用户等级升级
        if($pid != 0) {
            $pUserInfo = $user_model->where('id',$pid)->find();
            //$pNextLevel = $pUserInfo['level'] + 1;
            $plwhere['value'] = array('>',$pUserInfo['level']);
            $pNextLevel = $level_model ->where($plwhere)->order('value')->value('value');
            
            if(false === $this->changeLevelOpt($pid,$pNextLevel)) {
                Db::rollback();
                return false;
            }
           
            // 荣誉值
            if(false === $this->honorLog($pid,3,'')) {
                Db::rollback();
                return false;
            }
        }

        $adverInfo = $advanced_model->field('id')->where('type',3)->find();
        $courseInfo = $course_model->field('id')->where('advanced_id',$adverInfo['id'])->find();
      
        // 学习力
        if(false === $this->learnPowerLog($uid,2,$courseInfo['id'])) {
            Db::rollback();
            return false;
        }
      
        return true;
    }

    /*
     * 等级升级操作
     */
    public function changeLevelOpt($uid = 0,$level = 1) {
        $user_model = new User();
        $level_model = new Levels();
        $advanced_model = new Advanced();
        $maxLevel = $level_model->max('value');
      
      	
        if($level > 1) {
            $invite_people = $level_model->where('value',$level)->value('invite_people');
            // 邀请并认证的数量
            $userinfo = $user_model->field('invate_num')->where(['id'=>$uid,'is_auth'=>1])->find();
            $invitedCount = empty($userinfo['invate_num']) ? 0 : $userinfo['invate_num'];
          //$documentRoot = $_SERVER['DOCUMENT_ROOT'];
//file_put_contents($documentRoot.'/log_0807__2.txt',print_r('邀请数量：'.($userInfo['invate_num']+ 1).'升级数量:'.$startLevelInfo['invite_people'].'荣誉值：'.($userInfo['dedication_value'] + 1).'升级数量：'.$startLevelInfo['contribution'].'拥有小沈群贡献值'.($smallSqCount + 1).'升级小社群'.$startLevelInfo['small_sq'].'最大等级'.$maxStartLevel.'下一级：'.$nextStartLevel.'是否是真：'.$true,true),FILE_APPEND);
        
            if(($invitedCount + 1 > $invite_people || $invitedCount + 1 == $invite_people) &&  ($maxLevel > $level || $maxLevel == $level)) {
                if(false === $user_model->where('id',$uid)->update(['level'=>$level])) {
                    Db::rollback();
                    return false;
                }
            }
        }else if($level == 1){
            if(false === $user_model->where('id',$uid)->update(['level'=>$level])) {
                Db::rollback();
                return false;
            }
          
            // 赠送免费课程
            $advancedInfo = $advanced_model->field('id')->where('type',3)->find();
            if(false === $this->sendAdvanceCourse($uid,$advancedInfo['id'])) {
                Db::rollback();
                return false;
            }
          
        }
      
        // 更新用户星际等级
        if(false === $this->userChangeStartLevel($uid)) {
            Db::rollback();
            return false;
        }
      
        return true;
    }

    /*
     * 用户星际等级升级
     * @param int $uid 用户等级
     */
    public function userChangeStartLevel($uid = 0) {
        $startLevel = new StartLevel();
        $user_model = new User();
        $dedicationLog = new DedicationLog();
        $userInfo = $user_model->field('start_level,invate_num,dedication_value')->where('id',$uid)->find();
        $swhere['value'] = array('>',$userInfo['start_level']);
        $nextStartLevel = $startLevel ->where($swhere)->order('value')->value('value');
        //$nextStartLevel = $userInfo['start_level'] + 1;
        $startLevelInfo = $startLevel->where('value',$nextStartLevel)->find();
        $maxStartLevel = $startLevel->max('value');
        //$smallSqCount = $dedicationLog->where(['uid'=>$uid,'sq_type'=>2])->sum('value');
      $true = 0;
       if($nextStartLevel==2){
            $punm = $user_model->where('is_auth',1)->where('pid',$uid)->where('start_level','egt',2)->count();
         	if($punm>=2){
            	$true = 1;
            }
        }elseif($nextStartLevel==3){
            $punm = $user_model->where('is_auth',1)->where('pid',$uid)->where('start_level','egt',3)->count();
         	if($punm>=2){
            	$true = 1;
            }
        }elseif($nextStartLevel==4){
            $punm = $user_model->where('is_auth',1)->where('pid',$uid)->where('start_level','egt',4)->count();
         	if($punm>=2){
            	$true = 1;
            }
        }else{
       		$true = 1;
       }
        $minshequnD = $this->isMaxOrMin($uid,1);
        $smallSqCount = $minshequnD['minshequnD'];
      //$smallSqCount = 0;
      //if($uid == 7498){
        	//$documentRoot = $_SERVER['DOCUMENT_ROOT'];
//file_put_contents($documentRoot.'/log_0807__1.txt',print_r('邀请数量：'.($userInfo['invate_num']+ 1).'升级数量:'.$startLevelInfo['invite_people'].'荣誉值：'.($userInfo['dedication_value'] + 1).'升级数量：'.$startLevelInfo['contribution'].'拥有小沈群贡献值'.($smallSqCount + 1).'升级小社群'.$startLevelInfo['small_sq'].'最大等级'.$maxStartLevel.'下一级：'.$nextStartLevel.'是否是真：'.$true,true),FILE_APPEND);
        //}
        if(($userInfo['invate_num'] + 1 > $startLevelInfo['invite_people'] || $userInfo['invate_num'] + 1 == $startLevelInfo['invite_people']) && ($userInfo['dedication_value'] + 1 > $startLevelInfo['contribution'] || $userInfo['dedication_value'] + 1 == $startLevelInfo['contribution']) && ($smallSqCount + 1 > $startLevelInfo['small_sq'] || $smallSqCount + 1 == $startLevelInfo['small_sq']) && ($maxStartLevel > $nextStartLevel || $nextStartLevel == $maxStartLevel)&&$true) {
            if(false === $user_model->where('id',$uid)->update(['start_level'=>$nextStartLevel])) {
                Db::rollback();
                return false;
            }
            // 星际等级达到 赠送课程
            if(false === $this->sendAdvanceCourse($uid,$startLevelInfo['advanced_id'])) {
                Db::rollback();
                return false;
            }
        }
        return true;
    }

    /*
     * 赠送课程
     * @param int $uid 用户id
     * @param int $advanced_id  进阶id
     */
    public function sendAdvanceCourse($uid = 0,$advanced_id = 0,$type = 0,$cid = 0) {
        $course_model = new Course();
        $order_model = new \app\wxapp\model\Orders();
        $advanced_model = new Advanced();
        if($type==3){
            $courseList = $course_model->field('id,imgurl,name,advanced_id')->where('id',$cid)->limit(1)->where('is_delete',0)->select();
            //var_dump($courseList);exit;
            //$advanced_id = $courseList[0]['$courseList'];
        }else{
            $courseList = $course_model->field('id,imgurl,name,advanced_id')->where('advanced_id',$advanced_id)->limit(1)->order('id')->where('is_delete',0)->select();
        }
        
        foreach ($courseList as $k=>$val) {
            $order_id = time().rand(1000,9999).$k;
            $advancedInfo = $advanced_model->field('deadline,value,reward')->where('id',$val['advanced_id'])->find();
            $data = [
                'order_id'=>$order_id,
                'course_id'=>$val['id'],
                'advanced_id'=>$val['advanced_id'],
                'uid'=>$uid,
                'effective'=>$advancedInfo['deadline'],
                'addtime'=>time(),
                'value'=>$advancedInfo['value'],
                'score'=>$advancedInfo['reward'],
                'status'=>1,
                'paytime'=>time(),
            ];
          	if($order_model->where(['uid'=>$uid,'course_id'=>$val['id']])->where('status','neq',0)->find()){
          	    
            	continue;
            }
            
            if(!$order_model->where(['uid'=>$uid,'course_id'=>$val['id'],'status'=>1])->find()) {
                if(!$order_model->insert($data)) {
                    Db::rollback();
                    return false;
                }
              	if($type==3){
              	    
              	}else{
              	    if(false === $this->honorLog($uid,9,$val['id'],$advancedInfo['value'])) {
                        Db::rollback();
                        return false;
                    }
              	}
                 
             
              if($uid == 7498){
              	$missiondata = array(
                	'objid'=>$val['id'],
                  'type'=>1,
                  'addtime'=>time(),
                );
                Db::name('mission')->insert($missiondata);
              }
             
        		
                if(false === $this->sendCourseOpt($uid,$val['id'],$type)) {
                    Db::rollback();
                    return false;
                }
             
                // 解锁第一节课
                $this->unlockCourse($val['id'],$uid);
           } 
            
        }
      
        return true;
    }

    /*
     * 赠送课程的后续操作
     * @param int $uid
     * @param int $course_id
     * @return \type
     */
    public function sendCourseOpt($uid = 0,$course_id = 0,$type=0) {
        $user_model = new User();
        $advanced_model = new Advanced();
        $course_model = new Course();
        $advanced_id = $course_model->where('id',$course_id)->value('advanced_id');
        $learn_power = $advanced_model->where('id',$advanced_id)->value('learn_power');
        // 荣誉值
        $pid = $user_model->where('id',$uid)->value('pid');
        if($pid != 0) {
            //加成学习力
            if($type==3){
                
            }else{
                $pluspowerres = $this->pulsLearnPowerLog($uid,2,$course_id);
                if(false === $pluspowerres){
                    Db::rollback();
                    return returnjson(1001,'','购买失败');
                }
            }
            

            $parent_ = $user_model->field('parentids')->where(['pid'=>$pid])->find();
            if($parent_['parentids'] == '0,'){
                $myshequn = $pid;
            }else{
                $myparentids= $user_model->field('parentids')->where(['parentids'=>['like',"%,{$pid},%"]])->find();
                //echo $user_model->getLastSql();exit;
                $myparentids = explode(',',$myparentids['parentids']);

                $myshequn = $myparentids[1];
            }
            // 直推相关结算是否已经结算
            if($user_model->where('id',$uid)->value('is_zt') === 0) {
                //  条件 直接推荐，至少有一个课程在学
                if($user_model->where(['id'=>$pid])->value('learning_power') > 0 ) {
                    $content = "邀请一名同班同学";
                    //$user_model->where(['id'=>$pid])->setInc('invate_num');
                    if(false === $this->dedicationLog($pid,6,$course_id,$content)) {
                        Db::rollback();
                        return false;
                    }
                }
                /*
                // 大社群新增一人-- 贡献值明细
                $maxShequn = $user_model->field('id')->where(['parentids'=>'0,'])->order('invate_num asc')->find();
                if($myshequn == $maxShequn['id']) {
                    $content = "大社群新增一人";
                    if(false === $this->dedicationLog($maxShequn['id'],9,$course_id,$content)) {
                        Db::rollback();
                        return false;
                    }
                    $user_model->where(['id'=>$maxShequn['id']])->setInc('invate_num');
                    //大社群新增一个学习力
                    if(false === $this->bonusDedicationLog($maxShequn['id'],11,$learn_power,$course_id,'big')) {
                        Db::rollback();
                        return false;
                    }
                    $user_model->where(['id'=>$maxShequn['id']])->setInc('invate_num');
                }else{// 小社群新增一人
                    $otherShequnList = $user_model->field('id')->where(['parentids'=>'0,','id'=>['neq',$maxShequn['id']]])->select();
                    foreach ($otherShequnList as $value) {
                        $content = "小社群新增一人";
                        if(false === $this->dedicationLog($value['id'],10,$course_id,$content)) {
                            Db::rollback();
                            return false;
                        }
                        // 小社群新增一个学习力 获得的贡献值
                        if(false === $this->bonusDedicationLog($value['id'],12,$learn_power,$course_id,'small')) {
                            Db::rollback();
                            return false;
                        }
                    }
                    $user_model->where(['id'=>$myshequn])->setInc('invate_num');
                }*/
                $user_model->where(['id'=>$uid])->setInc('is_zt');
            }
        }
        return true;
    }

    /*
      * 进入已购课程列表页面开始解锁操作
      */
    public function unlockCourse($course_id = 0,$uid = 0) {
        $user_model = new \app\wxapp\model\User();
        $startLevel = new StartLevel();
        $order_model = new \app\wxapp\model\Orders();
        $course_model = new Course();
        $section_model = new Sectiones();
        $learnLog = new CourseLearnLog();
        $courseInfo = $course_model->where('id',$course_id)->find();
        $orderCount = $order_model->where(['course_id'=>$course_id,'uid'=>$uid,'status'=>1])->count();

        if($orderCount > 0) {
            $paytime = $order_model->where(['uid'=>$uid,'course_id'=>$course_id,'status'=>1])->value('paytime');
            $days = ceil((time() - $paytime) / 86400) ; // 学习该课程的天数
            $start_level = $user_model->where('id',$uid)->value('start_level');
          	if($days==0){
            	$days = $days+1;
            }
            if($start_level == 0) {
                // 会员等级 则一天解锁一节课
                $unlockCourseCount = $days;
            }else{
                // 学习周期加速比例 % 计算1天解锁的章节数
                $learn_accelerate = $startLevel->where('value',$start_level)->value('learn_accelerate');
                $unlockCourseCount = floor(($days) * $learn_accelerate / 100);
            }
            file_put_contents("unlock1.txt",$unlockCourseCount,FILE_APPEND);
            $sectionList = $section_model->where(['c_id'=>$course_id,'is_delete'=>0])->order('sort asc')->limit(0,$unlockCourseCount)->select();
            $learnData = [];
          //明天开始时间戳
          	//$tomorrow_start_time = mktime(0,0,0,date('m'),date('d'),date('Y'))+86400;
            if($sectionList) {
                foreach ($sectionList as $k=>$val) {
                    $learnData['section_id'] = $val['id'];
                    $learnData['uid'] = $uid;
                    $learnData['course_id'] = $course_id;
                    $learnData['addtime'] = time();
                  	$learnData['unlocktime'] = time();
                    $learnLog->insert($learnData);
                }
            }
        }
        return true;
    }
//获取大神群
    public function getDshequn($uid){
        $user_model = new User();
        $myinfo = $user_model->where('id',$uid)->field('pid,parentids,invate_num')->find();
        $total_pnum1 = 0;
            if($myinfo['pid'] == 0){
                $total_pnum1 = $user_model->where('pid',$uid)->field('id')->count();
                $total_ = $user_model->where('pid',$uid)->field('id')->select();

                foreach($total_ as $tokey=>$toval){
                    $t_id = "%,".$toval['id'].",%";
                    $total_pnum1 += $user_model->where('parentids','like',$t_id)->field('id')->count();
                }

            }
            $vid = '%,'.$uid.',%';
            $total_pnum2 = $user_model->where('parentids','like',$vid)->field('id')->count();


          $total_pnum = $total_pnum1 + $total_pnum2+1;
          return $total_pnum;
    }
    //查看我是大社群还是小社群,获取社群人数，获取小社群贡献值
    public function isMaxOrMin($uid,$minshequnDtype=0,$linshi=0){
        $user_model = new User();
        $myinfo = $user_model->where('id',$uid)->field('pid,parentids,invate_num')->find();
        $is_max = 0;
        //直系邀请
      	
        
      	//获取线下总人数
      //如果pid == 0
      if($linshi == 1){
          $total_pnum1 = 0;
            if($myinfo['pid'] == 0){
                $total_pnum1 = $user_model->where('pid',$uid)->field('id')->count();
                $total_ = $user_model->where('pid',$uid)->field('id')->select();

                foreach($total_ as $tokey=>$toval){
                    $t_id = "%,".$toval['id'].",%";
                    $total_pnum1 += $user_model->where('parentids','like',$t_id)->field('id')->count();
                }

            }
            $vid = '%,'.$uid.',%';
            $total_pnum2 = $user_model->where('parentids','like',$vid)->field('id')->count();


          $total_pnum = $total_pnum1 + $total_pnum2;
        $direct = $user_model->where(['pid'=>$uid])->field('pid,parentids,invate_num')->count();
      }else{
            $total_pnum1 = 0;
            if($myinfo['pid'] == 0){
                $total_pnum1 = $user_model->where('pid',$uid)->where(['is_auth'=>1])->field('id')->count();
                $total_ = $user_model->where('pid',$uid)->where(['is_auth'=>1])->field('id')->select();

                foreach($total_ as $tokey=>$toval){
                    $t_id = "%,".$toval['id'].",%";
                    $total_pnum1 += $user_model->where('parentids','like',$t_id)->where(['is_auth'=>1])->field('id')->count();
                }

            }
            $vid = '%,'.$uid.',%';
            $total_pnum2 = $user_model->where('parentids','like',$vid)->where(['is_auth'=>1])->field('id')->count();


          $total_pnum = $total_pnum1 + $total_pnum2;
        $direct = $user_model->where(['pid'=>$uid,'is_auth'=>1])->field('pid,parentids,invate_num')->count();
      }
      
        //大社群人数
        $pnum = 0;
        //其他社群
        $other = 0;
        $min_dedication_value = 0;
        $minshequnD = 0;
        $getall = [];
      $total_num = 0;
      $min_dedication_value = 0;
      $all_power = 0;
        //if($myinfo['pid']>0){
            $allshequn = $user_model->where(['pid'=>$uid])->where('invate_num','gt',0)->field('id')->select();
            $max_qun = array();
            $idss = array();
            $dsnum = array();
            /*if($allshequn){
                foreach($allshequn as $akey=>$aval){
                    $idss[$akey] = $aval['id'];
                    $dsnum[$akey] = $this->getDshequn($aval['id']);
                    $allshequn[$akey]['dsnum'] = $dsnum[$akey];
                }
            }
            if($idss){
               
                array_multisort($dsnum, SORT_DESC, $idss, SORT_ASC, $allshequn);
            }
             
              $i = 0;
              $max_qun = array();
              if($allshequn){
                  foreach($allshequn as $allkey=>$allval){
                      if($i>=2){
                            break;
                        }
                        $max_qun[$allkey]['id'] = $allval['id'];
                        $max_qun[$allkey]['dsnum'] = $allval['dsnum'];
                        $i++;
                    }
                    
                    
              }*/
            $max_qun = $user_model->where(['pid'=>$uid,'is_auth'=>1])->order('invate_num desc')->limit(2)->select();
            //大社群id
            $maxids = '';
            if(!empty($max_qun)){
                foreach($max_qun as $mkey=>$mvalue){
                    if($mvalue['id'] == $uid){
                        $is_max = 1;

                    }
                    $maxids .= $mvalue['id'].',';
                    //$pnum += $mvalue['invate_num']+1;
                }

                //小社群
                $maids = trim($maxids,',');

                $getall = $this->getall($uid,$maids,$myinfo['pid']);
                $pnum = $getall['maxinvate_num'];
				$total_num = $getall['total_num'];
                if($maids){
                  
                    if($minshequnDtype == 1){
                        $minshequnD = $this->minShequnD($uid,$maids,$myinfo['pid']);
                      
                    }else{
                        $minshequnD = 0;
                    }
                    $other = $user_model->where(['pid'=>$myinfo['pid'],'is_auth'=>1])->where('id','not in',$maids)->sum('invate_num');
                    $other1 = $user_model->where(['pid'=>$myinfo['pid'],'is_auth'=>1])->where('id','not in',$maids)->count();
                    $other   += $other1;
                    $other = $getall['mininvate_num'];
                }
                $min_dedication_value = $getall['minlearning_power'];
              $all_power = $getall['all_power'];
            }
       // }

        return array('direct'=>$direct,'pnum'=>$pnum,'other'=>$other,'is_max'=>$is_max,'min_dedication_value'=>$min_dedication_value,'minshequnD'=>$minshequnD,'power'=>$getall,'total_num'=>$total_pnum,'all_power'=>$all_power);
    }
    //获取大社群小社群的人数，学习力
    public function getall($uid,$maxids,$pid){
        $user_model = new User();
      $dedicationLog = new DedicationLog();
        $max_ids = '';
        if(!empty($maxids)){
            $maxidsarr =$user_model->where('id','in',$maxids)->where(['pid'=>$uid,'is_auth'=>1])->field('id')->select();
            $max_ids = '';
            foreach($maxidsarr as $mkey=>$mval){
              	
                $max_ids .= $mval['id'].',';
                //$mvid = '%,'.$mval['id'].',%';
                //$mchg = $user_model->where('parentids','like',$mvid)->where(['is_auth'=>1])->field('id')->select();
                //if(!empty($mchg)){
                 //   $mlids = explode(',',$mchg);
                 //   $max_ids .= $mlids.',';
                //}
            }
          	$$max_ids = trim($max_ids,',');
          	
           	$maxlearning_power = $dedicationLog->where('uid','in',$$max_ids)->where(['type'=>17])->sum('value');
            $minids = $user_model->where('id','not in',$maxids)->where(['pid'=>$uid,'is_auth'=>1])->field('id')->select();
        }
        $ids = '';
        if(!empty($minids)){
            $ids = '';
            foreach($minids as $key=>$val){
                $ids .= $val['id'].',';
               // $vid = '%,'.$val['id'].',%';
               // $chg = $user_model->where('parentids','like',$vid)->where(['is_auth'=>1])->field('id')->select();
                //if(!empty($chg)){
                 //   $lids = explode(',',$chg);
                 //   $ids .= $lids.',';
               // }
            }
        }
        $mininvate_num = 0;
        $minlearning_power = 0;
        $maxinvate_num = 0;
        $maxlearning_power = 0;
        if(!empty($ids)){
            $ids = trim($ids,',');
          	
           	$minlearning_power = $dedicationLog->where('uid','in',$max_ids)->where(['type'=>17])->sum('value');
            //$mininvate_num = $user_model->where('id','in',$ids)->sum('invate_num');
            //$minlearning_power = $user_model->where('id','in',$ids)->sum('learning_power');
        }
        if(!empty($max_ids)){
            $max_ids = trim($max_ids,',');
            $maxinvate_num = $user_model->where('id','in',$max_ids)->sum('invate_num');
            $maxlearning_power = $user_model->where('id','in',$max_ids)->sum('learning_power');

        }
      	//旗下总学习里
      	//$all = $user_model->where(['pid'=>$uid,'is_auth'=>1])->field('id')->select();
      //$a_ids = '';
      	//foreach($all as $akey=>$aval){
              	
              //  $a_ids .= $aval['id'].',';
                
            //}
      	//if($a_ids){
        	//$a_ids = trim($a_ids,',');
          	
           	$all_power = $dedicationLog->where('uid',$uid)->where(['type'=>17])->sum('value');
       // }
		$total_num = $mininvate_num + $maxinvate_num;
        return array('mininvate_num'=>$mininvate_num,'minlearning_power'=>$minlearning_power,'maxinvate_num'=>$maxinvate_num,'maxlearning_power'=>$maxlearning_power,'total_num'=>$total_num,'all_power'=>$all_power);
    }

    //获取小社群的贡献值
    public function minShequnD($uid,$maxids,$pid){
        $user_model = new User();
        
        
        $maxids = Db::name('user') ->where('pid',$uid)->limit(2)->order('dedication_value','desc')->field('id')->select();
        
        $count = count($maxids);
        if($count==2){
            
            $max_ids = $maxids[0]['id'].','.$maxids[1]['id'];
        }else{
            return 0;
        }
        //var_dump($max_ids);exit;
        $smallSqCount = Db::name('user') ->where('pid',$uid)->where('id','not in',$max_ids)->sum('dedication_value');
        //$smallSqCount = $all['class_dedication'] - $max;
       
        return $smallSqCount;
    }
  //用于定时任务
  	public  function dedicationLog_($uid = 0,$type = 0,$obj_id = '',$content = '',$powerValue=0) {
        $user_model = new User();
        $collier_model = new Colliers();
        $dedicationLog = new DedicationLog();
      
        $collierInfo = $collier_model->field('max,contribution')->where('type',$type)->find();
      
        list($start, $end) = Time::today();
      
        $allDedica = $dedicationLog->where(['uid'=>$uid,'type'=>$type,'addtime'=>['between',[$start, $end]]])->sum('value');
      
        
            $collierInfo['contribution'] = $powerValue * $collierInfo['contribution'];
            $sq_type = 0;
        
        if($collierInfo['max'] == '' || $collierInfo['max'] < 0.01 || $collierInfo['max'] == null) {
            $dedicData = [
                'uid'=>$uid,
                'type'=>$type,
                'value'=>$collierInfo['contribution'],
                'obj_id'=>$obj_id,
                'sq_type'=>$sq_type,
                'content'=>$content,
                'addtime'=>time()
            ];
            if(!$dedicationLog->insert($dedicData)) {
                Db::rollback();
                return false;
            }
            //if($type==17){
                if(false == $user_model->where('id',$uid)->setInc('class_dedication',$collierInfo['contribution'])) {
                    Db::rollback();
                    return  false;
                }
            //}
            
            if(false == $user_model->where('id',$uid)->setInc('dedication_value',$collierInfo['contribution'])) {
                Db::rollback();
                return  false;
            }
          
            return  $collierInfo['contribution'];
        }else{
            if($allDedica < $collierInfo['max']) {
                $dedicData = [
                    'uid'=>$uid,
                    'type'=>$type,
                    'value'=>$collierInfo['contribution'],
                    'obj_id'=>$obj_id,
                    'sq_type'=>$sq_type,
                    'content'=>$content,
                    'addtime'=>time()
                ];
                if(!$dedicationLog->insert($dedicData)) {
                    Db::rollback();
                    return false;
                }
                //if($type==17){
                if(false == $user_model->where('id',$uid)->setInc('class_dedication',$collierInfo['contribution'])) {
                        Db::rollback();
                        return  false;
                    }
                //}
                if(false == $user_model->where('id',$uid)->setInc('dedication_value',$collierInfo['contribution'])) {
                    Db::rollback();
                    return  false;
                }
              
                return  $collierInfo['contribution'];
            }
            return ture;
        }
    }
  	//发放族系学习力定时任务
  	function giveOut(){
      $i = 0;
    	$all = Db::name('give_out')->where('status',0)->select();
      	foreach($all as $key=>$value){
        	Db::rollback();
          	$ids = trim($value['ids'],'0,');
          	if(!empty($ids)){
              $idarray = array();
            	$idarray = explode(',',$ids);
              	foreach($idarray as $k=>$v){
                  	$i++;
                	$res = $this->dedicationLog_($v,17,$value['obj_id'],$value['content'],$value['value']);
                  //$res = 1;
                  if( false ===$res){
                  	 Db::rollback();
                    return false;
                  }
                }
              	Db::name('give_out')->where('id',$value['id'])->update(['status'=>1]);
            }
          
          	Db::commit();
        }
      echo '共发放'.$i.'份';
    }
    
    /*
     * 赠送课程
     * @param int $uid 当前用户id
     */
    public function userChangeLevel_admin($uid = 0,$course_id) {
      
        $level_model = new Levels();
        $user_model = new User();
        $advanced_model = new Advanced();
        $course_model = new Course();

        if(false === $this->sendAdvanceCourse($uid,$course_id,3,$course_id)) {
                Db::rollback();
                return false;
        }
        
        // 学习力
        if(false === $this->learnPowerLog($uid,6,$course_id)) {
            Db::rollback();
            return false;
        }
      
        return true;
    }
    
    /*
     * 本地区同学学习力1%的加成
     * @param int $uid 当前用户id
     */
    public function region_class_share($uid = 0,$course_id) {
      
        $user_model = new User();
        $advanced_model = new Advanced();
        $course_model = new Course();
        
        if(false === $this->sendAdvanceCourse($uid,$course_id,3,$course_id)) {
                Db::rollback();
                return false;
        }
        
        // 学习力
        if(false === $this->learnPowerLog($uid,6,$course_id)) {
            Db::rollback();
            return false;
        }
      
        return true;
    }
    
     //获取社群人数
    public function getShequnNum($uid,$linshi=0){
         $user_model = new User();
        $myinfo = $user_model->where('id',$uid)->field('pid,parentids,invate_num')->find();
        if($linshi == 1){
          $total_pnum1 = 0;
            if($myinfo['pid'] == 0){
                $total_pnum1 = $user_model->where('pid',$uid)->field('id')->count();
                $total_ = $user_model->where('pid',$uid)->field('id')->select();

                foreach($total_ as $tokey=>$toval){
                    $t_id = "%,".$toval['id'].",%";
                    $total_pnum1 += $user_model->where('parentids','like',$t_id)->field('id')->count();
                }

            }
            $vid = '%,'.$uid.',%';
            $total_pnum2 = $user_model->where('parentids','like',$vid)->field('id')->count();


          $total_pnum = $total_pnum1 + $total_pnum2;
        //$direct = $user_model->where(['pid'=>$uid])->field('pid,parentids,invate_num')->count();
      }else{
            $total_pnum1 = 0;
            if($myinfo['pid'] == 0){
                $total_pnum1 = $user_model->where('pid',$uid)->where(['is_auth'=>1])->field('id')->count();
                $total_ = $user_model->where('pid',$uid)->where(['is_auth'=>1])->field('id')->select();

                foreach($total_ as $tokey=>$toval){
                    $t_id = "%,".$toval['id'].",%";
                    $total_pnum1 += $user_model->where('parentids','like',$t_id)->where(['is_auth'=>1])->field('id')->count();
                }

            }
            $vid = '%,'.$uid.',%';
            $total_pnum2 = $user_model->where('parentids','like',$vid)->where(['is_auth'=>1])->where(['level'=>['neq',0]])->field('id')->count();


          $total_pnum = $total_pnum1 + $total_pnum2;
        //$direct = $user_model->where(['pid'=>$uid,'is_auth'=>1])->field('pid,parentids,invate_num')->count();
      }
      
      return $total_pnum;
    }
    
    //获取用户积分
    public function get_user_credit($uid){
        $credit = Db::name('user_credit')->where('uid',$uid)->value('credit');
        if(empty($credit)){
            $credit  = 0;
        }
        return $credit;
    }
    
    //增加用户积分
    public function save_credit($uid,$value,$type){
        $credit = Db::name('user_credit')->where('uid',$uid)->value('credit');
        if(empty($credit)&&$credit!=0){
            Db::name('user_credit')->insert(array('uid'=>$uid,'credit'=>$value));
        }else{
            Db::name('user_credit')->where('uid',$uid)->setInc('credit',$value);
        }
        $data = array(
                'uid' =>$uid,
                'credit' =>$value,
                'type' =>$type,
                'type1' =>1,
                'addtime' =>time(),
            );
        Db::name('user_credit_log')->insert($data);    
    }
    
}