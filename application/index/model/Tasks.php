<?php
// +----------------------------------------------------------------------
// | 功能：
// +----------------------------------------------------------------------
// | 作者: 
// +----------------------------------------------------------------------
// | 日期：
// +----------------------------------------------------------------------
namespace app\index\model;
use think\Model;
use think\Db;
use app\index\model\TaskOptions;
/**
 * 作业
 * Class Advanced
 * @package app\index\model
 */
class Tasks extends Model{
    protected $table = 'task';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        $taskOption = new TaskOptions();
        foreach ($list as $k=>$val){
            if($val['type'] == 1) {
                $optionInfo = $taskOption->where(['task_id'=>$val['id'],'is_true'=>1])->find();
                if($optionInfo){
                    $list[$k]['options'] = $optionInfo['options'];
                }else{
                    $list[$k]['options'] = '未设置';
                }
            }else{  // 多选
                $optionInfo = $taskOption->where(['task_id'=>$val['id'],'is_true'=>1])->select();
                if($optionInfo) {
                    $optionArr = [];
                    foreach ($optionInfo as $val) {
                        $optionArr[] = $val['options'];
                    }
                    $optiones = implode(',',$optionArr);
                    $list[$k]['options'] = $optiones;
                }else{
                    $list[$k]['options'] = '未设置';
                }
            }
        }
        return page_data($total, $list);
    }
}
