<?php

namespace app\index\model;
use think\Model;

/*
 * 广告列表
 * Class Advanced
 * @package app\index\model
 */
class AdverChild extends Model{
    protected $table = 'knowledge_adver_children';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }

    /*
     * 批量添加
     * @author Steed
     * @param $data
     * @return array|false
     */
    public function addChilds($data) {
        return $this::isUpdate(false)->saveAll($data);
    }

    /*
     * 删除
     * @author Steed
     * @param array $map
     * @return int
     */
    public function delChilds($map = []) {
        return $this::where($map)->delete();
    }

}
