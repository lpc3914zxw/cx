<?php

namespace app\index\model;

use think\Model;
use think\Db;
class Menu extends Model {
    protected $table = 'menu';

    /*
     * 获取对应角色的菜单
     * @author Steed
     * @group_id  用户组id
     * @return false|static[]
     */
    public function getMenu($group_id) {
        // 身份
        $rulesInfo = Db::table('auth_group')->field('rules')->where('id',$group_id)->find();
        if($rulesInfo) {
            $rules = $rulesInfo['rules'];
            $where = ['id'=>['in',($rules)],'is_delete'=>0,'type'=>1];
        }else{
            $where['id'] = ['is_delete'=>0,'type'=>1];
        }
        $list = $this::all(function($query) use($where) {
            $query->where($where)->order('sort asc');
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
       // echo $this->getLastSql();exit;
        return $menu;
    }
}
