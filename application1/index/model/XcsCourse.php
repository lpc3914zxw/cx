<?php
namespace app\index\model;
use app\wxapp\model\CompulsoryCourse;
use app\wxapp\model\Teachers;
use think\Model;
use think\Db;
class XcsCourse extends Model {
    protected $table = 'course';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->order('addtime desc')->limit(page());
        });
        $teacher_model = new Teachers();
        foreach ($list as $k=>$val) {
            $list[$k]['teacher'] = $teacher_model->where('id',$val['teacher_id'])->value('name');
        }
        return page_data($total, $list);
    }

    /*
     * 财商的觉醒接口
     * 获取课程信息
     */
    public function getApiData($where = [],$advanced_id = 0) {
        $field = 'name,abstract,imgurl,teacher_id,value,chapter_count,course_bright,people_num,complete_course,haschapter_num';
        $data = $this::field($field)->where('advanced_id',$advanced_id)->find();
        $teacher_model = new Teachers();
        $teacherInfo = $teacher_model->where('id',$data['teacher_id'])->find();
        $data = ['courseData'=>$data,'teacherData'=>$teacherInfo];
        return $data;
    }
}