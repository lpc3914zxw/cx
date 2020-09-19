<?php

namespace app\index\model;

use app\index\model\Advanced;
use app\wxapp\model\Teachers;
use think\Model;

class Orders extends Model {
    protected $table = 'order';
    /*
     * 获取已购买的课程
     */
    public function getLearnCourse($where = [],$is_export = 0) {
        $join = [
           ['course c','c.id=o.course_id','left']
        ];
        $join1 = [
           ['user u','u.id=o.uid','left']
        ];
        $total = $this::alias('o')->join($join)->join($join1)->where($where)->where('o.status','neq',0)->count(1);
        $field = ['o.id,c.teacher_id,c.advanced_id,c.name,c.abstract,c.chapter_count,o.paytime,o.status,o.pay_type,o.addtime,o.value,u.name as uname,u.tel,o.order_id,o.effective'];
        if($is_export == 0) {
            $data = $this::alias('o')->field($field)->join($join)->join($join1)->where($where)->order('id desc')->where('o.status','neq',0)->limit(page())->select();
        }else{
            $data = $this::alias('o')->field($field)->join($join)->join($join1)->where($where)->order('id desc')->where('o.status','neq',0)->select();
        }
        $teacher_model = new Teachers();
        $advanced_model = new Advanced();
        foreach ($data as $k=>$val) {
            $data[$k]['paytime'] = date('Y.m.d H:i:s',$val['paytime']);
            $data[$k]['addtime'] = date('Y.m.d H:i:s',$val['addtime']);
            $data[$k]['teacherImg'] = $teacher_model->where('id',$val['teacher_id'])->value('headimg');
            if($val['advanced_id'] == 0) {
                $data[$k]['name'] = "财商基础知识包:《".$val['name']."》";
            }else{
                $advancedName = $advanced_model->where('id',$val['advanced_id'])->value('name');
                $data[$k]['name'] = $advancedName.":《".$val['name']."》";
            }
            if($val['status'] == 1) {
                $data[$k]['status'] = '学习中';
            }else {
                $data[$k]['status'] = '已学完';
            }
        }
        return page_data($total, $data);
       
    }
}