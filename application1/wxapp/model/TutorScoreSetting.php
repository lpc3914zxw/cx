<?php

namespace app\wxapp\model;

use think\Model;
/*
 * 导师赞赏学分设置
 */
class TutorScoreSetting extends Model {
    protected $table = 'tutor_score_setting';
    public function getList($where = []) {
        $data = $this::where($where)->order('sort')->select();
        return returnjson('1000',$data,'获取成功');
    }

    public function getSettingList($where = []) {
        $total = $this::where($where)->count(1);
        $list = $this::all(function($query) use($where) {
            $query->where($where)->limit(page());
        });
        return page_data($total, $list);
    }
}