<?php
// +----------------------------------------------------------------------
// | 功能：导师证书
// +----------------------------------------------------------------------
// | 作者: xiaomage
// +----------------------------------------------------------------------
// | 日期：2020-05-19
// +----------------------------------------------------------------------
namespace app\index\model;
use think\Model;
use app\index\model\User;

/**
 * 导师证书
 * Class Advanced
 * @package app\index\model
 */
class TutorCert extends Model{
    protected $table = 'tutor_certificate';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        $user_model = new User();
        foreach ($list as $k=>$val) {
            $list[$k]['tutorname'] = $user_model->where('id',$val['uid'])->value('name');
            $list[$k]['tutorimg'] = $user_model->where('id',$val['uid'])->value('headimg');
        }
        return page_data($total, $list);
    }
}
