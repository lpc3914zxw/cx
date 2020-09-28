<?php

namespace app\wxapp\model;

use think\Model;
/*
 * 学分来源
 */
class CreditSource extends Model {
    protected $table = 'credit_source';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
            //echo $query->getLastSql();
        });
        return page_data($total, $list);
    }

    /*
     * 获取学分明细
     * @param $where
     */
    public function getApiScoreLogList($where = [],$limit = '') {
        $data = $this::where($where)->limit($limit)->order('id desc')->select();;
        foreach ($data as $k=>$val) {
            $data[$k]['type'] = $this->getTypeName($val['type']);
            $data[$k]['addtime'] = date('Y/m/d H:i',$val['addtime']);
            if($val['score'] > 0 ) {
                $data[$k]['score'] = "+".$val['score'];
            }
            if($val['status'] == 0) {
                $data[$k]['statusTxt'] = '未完成';
            }else{
                $data[$k]['statusTxt'] = '已完成';
            }
        }
        $map = $where;
        $map['type'] = ['in',['4,5,8,11']];
        $where['type'] = ['in',['1,2,3,6,7,10']];
        $reciveMoney = $this::where($where)->sum('score');
        $jianMoney = $this::where($map)->sum('score');
        $data = ['data'=>$data,'reciveMoney'=>$reciveMoney,'jianMoney'=>$jianMoney];
        return returnjson('1000',$data,'获取成功');
    }

    public function getTypeName($type = 0) {
        if($type == 1) {  //学分来源 1 课堂作业 2导师专栏文章赞赏获得/扣除  3 兑入 4 兑出 5 课程购买
            $type = '课堂作业';
        }else if($type == 2) {
            $type = '文章赞赏';
        }else if($type == 3) {
            $type = '兑入';
        }else if($type == 4) {
            $type = '兑出';
        }else if($type == 5) {
            $type = '课程购买';
        }else if($type == 6) {
            $type = '算力银行奖励';
        }else if($type == 7) {
            $type = '平台充值';
        }else if($type == 8) {
            $type = '平台扣除';
        }else if($type == 10) {
            $type = '置换手续费分红';
        }else if($type == 11) {
            $type = '开通置换手续费';
        }
        return $type;
    }
}


