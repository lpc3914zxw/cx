<?php
namespace app\wxapp\controller;
use app\common\Common;
use app\index\model\Advanced;
use app\wxapp\controller\Base;
use app\index\model\Course;
use app\wxapp\model\Colliers;
use app\wxapp\model\StartLevel;
use app\index\model\Sectiones;
use app\wxapp\model\CourseLearnLog;
use think\Db;
class Orders extends Base {

    /*
     * 下单
     * @param int $course_id
     * @param string $token
     */
    public function addOrder($course_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }

        $user_model = new \app\wxapp\model\User();
        $order_model = new \app\wxapp\model\Orders();
        $course_model = new Course();
        $advanced_model = new Advanced();
      $userstatus = $user_model->where('id',$this->uid)->value('is_frozen');
      if($userstatus == 1){
      	return returnjson(1001,'','账号异常');
      }
      //校验是否已实名
        $userIsauth = $user_model->where('id',$this->uid)->value('is_auth');
        if($userIsauth!=1){
            return returnjson(1001,'','实名认证后才能兑换');
        }
        // 查询当前所学进阶
        $advanced_id = $course_model->where('id',$course_id)->value('advanced_id');
        $advancedInfo = $advanced_model->field('type,studying_num,reward,value,deadline,pay_type,learn_power')->where(['id'=>$advanced_id])->find();
        $courseInfo = $course_model->field('imgurl,name,advanced_id,stock')->where('id',$course_id)->find();
        if(!empty($courseInfo['stock'])){
             $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $countwhere['status'] = array('neq',0);
            $countwhere['paytime'] = array('between',$beginToday.','.$endToday);
            $kcount = Db::name('order')->where($countwhere)->count();
            if($courseInfo['stock']<=$kcount){
                return returnjson(1001,'','今天的课程已兑完');
            }
        }
        $score = $user_model->where('id',$this->uid)->value('score');
        if($advancedInfo['type'] != 3) {
            // 判断当前可以学几个课程
            $preAdvanced = $advanced_model->field('id,studying_num,chapter_count')->where(['id'=>['lt',$advanced_id]])->order('sort')->find();
            if($preAdvanced) {  // 查看上一个进阶的课程是否学完
                // 上一进阶读取完的数量
                $preLearnedCount = $order_model->where(['advanced_id'=>$preAdvanced['id'],'status'=>3])->count();
                if($preAdvanced['chapter_count'] != $preLearnedCount) {
                    //return returnjson(1001,'','请先学完上一个课程');
                }
            }else{
                $nowLearnedCount = $order_model->where(['advanced_id'=>$advanced_id,'status'=>3])->count();
                if($nowLearnedCount == $advancedInfo['studying_num']) {
                    return returnjson(1001,'','该进阶最多同时学习'.$advancedInfo['studying_num'].'课程');
                }
            }
        }
        $status = '1,2,3';
        if($order_model->where(['course_id'=>$course_id,'uid'=>$this->uid,'status'=>['in',($status)]])->find()){
            return returnjson(1001,'','已购买过，请勿重复购买');
        }
        $order_id = time().rand(1000,9999);
        $data = [
            'order_id'=>$order_id,
            'course_id'=>$course_id,
            'uid'=>$this->uid,
            'status'=>0,
            'value'=>$advancedInfo['value'],
            'effective'=>$advancedInfo['deadline'],
            'score'=>$advancedInfo['reward'],
            'advanced_id'=>$courseInfo['advanced_id'],
            'addtime'=>time(),
            'paytime'=>time(),
        ];
        if(!$order_model->insert($data)) {
            return returnjson(1001,'','下单失败');
        }
        if($advancedInfo['pay_type'] == 1) {  // 1 学分支付 2 支付宝支付  3 微信支付
            $courseInfo['value'] = $advancedInfo['value']."学分";
        }else {
            $courseInfo['value'] = $advancedInfo['value']."元";
        }
        $courseInfo['deadline'] = $advancedInfo['deadline'];
        $courseInfo['order_id'] = $order_id;
        $courseInfo['score'] = $score;
        $pay_types = explode(',',$advancedInfo['pay_type']);
        $courseInfo['is_score'] = 0;
        $courseInfo['is_alipay'] = 0;
        $courseInfo['is_wxpay'] = 0;
        foreach ($pay_types as $val) {
            if($val == 1) {
                $courseInfo['is_score'] = 1;
            }else if($val == 2) {
                $courseInfo['is_alipay'] = 1;
            }else if($val == 3) {
                $courseInfo['is_alipay'] = 1;
            }
        }
        $courseInfo['paytype'] = $pay_types;
        return returnjson(1000,$courseInfo,'下单成功');
    }

    /*
     * 确认支付
     * @param $order_id  订单号   $pay_type 支付方式   1 学分兑换  2 支付宝支付  3 微信支付
     */
    public function submitPayData($order_id = '',$pay_type = 1) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'','该设备在其他地方登录');
        }
      	//return returnjson(1001,'','太晚了，早点休息哦');
        $order_model = new \app\wxapp\model\Orders();
        $orderInfo = $order_model->field('value,status')->where('order_id',$order_id)->find();
        if($orderInfo['status'] != 0) {
            return returnjson(1001,'','该订单已支付');
        }
        $user_model = new \app\wxapp\model\User();
        $advanced_model = new Advanced();
        $course_model = new Course();
        Db::startTrans();
        $common = new Common();
       $userstatus = $user_model->where('id',$this->uid)->value('is_frozen');
        if($userstatus == 1){
          return returnjson(1001,'','账号异常');
        }
      //校验是否已实名
        $userIsauth = $user_model->where('id',$this->uid)->value('is_auth');
        if($userIsauth!=1){
            return returnjson(1001,'','实名认证后才能兑换');
        }
      $course_id = $order_model->where('order_id',$order_id)->value('course_id');
      	$isbuy = $order_model->where('course_id',$course_id)->where('status','neq',0)->where('uid',$this->uid)->count();
      if($this->uid == 392){
      if($isbuy!=0){
      	return returnjson(1001,'','您已拥有此课程');
      }
      }

      	if($course_id == 2){
        	return returnjson(1001,'','此课程无需购买,实名支付并认证后赠送');
        }
        if($pay_type == 1) {   // 学分兑换

            $advanced_id = $course_model->where('id',$course_id)->value('advanced_id');
            $learn_power = $advanced_model->where('id',$advanced_id)->value('learn_power');
            if(!$order_model->where('order_id',$order_id)->update(['pay_type'=>$pay_type,'status'=>1])) {
                return returnjson(1001,'','购买失败');
            }
            $userScore = $user_model->where('id',$this->uid)->value('score');

            if($orderInfo['value'] != 0) {
                if(floatval($orderInfo['value']) > floatval($userScore)) {
                    return returnjson(1001,'','学分余额不足');
                }
            }
            $course_model->where('id',$course_id)->setInc('people_num');
            if(floatval($orderInfo['value']) > 0) {
                if(!$user_model->where('id',$this->uid)->setDec('score',$orderInfo['value'])) {
                    Db::rollback();
                    return returnjson(1001,'','购买失败');
                }
                $courseName = $course_model->where('id',$course_id)->value('name');
                $data =[
                    'type'=>5,'uid'=>$this->uid,'pay_type'=>3,'score'=>"-".$orderInfo['value'],
                    'status'=>1,'note'=>"购买课程《".$courseName.'》','value'=>$course_id,'addtime'=>time()
                ];
                if(false === $common->creditSource($data,$this->uid)){
                    Db::rollback();
                    return returnjson(1001,'','购买失败');
                }
                // 荣誉值
                if(false === $common->honorLog($this->uid,1,$course_id,$orderInfo['value'])) {
                    Db::rollback();
                    return returnjson(1001,'','购买失败');
                }
            }

            if(false === $common->learnPowerLog($this->uid,2,$course_id)) {
                Db::rollback();
                return returnjson(1001,'','购买失败');
            }

            $pid = $user_model->where('id',$this->uid)->value('pid');
            if($pid != 0) {
                //给上级加成学习力并且把加入加成学力任务
                $pluspowerres = $common->pulsLearnPowerLog($this->uid,2,$course_id);
                if(false === $pluspowerres){
                    Db::rollback();
                    return returnjson(1001,'','购买失败');
                }
                //$bonus_learn_power = $learn_power * 0.05;
                 //   if(false === $user_model->where('id',$pid)->setInc('bonus_learn_power',$bonus_learn_power)){
                 //   Db::rollback();
                 //   return returnjson(1001,'','购买失败');
                //}

                /*$parent_ = $user_model->field('parentids')->where(['pid'=>$pid])->find();
                if($parent_['parentids'] == '0,'){
                    $myshequn = $pid;
                }else{
                   $myparentids= $user_model->field('parentids')->where(['parentids'=>['like',"%,{$pid},%"]])->find();
                   $myparentids = explode(',',$myparentids['parentids']);
                   $myshequn = $myparentids[1];
                }*/

                // 大社群新增一个学习力
                /*$maxShequn = $user_model->field('id')->where(['parentids'=>'0,'])->order('invate_num asc')->find();
                if($order_model->where(['status'=>1,'uid'=>$this->uid])->count() == 1) {
                    // 如果初次购买，那么将上级用户的邀请人数加1
                    if(false === $user_model->where('id',$pid)->setInc('invate_num',1)) {
                        Db::rollback();
                        return returnjson(1001,'','购买失败');
                    }
                }*/
                // 直推相关结算是否已经结算
                if($user_model->where('id',$this->uid)->value('is_zt') === 0) {
                    //  条件 直接推荐，学习力大于一
                  	/*
                    if($user_model->where(['id'=>$pid])->value('learning_power') >= 1 ) {
                        $content = "直接推荐一人";
                        if(false === $common->dedicationLog($pid,6,$course_id,$content)) {
                            Db::rollback();
                            return returnjson(1001,'','购买失败');
                        }
                        // 实名直推好友
                        if($user_model->where(['id'=>$this->uid])->value('is_auth') == 1 ) {
                            if(false === $common->honorLog($pid,3,0)){
                                Db::rollback();
                                return returnjson(1001,'','购买失败');
                            }
                        }
                    }*/
                    // 大社群新增一人-- 贡献值明细
                    /*$maxShequn = $user_model->field('id')->where(['parentids'=>'0,'])->order('invate_num asc')->find();
                    if($myshequn == $maxShequn['id']) {
                        $content = "大社群新增一人";
                        if(false === $common->dedicationLog($maxShequn['id'],9,$course_id,$content)) {
                            Db::rollback();
                            return returnjson(1001,'','购买失败4');
                        }
                        $user_model->where(['id'=>$maxShequn['id']])->setInc('invate_num');
                        //大社群新增一个学习力
                        if(false === $common->bonusDedicationLog($maxShequn['id'],11,$learn_power,$course_id,'big')) {
                            Db::rollback();
                            return returnjson(1001,'','购买失败3');
                        }
                        $user_model->where(['id'=>$maxShequn['id']])->setInc('invate_num');

                    }else{// 小社群新增一人
                        $otherShequnList = $user_model->field('id')->where(['parentids'=>'0,','id'=>['neq',$maxShequn['id']]])->select();
                        foreach ($otherShequnList as $value) {
                            $content = "小社群新增一人";
                            if(false === $common->dedicationLog($value['id'],10,$course_id,$content)) {
                                Db::rollback();
                                return returnjson(1001,'','购买失败');
                            }
                            // 小社群新增一个学习力 获得的贡献值
                            if(false === $common->bonusDedicationLog($value['id'],12,$learn_power,$course_id,'small')) {
                                Db::rollback();
                                return returnjson(1001,'','购买失败');
                            }
                        }
                        $user_model->where(['id'=>$myshequn])->setInc('invate_num');
                    }*/
                    $user_model->where(['id'=>$this->uid])->setInc('is_zt');
                }
            }
            // 解锁第一节课
            $this->unlockCourse($course_id,$this->uid);
            Db::commit();
            return returnjson(1000,'','购买成功');
        }else if($pay_type == 2) {

        }else if($pay_type == 3) {

        }else{
            return returnjson(1001,'','购买方式错误');
        }
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
        $orderCount = $order_model->where(['course_id'=>$course_id,'uid'=>$this->uid,'status'=>1])->count();

        if($orderCount > 0) {
            $paytime = $order_model->where(['uid'=>$uid,'course_id'=>$course_id,'status'=>1])->value('paytime');
            $days = ceil((time() - $paytime)/ 86400) ; // 学习该课程的天数
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
          //$sectionList = $section_model->where(['c_id'=>$course_id,'is_delete'=>0])->order('sort asc')->limit($learnedCount,$unlockNum)->select();
            $sectionList = $section_model->where(['c_id'=>$course_id,'is_delete'=>0])->order('sort asc')->limit(0,$unlockCourseCount)->select();
            $learnData = [];
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

    /*
     * 人脸识别支付成功
     */
    public function paySuccess($result = '') {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
      if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $result = input('result');
        $res = json_decode($result,true);
        $type = input('type');
        $is_face = input('is_face');
        
        if(!isset($is_face)){
            $is_face=1;
        }else{
            $is_face=0;
        }
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        file_put_contents($documentRoot.'/log_666.txt',print_r($result,true),FILE_APPEND);

        if(input('pay_type')==1){
          	if($type == 2){//微信支付
          	    if($is_face == 0){//是否人脸支付 1
                    $out_trade_no = input('result');
                    $res = Db::name('order')->where('out_trade_no',$out_trade_no)->find();

                    //return returnjson(1000,'','支付成功');
                    if($res['status']==1){//
                        return returnjson(1000,'','支付成功');
                    }else{
                        return returnjson(1001,'','支付失败');
                    }
                }else{
                    $out_trade_no = input('out_trade_no');
                    $res = Db::name('face_order')->where('out_trade_no',$out_trade_no)->find();
                    //return returnjson(1000,'','支付成功');
                    if($res['status']==1){
                        return returnjson(1000,'','支付成功');
                    }else{
                        return returnjson(1001,'','支付失败');
                    }
                }

            }elseif($type == 1){
          	    if($is_face == 0){
                    if(!empty($res['alipay_trade_app_pay_response'])){

                        if($res['alipay_trade_app_pay_response']['code'] == '10000'){
                            Db::name('order')->where(['out_trade_no'=>$res['alipay_trade_app_pay_response']['out_trade_no']])->update(['status'=>1,'paytime'=>time(),'paytype'=>1]);
                            return returnjson(1000,'','支付成功');
                        }
                    }else{
                        return returnjson(1001,'','支付失败');
                    }
                }else{
                    if(!empty($res['alipay_trade_app_pay_response'])){

                        if($res['alipay_trade_app_pay_response']['code'] == '10000'){
                            Db::name('face_order')->where(['out_trade_no'=>$res['alipay_trade_app_pay_response']['out_trade_no']])->update(['status'=>1,'paytime'=>time(),'paytype'=>1]);
                            return returnjson(1000,'','支付成功');
                        }
                    }else{
                        return returnjson(1001,'','支付失败');
                    }
                }



            }else{
                return returnjson(1001,'','参数错误');
            }

        }else{
            if(!empty($res['orderNo'])){

                    Db::name('face_order')->where(['out_trade_no'=>$res['orderNo']])->update(['status'=>1,'paytime'=>time(),'paytype'=>1]);
                    $documentRoot = $_SERVER['DOCUMENT_ROOT'];

                    return returnjson(1000,'','支付成功');

            }
        }




        return returnjson(1000,'','已完成认证');
    }
}

