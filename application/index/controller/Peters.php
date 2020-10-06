<?php


namespace app\index\controller;

use app\index\model\PetersLog;
use app\index\model\PetersSet;

/**
 * 堂主申请列表
 * Class Xcscourse
 * @package app\index\controller
 */
class Peters extends Base
{
        /*
         * 申请列表
         */
    public function index() {
        $peterslog_model = new PetersLog();
        if($this->request->isAjax()){
            $where = [];
            return $peterslog_model->getList($where);
        }
        return $this->fetch('index');
    }
    public function petersset() {
        $peterslog_model = new PetersSet();
        if($this->request->isAjax()){
            $where = [];
            return $peterslog_model->getList($where);
        }
        return $this->fetch();
    }
    /*
     * 编辑
     */
    public function setadd(){
        if ($this->request->isPost()) {
            $PetersSet = new PetersSet();
            $peters_validate = new \app\index\validate\Peters();
            $request = $this->request->param();
            //验证字段
            $id = $request['id'];
            unset($request['id']);
            $data = [
                'name'=>$request['name'],
                'type'=>$request['type'],
                'sort'=>$request['sort'],
                'content'=>$request['content'],
                'addtime'=>time(),
            ];
            if (!$peters_validate->check($data)) {
                $this->error($peters_validate->getError());
            }
            if (empty($id)) {
                if(!$PetersSet->insert($data)){
                    $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {
                $where = ['id' => $id];
                if (false === $PetersSet->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                $msg = '修改成功';
            }
            $this->success($msg);
        }
        return $this->fetch();
    }

    /*
   * 编辑信息
   * @param int $id
   */
    public function setedit($id = 0) {
        $PetersSet = new PetersSet();
        $PetersSetInfo = $PetersSet->where('id',$id)->find();
        $this->assign('info',$PetersSetInfo);
        $this->assign('id',$id);
        return $this->fetch('setadd');
    }
    /*
    * 开启/关闭配置
    * @param int $id
    * @param int $is_open
    */
    public function openPeters($id = 0,$is_open = 0) {
        $petersSet = new PetersSet();
        false !== $petersSet->where('id',$id)->update(['is_open'=>$is_open]) && $this->success('操作成功');
        $this->error('操作失败');
    }
}
