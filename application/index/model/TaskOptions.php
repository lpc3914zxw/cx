<?php
namespace app\index\model;
use think\Model;

/*
 * 作业答案
 */
class TaskOptions extends Model {
    protected $table = 'task_option';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->order(sort_order())->limit(page());
        });
        foreach ($list as $k=>$val){
            if($val['is_true'] == 0){
                $list[$k]['is_true'] = '错误';
            }else{
                $list[$k]['is_true'] = '正确';
            }
        }
        return page_data($total, $list);
    }
}