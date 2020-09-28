<?php

namespace app\index\model;

use think\Model;
use think\Db;
class FeedbackCategory extends Model {
    protected $table = 'feedback_category';
    public function getList($map = []) {
        if($map = []){
            return page_data(0, []);
        }else{
            $total = $this::where($map)->count(1);
            $list = $this::all(function($query) use($map) {
                $query->where($map)->limit(page());
            });
            return page_data($total, $list);
        }
    }

    /*
     * 权限列表
     * @param array $mp
     */
    public function getPowerList($map = []){
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }
}