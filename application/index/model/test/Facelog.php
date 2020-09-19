<?php

namespace app\index\model;
use think\Model;
/*
 * 学分来源
 */
class Facelog extends Model {
    protected $table = 'face_log';
    public function getList($map = []) {
        $total = $this::alias('c')->where($map['c'])->count(1);
        $join = [
           ['user u','u.id=c.uid','left']
        ];
        $m = $map['c'];
        $a = $map['u'];
        $list = $this::all(function($query) use($m,$a,$join) {
            $query->alias('c')->join($join)->where($m)->whereOr($a)->order('addtime desc')->field('c.*,u.name,u.tel')->limit(page());
            //echo $query->getLastSql();
        });
        return page_data($total, $list);
    }

   
}


