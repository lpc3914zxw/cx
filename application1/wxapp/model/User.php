<?php

namespace app\wxapp\model;

use think\Model;
use think\Db;
class User extends Model {
    protected $table = 'user';

    public function getApiUserList($where =[],$limit = '1,10') {
        $list = $this::field('headimg,name,level,is_auth,dedication_value')->where($where)->order('id asc')->limit($limit)->select();
        foreach ($list as $k=>$val) {
            switch ($val['is_auth']) {
                case 0:
                    $is_auth = '未实名';
                    break;
                case  1:
                    $is_auth = '已实名';
                    break;
                default:
                    $is_auth = '未知状态';
                    break;
            }
            $list[$k]['is_auth'] = $is_auth;
            $list[$k]['level'] = "LV".$val['level'];
        }
        return $list;
    }

    /*
     * 获取排行榜信息
     */
    public function getRankSetInfo($uid = 0,$type = 1) {
        if($type == 'dedication') {
            $userInfo = $this::field('id,dedication_value,like_num,name,headimg')->where('id',$uid)->find();
            $count = $this::where(['dedication_value'=>['egt',$userInfo['dedication_value']]])->count();
            $count += 1;
            $userInfo['count'] = $count;
            $userInfo['value'] = $userInfo['dedication_value'];
            unset($userInfo['dedication_value']);
        }else{
            $userInfo = $this::field('id,learning_power,like_num,name,headimg')->where('id',$uid)->find();
            $count = $this::where(['learning_power'=>['egt',$userInfo['learning_power']]])->count();
            $count += 1;
            $userInfo['count'] = $count;
            $userInfo['value'] = $userInfo['learning_power'];
            unset($userInfo['learning_power']);
        }
        $userLike = new UserLike();
        if($userLike->where(['uid'=>$uid,'like_id'=>$uid])->find()){
            $userInfo['is_like'] = 1;
        }else{
            $userInfo['is_like'] = 0;
        }
        return $userInfo;
    }

    /*
     * 排行榜
     * @param $type
     * @param $limit
     */
   public function getRankList($type,$limit,$uid) {
        $userLike = new UserLike();
        	$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $wid['addtime'] = array('between',$beginToday.','.$endToday);
     $wid['user.id'] = array('gt',393);
        if($type == 'dedication') {
           
            //$wid['id'] = array('gt',393);
            
            $data = Db::name('dedication_log')->join('user','user.id=dedication_log.uid')->field('uid,sum(value) as value')->where($wid)->group('uid')->order('sum(value)','desc')->limit($limit)->select();
            
            foreach ($data as $k=>$val) {
                $data[$k] = Db::name('user')->field('id,headimg,name,like_num')->where('id',$val['uid'])->find();
                $data[$k]['value'] = $val['value'];
                if($userLike->where(['uid'=>$uid,'like_id'=>$val['uid']])->find()){
                    $data[$k]['is_like'] = 1;
                  	$data[$k]['is_like2'] = 1;
                }else{
                    $data[$k]['is_like'] = 0;
                  $data[$k]['is_like2'] = 0;
                }
            }
        }else {
            $field = 'id,learning_power,name,headimg,like_num';
          	//$wid['id'] = array('gt',393); 
          	$data = Db::name('learning_power_log')->join('user','user.id=learning_power_log.uid')->field('uid,sum(value) as value')->where($wid)->group('uid')->order('sum(value)','desc')->limit($limit)->select();
            //return $data;
            foreach ($data as $k=>$val) {
                
                $data[$k] = Db::name('user')->field('id,headimg,name,like_num')->where('id',$val['uid'])->find();
                $data[$k]['value'] = $val['value'];
                if($userLike->where(['uid'=>$uid,'like_id'=>$val['uid']])->find()){
                    $data[$k]['is_like'] = 1;
                  	$data[$k]['is_like2'] = 1;
                }else{
                    $data[$k]['is_like'] = 0;
                  $data[$k]['is_like2'] = 0;
                }
            }
        }

        return $data;
    }
    
    /*
     * 获取龙虎排行榜信息
     */
    public function getLongRankSetInfo($uid = 0,$type = 1) {
        
            $userInfo = $this::field('id,dedication_value,long_like_num as like_num,name,headimg,power_dedication_small')->where('id',$uid)->find();
            $count = $this::where(['dedication_value'=>['egt',$userInfo['dedication_value']]])->count();
            $count += 1;
            $userInfo['count'] = $count;
            $userInfo['value'] = $userInfo['power_dedication_small'];
            unset($userInfo['dedication_value']);
        
        $userLike = new LongUserLike();
        if($userLike->where(['uid'=>$uid,'like_id'=>$uid])->find()){
            $userInfo['is_like'] = 1;
        }else{
            $userInfo['is_like'] = 0;
        }
        return $userInfo;
    }
    /*
     * 龙虎排行榜
     * @param $type
     * @param $limit
     */
   public function getLongRankList($type,$limit,$uid) {
        $userLike = new LongUserLike();
        	$beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            //$wid['addtime'] = array('between',$beginToday.','.$endToday);
     $wid['id'] = array('gt',393);
        
           
            //$wid['id'] = array('gt',393);
            
            $data = Db::name('user')->field('id,power_dedication_small')->where($wid)->order('power_dedication_small','desc')->limit($limit)->select();
            
            foreach ($data as $k=>$val) {
                $data[$k] = Db::name('user')->field('id,headimg,name,long_like_num as like_num')->where('id',$val['id'])->find();
                $data[$k]['value'] = round($val['power_dedication_small'],2);
                if($userLike->where(['uid'=>$uid,'like_id'=>$val['id']])->find()){
                    $data[$k]['is_like'] = 1;
                  	$data[$k]['is_like2'] = 1;
                }else{
                    $data[$k]['is_like'] = 0;
                  $data[$k]['is_like2'] = 0;
                }
            }
        

        return $data;
    }
  
  
}