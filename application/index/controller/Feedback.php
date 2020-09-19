<?php

namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\Levels;
/**
 * 反馈列表
 * Class Advanced
 * @package app\index\controller
 */
class Feedback extends Base
{
    /*
     * 反馈列表
     */
    public function index() {
        $feedback = new \app\index\model\Feedback();
        if($this->request->isAjax()){
            $where = [];
            return $feedback->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 单条删除
     */
    public function del($id = 0) {
        $this->request->isAjax() || $this->error('非法请求');
        $feedback = new \app\index\model\Feedback();
        $feedback->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }

    /*
     * 批量删除
     * @param string $ids
     */
    public function piDel($ids = []) {
        $this->request->isAjax() || $this->error('非法请求');
        $feedback = new \app\index\model\Feedback();
        $feedback->where(['id'=>['in',$ids]])->delete() && $this->success('已删除');
        $this->error('删除失败');
    }
}