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
        $petersSet_model = new \app\index\model\PetersSet();
        $res['equity']= $petersSet_model->where('type','=',1)->where('is_open','=',1)->order('sort')->column('content');
        //$res=PetersService::Save(input(),$this->uid);equity
        $res['condition']= $petersSet_model->where('type','=',2)->where('is_open','=',1)->value('content');
        $res['level']= $petersSet_model->where('type','=',3)->where('is_open','=',1)->value('content');
        $res['is_allow']=1;
        return returnjson(1000,$res,'获取成功');

    }
}
