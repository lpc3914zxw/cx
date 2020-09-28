<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\HonorSet;

/**
 * 荣誉值设置
 * Class Xcscourse
 * @package app\index\controller
 */
class Honor extends Base
{
    /*
         * 荣誉值
         */
    public function honor() {
        $honorSet = new HonorSet();
        if($this->request->isAjax()){
            $where = [];
            return $honorSet->getList($where);
        }
        return $this->fetch('index');
    }

    /*
     * 编辑
     */
    public function add(){
        if ($this->request->isPost()) {
            $honorSet = new HonorSet();
            $hornor_validate = new \app\index\validate\Honor();
            $request = $this->request->param();
            //验证字段
            $id = $request['id'];
            unset($request['id']);
            $data = [
                'name'=>$request['name'],
                'type'=>$request['type'],
                'contribution'=>$request['contribution'],
                'max'=>$request['max'],
                'note'=>$request['note'],
                'addtime'=>time(),
            ];
            if (!$hornor_validate->check($data)) {
                $this->error($hornor_validate->getError());
            }
            if (empty($id)) {
                if($honorSet->where('type',$request['type'])->find()) {
                    $this->error('type值已存在，该值不能重复');
                }
                if(!$honorSet->insert($data)){
                   $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {
                $where = ['id' => $id];
                if (false === $honorSet->where($where)->update($data)) {
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
        $honorSet = new HonorSet();
        $honorSetInfo = $honorSet->where('id',$id)->find();
        $this->assign('info',$honorSetInfo);
        $this->assign('id',$id);
        return $this->fetch('add');
    }

    /*
     * 删除
     */
    public function del($id = 0) {
        $honorSet = new HonorSet();
        $honorSet->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }
}