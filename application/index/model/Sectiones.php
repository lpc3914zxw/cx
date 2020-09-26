<?php
namespace app\index\model;
use app\wxapp\model\CourseLearnLog;
use think\Model;
use think\Db;

/*
 * 课程章节
 */
class Sectiones extends Model {
    protected $table = 'section';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->order('sort')->limit(page());
        });
        return page_data($total, $list);
    }

    /*
     * 接口 学财商 获取课程章节列表以及学习情况
     */
    public function getApiSectionList($where =[],$uid = 0) {
        $list = $this::field('audiourl,audiotime,name,people_num,id,sort')->where($where)->order('sort')->select();
        //echo $this->getLastSql();exit;
        $course_model = new Course();
        $order_model = new \app\wxapp\model\Orders();
        $learnLog = new CourseLearnLog();
        $adver_model = new Advanced();
        $course_id = $this::where($where)->value('c_id');
        $courseInfo = $course_model->where('id',$course_id)->find();
        // 过期时间
        $deadline = $adver_model->where('id',$courseInfo['advanced_id'])->value('deadline');
        $paytime = $order_model->where(['uid'=>$uid,'course_id'=>$course_id,'status'=>1])->value('paytime');

        $days = intval((time() - $paytime) / 86400);
        $syDays = $deadline - $days;
        foreach ($list as $k=>$val) {
            if($syDays < 1) {  // 超过有效期，全部解锁，但此课程学习考核不再发放任何学分
                $list[$k]['is_lock'] = 1;
            }else{   // 未超有效期
                $log = $learnLog->where(['section_id'=>$val['id'],'uid'=>$uid])->find();
                if($log){
                  	if($log['unlocktime']>time()){
                    	$list[$k]['is_lock'] = 0;
                    }else{
                    	$list[$k]['is_lock'] = 1;
                    }

                }else {
                    $list[$k]['is_lock'] = 0;
                }
            }

          	$task = Db::name('task')->where(['section_id'=>$val['id']])->find();
          	if(!empty($task)){
            	$task_result = Db::name('task_result')->where(['uid'=>$uid,'task_id'=>$task['id']])->find();
              	if($task_result){
                	$list[$k]['is_lock'] = 2;
                }
            }

            $list[$k]['name'] = $val['sort'].'、'.$val['name'];
        }
        return $list;
    }

    /*
     * 接口 获取课程章节列表以及学习情况
     */
    public function getCourseSectionList($where =[],$uid = 0) {
        $list = $this::field('audiourl,audiotime,name,people_num,id,sort')->where($where)->order('sort')->select();
        foreach ($list as $k=>$val) {
            $list[$k]['name'] = $val['sort'].'、'.$val['name'];
        }
        return $list;
    }
}
