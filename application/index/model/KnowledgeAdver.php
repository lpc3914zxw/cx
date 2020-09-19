<?php

namespace app\index\model;
use think\Model;

/*
 * 涨知识广告
 * Class Advanced
 * @package app\index\model
 */
class KnowledgeAdver extends Model{
    protected $table = 'knowledge_adver';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }

    /**
     * 添加
     * @author Steed
     * @param $data
     * @return false|int
     */
    public function addAdv($data) {
        if ($this::isUpdate(false)->save($data)) return $this->data['id'];
        return 0;
    }

    /**
     * 更新商品信息
     * @author Steed
     * @param $data
     * @param $map
     * @return false|int
     */
    public function updateAdv($data, $map) {
        return $this::isUpdate(true)->save($data, $map);
    }

}
