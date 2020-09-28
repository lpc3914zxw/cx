<?php
namespace app\index\model;
use think\Model;
class Feedback extends Model {
    protected $table = 'feedback';
    public function getList($map = []) {
        $total = $this::alias('f')->where($map)->count(1);
        $join = [
           ['feedback_category fc','f.categroy=fc.id','left']
        ];
        $list = $this::all(function($query) use($map,$join) {
            $query->alias('f')->where($map)->join($join)->order('f.addtime desc')->field('f.*,fc.id as fid,fc.name as fname')->limit(page());
        });
        return page_data($total, $list);
    }
}