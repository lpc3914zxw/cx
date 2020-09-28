<?php
namespace app\index\model;
use think\Model;
use think\Db;
use app\index\model\Advanced;
class StartLevel extends Model {
    protected $table = 'start_level';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        $advanced_model = new Advanced();
        foreach ($list as $k=>$val) {
            $courseinfo = $advanced_model->where('id',$val['advanced_id'])->find();
            $list[$k]['course'] = $courseinfo['name'];
        }
        return page_data($total, $list);
    }
}