<?php
namespace app\index\model;
use think\Model;

/*
 * 贡献值记录中心
 */
class DedicationLog extends Model{
    protected $table = 'dedication_log';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }
    
    public function get_list($map = []) {
        $join = [
           ['user u','u.id=d.uid','left']
        ];
        $total = $this::alias('d')->join($join)->where($map)->count(1);
        $list = $this::all(function($query) use($map,$join) {
            $query->alias('d')->join($join)->where($map)->field('d.*,u.name,u.tel')->order('addtime desc')->limit(page());
        });
        return page_data($total, $list);
    }
}
