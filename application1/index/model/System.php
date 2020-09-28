<?php

namespace app\index\model;

use think\Model;

class System extends Model {
    protected $table = 'system';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }

}