<?php

namespace app\index\model;
use think\Model;

/*
 * 广告列表
 * Class Advanced
 * @package app\index\model
 */
class Notice extends Model{
    protected $table = 'notice';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }

    

}
