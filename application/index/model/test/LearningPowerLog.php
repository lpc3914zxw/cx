<?php
namespace app\index\model;
use think\Model;

/*
 * 贡献值记录中心
 */
class LearningPowerLog extends Model{
    protected $table = 'learning_power_log';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }
    
    public function get_list($map = []) {
        $total = $this::alias('l')->where($map['l'])->count(1);
        $join = [
           ['user u','u.id=l.uid','left']
        ];
        $m = $map['l'];
        $a = $map['u'];
        $list = $this::all(function($query) use($m,$a,$join) {
            $query->alias('l')->join($join)->where($m)->whereOr($a)->limit(page());
        });
        return page_data($total, $list);
    }
}
