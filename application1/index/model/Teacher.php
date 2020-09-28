<?php
namespace app\index\model;
use think\Model;
class Teacher extends Model {
    protected $table = 'teacher';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->order('addtime desc')->limit(page());
        });
        return page_data($total, $list);
    }
}