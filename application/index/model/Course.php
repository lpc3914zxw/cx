<?php
namespace app\index\model;
use app\wxapp\model\CompulsoryCourse;
use app\wxapp\model\Orders;
use app\wxapp\model\Teachers;
use think\Model;
use think\Db;
use app\index\model\Sectiones;
use app\index\model\Advanced;
class Course extends Model {
    protected $table = 'course';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->order('addtime desc')->limit(page());
        });
        //var_dump($list);exit;
        $teacher_model = new Teachers();
        $advanced_model = new Advanced();
        foreach ($list as $k=>$val) {
            $list[$k]['teacher'] = $teacher_model->where('id',$val['teacher_id'])->value('name');
            $list[$k]['cate'] = $advanced_model->where('id',$val['advanced_id'])->value('name');
        }
        return page_data($total, $list);
    }

    /*
     * 财商的觉醒接口
     * 获取课程信息
     */
    public function getApiData($where = [],$id = 0) {
        $section_model = new Sectiones();
        $field = 'name,abstract,imgurl,teacher_id,course_bright,people_num,complete_course,haschapter_num';
        $advanced_id = $this::where('id',$id)->value('advanced_id');
        $advanced_model = new Advanced();
        $advancedInfo = $advanced_model->field('chapter_count,pay_type,value')->where('id',$advanced_id)->find();
        $data = $this::field($field)->where('id',$id)->find();
        $data['chapter_count'] = $advancedInfo['chapter_count'];
      	if($advancedInfo['pay_type']==1){
        	$data['paytype'] = 1;
        }else{
        	$data['paytype'] = 0;
        }

        $data['pay_type'] = $advancedInfo['pay_type'];
        //$data['paytype'] = $advancedInfo['pay_type'];
        $data['value'] = $advancedInfo['value'];
        $teacher_model = new Teachers();
        $teacherInfo = $teacher_model->where('id',$data['teacher_id'])->find();
        $system_model = new \app\wxapp\model\System();
        $systeminfo = $system_model->find();
        $sectionList = $section_model->field('name')->where('c_id',$id)->order('id')->limit(0,8)->select();
        $shareLink = GetCurUrl()."/wxapp/Xcscourse/h5_curriculum?c_id=".$id."&muid=";
        $data = ['courseData'=>$data,'teacherData'=>$teacherInfo,'exchangenote'=>$systeminfo['exchangenote'],'sectionList'=>$sectionList,'shareLink'=>$shareLink];
        $data['teacherData']['content'] = $data['teacherData']['introduction'];
        return $data;
    }

    /*
     * 获取进阶对应的课程列表
     */
    public function getApiAdvancedCourse($where = [],$uid = 0) {
        $teacher_model = new Teachers();
        $advanced_model = new Advanced();
        $list = $this::field('id,name,teacher_id,advanced_id,abstract,people_num')
                ->where($where)->select();
        // 判断是否购买
        $order_model = new Orders();
        foreach ($list as $k=>$val) {
            $list[$k]['teacher_img'] = $teacher_model->where('id',$val['teacher_id'])->value('imgurl');
            $count= $order_model->where(['course_id'=>$val['id'],'uid'=>$uid,'status'=>1])->count();
            $advancedInfo = $advanced_model->field('chapter_count,value')->where('id',$val['advanced_id'])->find();
            $list[$k]['chapter_count'] = $advancedInfo['chapter_count'];
            $list[$k]['value'] = $advancedInfo['value'];
            if($count > 0) {
                $list[$k]['is_buy'] = 1;
            }else{
                $list[$k]['is_buy'] = 0;
            }
        }
        return $list;
    }

    /*
     * 获取导师课程
     * @param array $where
     * @param string $limit
     * @return \type
     */
    public function getTutorCourse($where = [],$limit = '') {
        $data = $this::field('teacher_id,advanced_id,name,abstract,chapter_count,addtime,value,type')->where($where)->limit($limit)->select();
        $teacher_model = new Teachers();
        $advanced_model = new Advanced();
        $advanced_id = $advanced_model->where('type',3)->value('id');
        foreach ($data as $k=>$val) {
            $data[$k]['addtime'] = date('Y.d.d',$val['addtime']);
            $data[$k]['teacherImg'] = $teacher_model->where('id',$val['teacher_id'])->value('headimg');
            if($val['advanced_id'] == $advanced_id) {
                $data[$k]['name'] = "财商基础知识包:《".$val['name']."》";
            }else{
                $advancedName = $advanced_model->where('id',$val['advanced_id'])->value('name');
                $data[$k]['name'] = $advancedName.":《".$val['name']."》";
            }
            if($val['type'] == 1) {
                $data[$k]['value'] = $val['value'].'元';
            }else{
                $data[$k]['value'] = $val['value'].'学分';
            }
        }
        return returnjson('1000',$data,'获取成功');
    }

    public function getCourseList($where = [],$uid = 0) {
        $teacher_model = new Teachers();
        $advanced_model = new Advanced();
        $list = $this::field('id,name,teacher_id,advanced_id,abstract,people_num')
            ->where($where)->where('is_delete',0)->select();

        // 判断是否购买
        $order_model = new Orders();
        foreach ($list as $k=>$val) {
            $list[$k]['teacher_img'] = $teacher_model->where('id',$val['teacher_id'])->value('imgurl');
            $count= $order_model->where(['course_id'=>$val['id'],'uid'=>$uid,'status'=>1])->count();
            $advancedInfo = $advanced_model->field('chapter_count,value')->where('id',$val['advanced_id'])->find();
            $list[$k]['chapter_count'] = $advancedInfo['chapter_count'];
            $list[$k]['value'] = $advancedInfo['value'];
            if($count > 0) {
                $list[$k]['is_buy'] = 1;
            }else{
                $list[$k]['is_buy'] = 0;
            }
        }
        return $list;
    }
}
