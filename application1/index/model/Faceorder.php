<?php

namespace app\index\model;
use think\Model;
/*
 * 学分来源
 */
class Faceorder extends Model {
    protected $table = 'face_order';
    public function getList($map = [],$is_export = 0) {
        $join = [
           ['user u','u.id=f.uid','left']
        ];
        $total = $this::alias('f')->join($join)->where($map)->count(1);
        $field = ['f.*,u.name,u.tel'];
        if($is_export == 0) {
            $list = $this::all(function($query) use($map,$field,$join) {
                $query->alias('f')->field($field)->join($join)->where($map)->order('paytime desc')->limit(page());
            });
        }else{
            $list = $this::alias('f')->field($field)->join($join)->where($map)->order('paytime desc')->select();
        }
        return page_data($total, $list);
    }

   
}


