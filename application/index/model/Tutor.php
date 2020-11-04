<?php
namespace app\index\model;
use app\wxapp\model\CompulsoryCourse;
use app\index\model\User;
use think\Model;
use think\Db;
use app\index\model\Sectiones;
class Tutor extends Model {
    protected $table = 'tutor';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->order('addtime desc')->limit(page());
        });
        $user_model = new User();
        foreach ($list as $k=>$val) {
            $list[$k]['tutorname'] = $user_model->where('id',$val['uid'])->value('name');
            $list[$k]['tutorimg'] = $user_model->where('id',$val['uid'])->value('headimg');
        }
        return page_data($total, $list);
    }

    /*
     * 导师列表
     */
    public function getApiList($where = [],$limit = '') {
        $data = $this::field('id,imgurl,article_num,comment_num,name,content,like_num')->where($where)->order('like_num desc')->limit($limit)->select();
        return returnjson(1000,$data,'获取成功');
    }
}