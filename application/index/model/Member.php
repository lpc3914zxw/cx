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
use think\Db;
class Member extends Model{
    protected $table = 'member';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }

    public function getUserList($map = []) {
        $total = Db::table('auth_group_access')
            ->alias('aga')
            ->field('u.*,aga.group_id,ag.title')
            ->join('__MEMBER__ u', 'aga.uid=u.uid', 'RIGHT')
            ->join('__AUTH_GROUP__ ag', 'aga.group_id=ag.id', 'LEFT')
            ->count();
        $data = Db::table('auth_group_access')
            ->alias('aga')
            ->field('u.*,aga.group_id,ag.title')
            ->join('__MEMBER__ u', 'aga.uid=u.uid', 'RIGHT')
            ->join('__AUTH_GROUP__ ag', 'aga.group_id=ag.id', 'LEFT')
            ->select();
        $first = $data[0];
        $first['title'] = array();
        $user_data[$first['uid']] = $first;
        // 组合数组
        foreach ($data as $k => $v) {
            foreach ($user_data as $m => $n) {
                $uids = array_map(function($a) {
                    return $a['uid'];
                }, $user_data);
                if (!in_array($v['uid'], $uids)) {
                    $v['title'] = array();
                    $user_data[$v['uid']] = $v;
                }
            }
        }
        // 组合管理员title数组
        foreach ($user_data as $k => $v) {
            foreach ($data as $m => $n) {
                if ($n['uid'] == $k) {
                    $user_data[$k]['title'][] = $n['title'];
                }
            }
            $user_data[$k]['title'] = implode('、', $user_data[$k]['title']);
        }
        return page_data($total, $data);
    }
}
