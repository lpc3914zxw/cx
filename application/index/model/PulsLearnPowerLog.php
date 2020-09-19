<?php

namespace app\index\model;

use think\Model;

class PulsLearnPowerLog extends Model {
    protected $table = 'puls_learning_power';

    public function get_list($map = [],$is_export = 0) {
        $join = [
           ['user u','u.id=l.uid','left']
        ];
        $total = $this::alias('l')->join($join)->where($map)->count(1);
        if($is_export == 0) {
            $list = $this::all(function($query) use($map,$join) {
                $query->alias('l')->field('l.*,u.name,u.tel')->join($join)->where($map)->order('addtime desc')->limit(page());
            });
        }else{
            $list = $this::alias('l')->field('l.*,u.name,u.tel')->join($join)->where($map)->order('addtime desc')->select();
        }

        return page_data($total, $list);
    }
}