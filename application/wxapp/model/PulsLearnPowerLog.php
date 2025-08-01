<?php

namespace app\wxapp\model;

use think\Model;

class PulsLearnPowerLog extends Model {
    protected $table = 'puls_learning_power';

    /*
    * 获取学习力明细
    * @param $where
    */
    public function getApiLearnPowerList($where = [],$limit = '') {
        $data = $this::field('id,type,addtime,status,value')->where($where)->limit($limit)->order('addtime desc')->select();;
        foreach ($data as $k=>$val) {
            $data[$k]['type'] = $this->getTypeName($val['type']);
            $data[$k]['addtime'] = date('H:i',$val['addtime']).' '.date('Y/m',$val['addtime']);
            if($val['status'] == 0) {
                $data[$k]['statusType'] = '失效';
            }else{
                $data[$k]['statusType'] = '收入';
            }
        }
        $map = $where;
        $where['status'] = 1;
        $map['status'] = 0;
        $reciveMoney = $this::where($where)->limit($limit)->sum('value');
        $noUserMoney = $this::where($map)->limit($limit)->sum('value');
        $data = ['data'=>$data,'reciveMoney'=>$reciveMoney,'noUserMoney'=>$noUserMoney];
        return returnjson('1000',$data,'获取成功');
    }

    public function getTypeName($type = 0) {
        if($type == 1) {  //学分来源 1 课堂作业 2导师专栏文章赞赏获得/扣除  3 兑入 4 兑出 5 课程购买
            $type = '学习课程';
        }else if($type == 2) {
            $type = '兑换课程';
        }
        return $type;
    }
}