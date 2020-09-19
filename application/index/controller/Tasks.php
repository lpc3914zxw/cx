<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\Sectiones;
use app\index\model\TaskOptions;

/**
 * 作业
 * Class Xcscourse
 * @package app\index\controller
 */
class Tasks extends Base
{
    /*
     * 作业 问题
     */
    public function index($id = 0){
        $task_model = new \app\index\model\Tasks();
        if($this->request->isAjax()){
            $where = ['section_id'=>$id];
            return $task_model->getList($where);
        }
        $this->assign('section_id',$id);
        return $this->fetch();
    }

    /*
    * 获取问题答案
    * 问题id
    * @param int $task_id
    * @return array
    */
    public function optionlist($task_id = 0){
        $this->request->isAjax() || $this->error('非法请求');
        empty($task_id) && $this->error('缺少必要参数');
        $where = ['task_id' => $task_id];
        $taskopt_model = new TaskOptions();
        return $taskopt_model->getList($where);
    }

    /*
     * 设置正确答案
     */
    public function setTrue($id = 0) {
        $this->request->isAjax() || $this->error('非法请求');
        empty($id) && $this->error('缺少必要参数');
        $task_model = new \app\index\model\Tasks();
        $taskopt_model = new TaskOptions();
        $task_id = $taskopt_model->where('id',$id)->value('task_id');
        $type = $task_model->where('id',$task_id)->value('type');
        if($type == 1) {  //单选
            if(false === $taskopt_model->where(['task_id'=>$task_id])->update(['is_true'=>0])){
                $this->error('设置错误');
            }
            if(false === $taskopt_model->where('id',$id)->update(['is_true'=>1])){
                $this->error('设置错误');
            }
            $this->success('设置成功');
        }else{
            if(false === $taskopt_model->where('id',$id)->update(['is_true'=>1])){
                $this->error('设置错误');
            }
            $this->success('设置成功');
        }
    }


    /*
     * 设置错误答案
     */
    public function setFalse($id = 0) {
        $this->request->isAjax() || $this->error('非法请求');
        empty($id) && $this->error('缺少必要参数');
        $task_model = new \app\index\model\Tasks();
        $taskopt_model = new TaskOptions();
        $task_id = $taskopt_model->where('id',$id)->value('task_id');
        $type = $task_model->where('id',$task_id)->value('type');
        if($taskopt_model->where(['task_id'=>$task_id,'is_true'=>1])->count() < 2){
            $this->error('必须有一个正确选项');
        }
        if($type == 1) {  //单选
            $this->success('设置成功');
        }else{
            if(false === $taskopt_model->where('id',$id)->update(['is_true'=>0])){
                $this->error('设置错误');
            }
            $this->success('设置成功');
        }
    }

    /*
     * 课时列表//添加问题
     */
    public function add($id = 0) {
        $task_model = new \app\index\model\Tasks();
        $taskopt_model = new TaskOptions();
        if($this->request->isAjax()){
            $params = $this->request->param();
            // 组装数据
            $data = [
                'title'=>$params['title'],
                'type'=>$params['changetype'],
                'section_id'=>$params['section_id']
            ];
            //$task_model->startTrans();
            if($params['id']){ //修改
                $task_id = $params['id'];
                false === $task_model->where('id',$task_id)->update($data)  && $this->error('更新失败');
                if(!$taskopt_model->where('task_id',$task_id)->delete()){
                    //$task_model->rollback();
                    $this->error('更新失败');
                }
                $optiondata = $this->disposeData($params, $task_id);
                if($optiondata == 1){
                    $this->error('选项不能为空');
                }
                if(!$taskopt_model->insertAll($optiondata)){
                    //$task_model->rollback();
                    $this->error('添加失败');
                }
                //$task_model->commit();
                $this->success('更新成功','/index/Tasks/index/id/'.$params['section_id']);
            }else{
                if(!$task_model->insert($data)){
                    //$task_model->rollback();
                    $this->error('添加失败');
                }
                $task_id= $task_model->getLastInsID();
                $optiondata = $this->disposeData($params, $task_id);
                if($optiondata == 1){
                    $this->error('选项不能为空');
                }
                if(!$taskopt_model->insertAll($optiondata)){
                    //$task_model->rollback();
                    $this->error('添加失败');
                }
            }
            //$task_model->commit();
            $this->success('添加成功','/index/Tasks/index/id/'.$params['section_id']);
        }
        $info = $task_model->where('section_id',$id)->find();
        if($info) {
            $this->assign('info',$info);
            $optionList = $taskopt_model->where('task_id',$info['id'])->select();
            foreach ($optionList as $k=>$val) {
                $optionList[$k]['option'] = '选项'.$this->getCasNum($k);
            }
            $this->assign('optionlist',$optionList);
        }
        $this->assign('section_id',$id);
        return $this->fetch();
    }

    /*
     *编辑
     */
    public function edit($id = 0) {
        $task_model = new \app\index\model\Tasks();
        $taskopt_model = new TaskOptions();
        $taskinfo = $task_model->where('id',$id)->find();
        $this->assign('info',$taskinfo);
        $this->assign('section_id',$taskinfo['section_id']);
        $optionlist = $taskopt_model->where('task_id',$id)->select();
        foreach ($optionlist as $k=>$val){
            $optionlist[$k]['option'] = '选项'.$this->getCasNum($k);
        }
        $this->assign('optionlist',$optionlist);
        return $this->fetch('add');
    }

    /*
     *删除
     */
    public function delet($id = 0) {
        
        $task_model = new \app\index\model\Tasks();
       
        $taskinfo = $task_model->where('id',$id)->delete() && $this->success('已删除','');
        $this->error('删除失败');
        
    }

    function getCasNum($num){
        $arrnum = ['一','二','三','四','五','六','七','八','九','十','十一','十二','十二','十三','十四','十五','十六','十七','十八','十九','二十'];
        foreach ($arrnum as $k=>$val){
            if($k == $num){
                return $val;
            }
        }
    }

    /*
     * 组装传过来的数据
     * @author Steed
     * @param $request
     * @return array
     */
    private function disposeData($request,$task_id = 0) {
        $data = [];
        for ($i = 0; $i < count($request['answer']); $i++) {
            if(!empty($request['answer'])){
                $data[$i]['task_id'] = $task_id;
                $data[$i]['options']  = $request['answer'][$i];
                if(empty($request['answer'][$i])){
                    return 1;
                }
            }else{
                return 1;
            }
        }
        return $data;
    }

}