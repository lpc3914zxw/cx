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
 * 海报
 * Class Advanced
 * @package app\index\model
 */
class PosterTemp extends Model{
    protected $table = 'poster_temp';
    public function getList() {
        $total = $this::count(1);
        $list = $this::all(function($query) {
            $query->limit(page());
        });
        return page_data($total, $list);
    }
}
