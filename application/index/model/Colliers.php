<?php
namespace app\index\model;
use think\Model;
class Colliers extends Model {
    protected $table = 'colliers';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }
}