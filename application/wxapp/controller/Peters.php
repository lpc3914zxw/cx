<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-09-28
 * Time: 18:53
 */

namespace app\wxapp\controller;
/*申请堂主记录表*/

use app\service\PetersService;
use think\Db;
class Peters extends Base
{
    /*
    * 课程分类
    */
    public function apply() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }

        
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $county = input('county');
        $is_apply = Db::name('region')->where('id',$county)->value('is_apply');
        if($is_apply==1){
             return returnjson(1001,'','该地区已被申请！');
        }
        $is_has = Db::name('peters_log')->where('uid',$this->uid)->where(['status'=>['in',[0,1]]])->field('id,status')->find();
        if($is_has){
            if($is_has['status']==1){
                return returnjson(1001,'','您已成为堂主，不需申请');
            }else if($is_has['status']==0){
                return returnjson(1001,'','您已成提交申请，请勿重复提交');
            }
            
        }
        $peters_num = Db::name('system')->where('id',1)->value('peters_num');
        //$user_num = Db::name('user')->where('is_peters',1)->where('peters_expire_time','>',time())->count();
        $user_num = Db::name('user')->where('is_peters',1)->count();
        if($peters_num<=$user_num){
            return returnjson(1001,'','第一批开放名额'.$peters_num.'名已经满额，请留意下一次开放时间！');
        }
        $res=PetersService::Save(input(),$this->uid);
        return returnjson($res['code'],$res['data'],$res['msg']);

    }
   public function petersset() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $peters_num = Db::name('system')->where('id',1)->value('peters_num');
        //$user_num = Db::name('user')->where('is_peters',1)->where('peters_expire_time','>',time())->count();
        $user_num = Db::name('user')->where('is_peters',1)->count();
        if($peters_num<=$user_num){
            return returnjson(1001,'','第一批开放名额'.$peters_num.'名已经满额，请留意下一次开放时间！');
        }
        $petersSet_model = new \app\index\model\PetersSet();
        $res['equity']= $petersSet_model->where('type','=',1)->where('is_open','=',1)->order('sort')->column('content');
        //$res=PetersService::Save(input(),$this->uid);equity
        $res['condition']= '达到中产或以上级别即可！';
        
        $myinfo = Db::name('user')->where('id',$this->uid)->field('dedication_value,start_level')->find();
        $res['is_allow']=0;
        if($myinfo['start_level']>=1){
            $res['is_allow']=1;
        }
       // $res['is_allow']=1;
        if($myinfo['start_level']>0){
            $res['level'] = Db::name('start_level')->where('value',$myinfo['start_level'])->value('name');
        }else{
            $res['level'] = '无等级';
        }
        
        $res['proxyLinkUrl']='https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/peters_contert.html';
        return returnjson(1000,$res,'获取成功');

    }
    public function myPeters() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        $petersSet_model = new \app\index\model\PetersSet();
        $equity= $petersSet_model->where('type','=',1)->where('is_open','=',1)->order('sort')->column('content');
        $myinfo = Db::name('user')->where('id',$this->uid)->field('headimg,name,start_level,level,peters_province,peters_city,peters_area,is_peters,peterstime,peters_expire_time')->find();
        if($myinfo['is_peters']==0){
             return returnjson(1001,'','您还不是财学堂堂主请先去申请！');
        }
        $myinfo['time'] = date('Y.m.d',$myinfo['peterstime']).'-'.date('Y.m.d',$myinfo['peters_expire_time']);
        $myinfo['equity'] = $equity;
        return returnjson(1000,$myinfo,'获取成功');

    }
}
