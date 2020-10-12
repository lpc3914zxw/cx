<?php

namespace app\index\model;
use think\Model;
/*
 * 学分来源
 */
class CreditSource extends Model {
    protected $table = 'credit_source';
    public function getList($map = [],$is_export = 0) {
        $join = [
           ['user u','u.id=c.uid','left']
        ];
        $total = $this::alias('c')->join($join)->where($map)->count(1);
        if($is_export == 0) {
            $list = $this::all(function($query) use($map,$join) {
                $query->alias('c')->field('c.*,u.name,u.tel')->join($join)->where($map)->order('addtime desc')->limit(page());
            });
        }else{
            $list = $this::alias('c')->field('c.*,u.name,u.tel')->join($join)->where($map)->order('addtime desc')->select();
        }
        return page_data($total, $list);
    }
    public function get_score_sum($status,$contrast) {

        $sum = $this::where('status','=',1)->where(['type'=>['in',("$status")]])->where('score',$contrast,0)->sum('score');

        return $sum;
    }
    public function get_score__time_sum($begin,$end,$status,$contrast) {


        $sum = $this::where('status','=',1)->where(['type'=>['in',("$status")]])->where('score',$contrast,0)->where(['addtime'=>['between',[$begin,$end]]])->sum('score');

        return $sum;
    }
    public function get_score_sum_min($status,$contrast,$beginMonth) {

        $sum = $this::where('status','=',1)->where(['type'=>['in',("$status")]])->where('score',$contrast,0)->where('addtime','<',$beginMonth)->sum('score');

        return $sum;
    }
}


