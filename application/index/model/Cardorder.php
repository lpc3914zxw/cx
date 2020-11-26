<?php

namespace app\index\model;
use think\Model;
use think\Db;
/*
 * 学分来源
 */
class Cardorder extends Model {
    protected $table = 'cards_order';
    public function getList($map = [],$is_export = 0) {
        $join = [
           ['user u','u.id=f.uid','left']
        ];
        $total = $this::alias('f')->join($join)->where($map)->count(1);
        $field = ['f.*,u.name,u.tel'];
        if($is_export == 0) {
            $list = $this::all(function($query) use($map,$field,$join) {
                $query->alias('f')->field($field)->join($join)->where($map)->order('paytime desc')->limit(page());
            });
        }else{
            $list = $this::alias('f')->field($field)->join($join)->where($map)->order('paytime desc')->select();
        }
        foreach($list as $key=>$val){
           $list[$key]['subject'] ='购买-'. Db::name('cards')->where('id',$val['cardid'])->value('name');
        }
        return page_data($total, $list);
    }

   
}


