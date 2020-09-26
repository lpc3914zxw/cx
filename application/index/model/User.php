<?php
namespace app\index\model;
use think\Model;
use think\Db;
use think\Loader;
class User extends Model {
    protected $table = 'user';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }


    public function get_list($map = [],$is_export = 0) {
        $u = $map['u'];
        $s = $map['s'];
        $total = $this::alias('u')->where($u)->whereOr($s)->count(1);
        $join = [
           ['start_level sl','sl.value=u.start_level','left']
        ];
        $join1 = [
           ['user_level l','l.value=u.level','left']
        ];
        $list = $this::all(function($query) use($u,$s,$join,$join1) {
            $query->alias('u')->join($join)->join($join1)->where($u)->whereOr($s)->order('id desc')->field('u.*,sl.name as sname,l.name as lname')->limit(page());

        });
        return page_data($total, $list);
    }

    public function get_face_time_count($begin,$end) {

       // $total = $this::alias('u')->where($u)->whereOr($s)->count(1);
        $join = [
            ['face_order f','f.uid=u.id','left']
        ];
        $count = $this::alias('u')->join($join)->where('f.status','=','1')->where(['f.paytime'=>['between',[$begin,$end]]])->count();

        return $count;
    }
    public function get_face_count() {

        // $total = $this::alias('u')->where($u)->whereOr($s)->count(1);
        $join = [
            ['face_order f','f.uid=u.id','left']
        ];
        $count = $this::alias('u')->join($join)->where('f.status','=','1')->count();

        return $count;
    }

}
