<?php

namespace app\wxapp\model;

use app\index\model\Advanced;
use think\Model;
use app\wxapp\model\CourseLearnLog;
use app\index\model\Course;
use think\Db;
class Orders extends Model {
    protected $table = 'order';
    /*
     * 获取已购买的课程
     */
    public function getLearnCourse($where = [],$limit = '') {
        $field = ['c.teacher_id,c.id,c.advanced_id,c.name,c.abstract,c.chapter_count,o.paytime,o.status,o.uid,o.course_id'];
        $join = [
           ['course c','c.id=o.course_id','left']
        ];
        $data = $this::alias('o')->field($field)->join($join)->where($where)->limit($limit)->select();
        $teacher_model = new Teachers();
        $advanced_model = new Advanced();
        $learnLog_model = new CourseLearnLog();
        $course_model = new Course();
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        foreach ($data as $k=>$val) {
            $paytime = $val['paytime'];
            $data[$k]['paytime'] = date('Y.m.d',$val['paytime']);
            $data[$k]['teacherImg'] = $teacher_model->where('id',$val['teacher_id'])->value('headimg');
            if($val['advanced_id'] == 0) {
                $data[$k]['name'] = "财商基础知识包:《".$val['name']."》";
            }else{
                $advancedName = $advanced_model->where('id',$val['advanced_id'])->value('name');
                $data[$k]['name'] = $advancedName.":《".$val['name']."》";
            }
            if($val['status'] == 1) {
                $data[$k]['status'] = '学习中';
            }else if($val['status'] == 2){
                $data[$k]['status'] = '已过期';
            }else if($val['status'] == 3){
                $data[$k]['status'] = '已完成';
            }
            $cour = $course_model->where(['id'=>$val['course_id']])->field('id,name,chapter_count,abstract,imgurl,advanced_id,teacher_id,deadline')->find();
            $learnedCount = $learnLog_model->where(['course_id'=>$val['course_id'],'uid'=>$val['uid']])->count();
            
            $chapter_count = $advanced_model->where('id',$cour['advanced_id'])->value('chapter_count');
            $advanced = $advanced_model->where('id',$cour['advanced_id'])->field('deadline,learn_power')->find();
            $learnPer =  round($learnedCount / $chapter_count, 2);
            $isStudy = Db::name('CourseLearnLog')->alias('cll')->join('task_result','cll.id=task_result.cllid','left')->where(['task_result.uid'=>$val['uid'],'cll.course_id'=>$val['course_id'],'task_result.addtime'=>['between',[$beginToday,$endToday]]])->find();
               
                if($isStudy){
                    
                    $isStudy = 1;
                }else{
                    $isStudy = 0;
                }    
            //$paytime = $val['paytime'];
            //echo $paytime;exit;
            $days = intval((time() - $paytime) / 86400);
            $yudays = ($advanced['deadline'] - $days) < 0 ? 0 : $advanced['deadline'] - $days;
            $data[$k]['days'] = $yudays>=0 ? $yudays.'天':'已过期';
            $data[$k]['learnPer'] = intval($learnPer * 100);
            $data[$k]['learn_power'] = $advanced['learn_power'];
            $data[$k]['isStudy'] = $isStudy;
        }
        return returnjson('1000',$data,'获取成功');
    }
}