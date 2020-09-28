<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\Teacher;
use app\index\validate\Messages;

/**
 * 系统消息
 * Class Xcscourse
 * @package app\index\controller
 */
class Message extends Base
{
    /*
     * 消息列表
     */
    public function index() {
        $model = new \app\index\model\Message();
        if($this->request->isAjax()){
            $where = ['type'=>1];
            return $model->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 添加系统消息
     */
    public function addmessage() {
        if($this->request->isPost()) {
            $param = $this->request->param();
            $model = new \app\index\model\Message();
            $validate = new Messages();
            $data = [
                'title'=>$param['title'],
                'abstract'=>$param['abstract'],
                'content'=>$param['content'],
                'addtime'=>time()
            ];
            if(!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $id = $param['id'];
            if($id) {
                if(false === $model->where('id',$id)->update($data)){
                    $this->error('发布失败');
                } 
                
                if(file_exists('html/clause/cxmessgindetail'.$id.'.html')){
                    unlink('html/clause/cxmessgindetail'.$id.'.html');
                }
                $this->success('添加成功','/index/message/index');
            }
            $model->insert($data) && $this->success('发布成功','/index/message/index');
            $this->error('发布失败');
        }
        return $this->fetch('add');
    }

    /*
     * 编辑
     */
    public function edit($id = 0) {
        $model = new \app\index\model\Message();
        $info = $model->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('id',$id);
        return $this->fetch('add');
    }

    /*
     * 删除消息
     */
    public function delmsg($id = 0) {
        $model = new \app\index\model\Message();
        $model->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }

    /*
     * 发布消息
     */
    public function sendmsg($id = 0) {
        $model = new \app\index\model\Message();
        $content = $model->where('id',$id)->value('abstract');
        false === $model->where('id',$id)->update(['send_time'=>time(),'is_send'=>1]) && $this->error('发布失败');
        //$res = send_jpush($content);
        //var_dump($res);exit;
        //false === send_jpush($content) && $this->error('发布失败');
        $this->success('发布成功');
    }
}