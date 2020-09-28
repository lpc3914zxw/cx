<?php

namespace app\tutor\model;

use think\Model;

class Menu extends Model {
    protected $table = 'tutor_menu';

    /**
     * 获取所有的菜单
     * @author Steed
     * @return false|static[]
     */
    public function getMenu() {
        $list = $this::all(function($query) {
            $query->where(['is_delete'=>0,'type'=>1])->order('sort asc');
        });
        return $list;
    }
    /**
     * 分页获取数据
     * @author Steed
     * @param array $map
     * @return array
     */
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->order(sort_order())->limit(page());
        });
        return page_data($total, $list);
    }

    /**
     * 获取单个菜单
     * @author Steed
     * @param array $map
     * @return static
     */
    public function findMenu($map = []) {
        $menu = $this::get(function($query) use($map) {
            $query->where($map);
        });
        return $menu;
    }
}