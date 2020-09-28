<?php


namespace app\index\controller;
use app\index\controller\Base;

/**
 * 算力银行
 * Class Xcscourse
 * @package app\index\controller
 */
class Colliers extends Base
{

    public function index() {
        $colliers_model = new \app\index\model\Colliers();
        if($this->request->isAjax()){
            $where = ['p_id'=>0];
            return $colliers_model->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 编辑
     */
    public function add(){
        if ($this->request->isPost()) {
            $colliers_model = new \app\index\model\Colliers();
            $colliers_validate = new \app\index\validate\Colliers();
            $request = $this->request->param();
            //验证字段
            $id = $request['id'];
            unset($request['id']);
            $data = [
                'name'=>$request['name'],
                'type'=>$request['type'],
                'logo'=>$request['logo'],
                'contribution'=>$request['contribution'],
                'max'=>$request['max'],
                'note'=>$request['note'],
                'model'=>$request['model'],
                'addtime'=>time(),
            ];
            if (!$colliers_validate->check($data)) {
                $this->error($colliers_validate->getError());
            }
            if (empty($id)) {
                if($colliers_model->where('type',$request['type'])->find()) {
                    $this->error('type值已存在，该值不能重复');
                }
                if(!$colliers_model->insert($data)){
                   $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $id];
                if (false === $colliers_model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                $msg = '修改成功';
            }
            $this->success($msg);
        }
        return $this->fetch('add');
    }

    /*
     * 编辑信息
     * @param int $id
     */
    public function edit($id = 0) {
        $colliers_model = new \app\index\model\Colliers();
        $colliereInfo = $colliers_model->where('id',$id)->find();
        $this->assign('info',$colliereInfo);
        $this->assign('id',$id);
        return $this->fetch('add');
    }

    /*
     * 删除
     */
    public function del($id = 0) {
        $colliers_model = new \app\index\model\Colliers();
        $colliers_model->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }

    /*
     * 是否开启任务
     */
    public function is_task($id = 0,$is_task = 0) {
        $colliers_model = new \app\index\model\Colliers();
        false !== $colliers_model->where('id',$id)->update(['is_task'=>$is_task]) && $this->success('设置成功');
        $this->error('设置失败');
    }

    /*
     * 查看下级
     */
    public function collierChild($p_id = 0) {
        $colliers_model = new \app\index\model\Colliers();
        $this->request->isAjax() || $this->error('非法请求');
        empty($p_id) && $this->error('缺少必要参数');
        $where = ['p_id' => $p_id];
        return $colliers_model->getList($where);
    }
    /*
     * 添加下级
     * @param int $p_id
     */
    public function addChild($p_id = 0) {
        if ($this->request->isPost()) {
            $colliers_model = new \app\index\model\Colliers();
            $colliers_validate = new \app\index\validate\Colliers();
            $request = $this->request->param();
            //验证字段
            $id = $request['id'];
            unset($request['id']);
            $data = [
                'name'=>$request['name'],
                'type'=>$request['type'],
                'contribution'=>$request['contribution'],
                'max'=>$request['max'],
                'p_id'=>$request['p_id'],
                'note'=>$request['note'],
                'addtime'=>time(),
            ];
            if (!$colliers_validate->check($data)) {
                $this->error($colliers_validate->getError());
            }
            if (empty($id)) {
                if($colliers_model->where('type',$request['type'])->find()) {
                    $this->error('type值已存在，该值不能重复');
                }
                if(!$colliers_model->insert($data)){
                    $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $id];
                if (false === $colliers_model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                $msg = '修改成功';
            }
            $this->success($msg);
        }
        $this->assign('p_id',$p_id);
        return $this->fetch('addchild');
    }

    /*
     * 编辑
     * @param int $id
     */
    public function editChild($id = 0) {
        $colliers_model = new \app\index\model\Colliers();
        $info = $colliers_model->where('id',$id)->find();
        $this->assign('info',$info);
        $p_id = $info['p_id'];
        $this->assign('p_id',$p_id);
        $this->assign('id',$id);
        return $this->fetch('addchild');
    }


}