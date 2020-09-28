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
        if(!$creditSource->insert($data)) {
            Db::rollback();
            return false;
        }
        return true;
    }

    /*
     * 贡献值记录
     * @param int $uid
     * @param int $type  类型   1每日才学  2 阅读 3点赞 4 分享  5 反馈意见  6邀请  7 购买消费  9 大社群新增一人
     *  10 小社群新增一人  11大社群新增一个学习力  12 小社群新增一个学习力    14、课程阅读 15、课程点赞 16、课程分享
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
     * @param int $type   类型  1  购买《财学堂》课程每支付1元, 2 买入1个学分, 3 直推实名好友,4 分享每日金句, 5 转发文章,6 兑换课程每支付1学分
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
        if($value != 0) {
            $honer_value = $honorSetInfo['contribution'] * $value;
        }else{
            $honer_value = $honorSetInfo['contribution'];
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
                    $content = '直推1名实名好友获得'.$honer_value .'荣誉值';
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
        $c_id = $section_model->where('id',$obj_id)->value('c_id');
        $advanced_id = $course_model->where('id',$c_id)->value('advanced_id');
        $powerValue = $advanced_model->where('id',$advanced_id)->value('learn_power');
        if($type == 1) {
            $content = '学习课程 ·《'.$sectionName."》";
            $difficulty = $advanced_model->where('id',$advanced_id)->value('difficulty');
            $powerValue = number_format($powerValue *  $difficulty,4);
        }else if($type == 2) {
            $content = '兑换课程 ·《'.$sectionName."》";
        }else if($type == 3) {
            $content = '加成学习力';
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
        
        if(false === $this->learnPowerDedication($uid,$powerValue,$obj_id)){
                Db::rollback();
                return false;
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
            $content = '兑换课程 ·《'.$sectionName."》";
        }
        $data = [
            'uid'=>$uid,'type'=>$type,'value'=>$powerValue,'status'=>1,
            'obj_id'=>$obj_id,'content'=>$content,'addtime'=>time()
        ];
        if(!$learnPowerLog->insert($data)) {
            Db::rollback();
            return false;
        }
        
        if(false === $user_model->where('id',$uid)->setInc('learning_power',$powerValue)) {
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
        
        if($userinfo['parentids'] == '0,'){
            if(false === $this->dedicationLog($uid,17,$obj_id,$content = '旗下会员增加学习力',$powerValue)){
                return false;
            }
            
        }else{
            $userinfo['parentids'] = trim($userinfo['parentids'],',');
            $parids = explode(',',$userinfo['parentids']);
            
            foreach($parids as $key=>$val){
                if($val == 0){
                    $topuserinfo = $user_model->where('id',$parids[1])->field('pid')->find();
                    if(false === $this->dedicationLog($topuserinfo['pid'],17,$obj_id,$content = '旗下会员增加学习力',$powerValue)){
                        
                        return false;
                    }
                }else{
                    if(false === $this->dedicationLog($val,17,$obj_id,$content = '旗下会员增加学习力',$powerValue)){
                        
                        return false;
                    }
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
        $sectionName = $section_model->where('id',$obj_id)->value('name');
        $content = '';
        $c_id = $section_model->where('id',$obj_id)->value('c_id');
        $advanced_id = $course_model->where('id',$c_id)->value('advanced_id');
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
        $pcredit =$preward * 0.05;
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
        if(false === $user_model->where('id',$pid)->setInc('bonus_learn_power',$pluspowerValue)) {
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
        $nextLevel = $userInfo['level'] + 1;
        // 当前用户等级升级
        if(false === $this->changeLevelOpt($uid,$nextLevel)) {
            Db::rollback();
            return false;
        }

        $pid = $user_model->where('id',$uid)->value('pid');
        // 上级用户等级升级
        if($pid != 0) {
            $pUserInfo = $user_model->where('id',$pid)->find();
            $pNextLevel = $pUserInfo['level'] + 1;
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
        if($level > 1) {
            $invite_people = $level_model->where('value',$level)->value('invite_people');
            // 邀请并认证的数量
             $userinfo = $user_model->field('invate_num')->where(['id'=>$uid,'is_auth'=>1])->find();
             $invitedCount = empty($userinfo['invate_num']) ? 0 : $userinfo['invate_num'];
            if($invitedCount + 1 > $invite_people || $invitedCount + 1 == $invite_people) {
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
        $nextStartLevel = $userInfo['start_level'] + 1;
        $startLevelInfo = $startLevel->where('value',$nextStartLevel)->find();
        $smallSqCount = $dedicationLog->where(['uid'=>$uid,'sq_type'=>2])->sum('value');;
        if(($userInfo['invate_num'] + 1 > $startLevelInfo['invite_people'] || $userInfo['invate_num'] + 1 == $startLevelInfo['invite_people']) && ($userInfo['dedication_value'] + 1 > $startLevelInfo['contribution'] || $userInfo['dedication_value'] + 1 == $startLevelInfo['contribution']) && ($smallSqCount + 1 > $startLevelInfo['small_sq'] || $smallSqCount + 1 == $startLevelInfo['small_sq'] )) {
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
    public function sendAdvanceCourse($uid = 0,$advanced_id = 0) {
        $course_model = new Course();
        $order_model = new \app\wxapp\model\Orders();
        $advanced_model = new Advanced();
        $courseList = $course_model->field('id,imgurl,name,advanced_id')->where('advanced_id',$advanced_id)->select();
        foreach ($courseList as $k=>$val) {
            $order_id = time().rand(1000,9999).$k;
            $advancedInfo = $advanced_model->field('deadline,value,reward')->where('id',$advanced_id)->find();
            $data = [
                'order_id'=>$order_id,
                'course_id'=>$val['id'],
                'advanced_id'=>$advanced_id,
                'uid'=>$uid,
                'effective'=>$advancedInfo['deadline'],
                'addtime'=>time(),
                'value'=>$advancedInfo['value'],
                'score'=>$advancedInfo['reward'],
                'status'=>1,
                'paytime'=>time(),
            ];
            if(!$order_model->where(['uid'=>$uid,'course_id'=>$val['id'],'status'=>1])->find()) {
                if(!$order_model->insert($data)) {
                    Db::rollback();
                    return false;
                }
                
                if(false === $this->sendCourseOpt($uid,$val['id'])) {
                    Db::rollback();
                    return false;
                }
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
    public function sendCourseOpt($uid = 0,$course_id = 0) {
        $user_model = new User();
        $advanced_model = new Advanced();
        $course_model = new Course();
        $advanced_id = $course_model->where('id',$course_id)->value('advanced_id');
        $learn_power = $advanced_model->where('id',$advanced_id)->value('learn_power');
        // 荣誉值
        $pid = $user_model->where('id',$uid)->value('pid');
        if($pid != 0) {
            //加成学习力
            $pluspowerres = $this->pulsLearnPowerLog($uid,2,$course_id);
                if(false === $pluspowerres){
                    Db::rollback();
                    return returnjson(1001,'','购买失败');
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
                    $content = "直接推荐一人";
                    if(false === $this->dedicationLog($pid,6,$course_id,$content)) {
                        Db::rollback();
                        return false;
                    }
                    // 实名直推好友
                    if($user_model->where(['id'=>$uid])->value('is_auth') == 1 ) {
                        if(false === $this->honorLog($pid,3)){
                            Db::rollback();
                            return false;
                        }
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
    
    //查看我是大社群还是小社群
    public function isMaxOrMin($uid){
        $user_model = new User();
        $myinfo = $user_model->where('id',$uid)->field('pid,parentids,invate_num')->find();
        $is_max = 0;
            //直系邀请
            $direct = $myinfo['invate_num'];
            //大社群人数
            $pnum = 0;
            //其他社群
            $other = 0;
            
        if($myinfo['pid']>0){
            $max_qun = $user_model->where(['pid'=>$myinfo['pid'],'is_auth'=>1])->order('invate_num desc')->select();
            //大社群id
            $maxids = '';
            if(!empty($max_qun)){
                foreach($max_qun as $mkey=>$mvalue){
                    if($mvalue['id'] == $uid){
                        $is_max = 1;
                        
                    }
                    $maxids .= $mvalue['id'].',';
                    $pnum += $mvalue['invate_num'];
                }
                //小社群
                $maids = trim($maxids,',');
                
                if($maids){
                   $other = $user_model->where(['pid'=>$myinfo['pid'],'is_auth'=>1])->where('id','not in',$maids)->sum('invate_num');
                }
                $min_dedication_value = $user_model->where(['pid'=>$myinfo['pid'],'is_auth'=>1])->where('id','not in',$maids)->sum('dedication_value');
                
                
            }
        }
        
        return array('direct'=>$direct,'pnum'=>$pnum,'other'=>$other,'is_max'=>$is_max,'min_dedication_value'=>$min_dedication_value);
    }
    //获取大社群和小社群的人数
    public function getShequnNum(){
        
    }
    //获取小社群的贡献值
    public function getMinShequnDication(){
        
    }

}