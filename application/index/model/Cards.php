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

/**
 * 才学堂
 * Class Advertisment
 * @package app\index\model
 */
class Cards extends Model{
    protected $table = 'cards';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }
}
