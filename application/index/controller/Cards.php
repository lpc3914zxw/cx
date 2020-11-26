<?php

namespace app\index\controller;
use app\index\controller\Base;
/**
 * 会员卡
 * Class Advanced
 * @package app\index\controller
 */
class Cards extends Base
{
    /*
     * 列表
     */
    public function index() {
        $adver_model = new \app\index\model\Cards();
        if($this->request->isAjax()){
            $where = [];
            return $adver_model->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 添加
     */
    public function add($id = 0){
        $adver_model = new \app\index\model\Cards();
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $data = [
                'name'=>$request['name'],
                'img'=>$request['img'],
                'dedication_value'=>$request['dedication_value'],
                 'price'=>$request['price'],
                'discount'=>$request['discount'],
                'explain'=>$request['explain'],
                'abstract'=>$request['abstract'],
                'addtime'=>time()
            ];
            //验证字段
            $editId = $request['id'];
            $advertisment_validate = new \app\index\validate\Cards();
            if(!$advertisment_validate->check($data)) {
                $this->error($advertisment_validate->getError());
            }
            if (empty($editId)) {
                if(!$adver_model->insert($data)){
                    $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $editId];
                if (false === $adver_model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                $msg = '修改成功';
            }
            $this->success($msg);
        }
        $info = $adver_model->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('id',$id);
        return $this->fetch();
    }


    /*
     * 删除
     * @param int $id
     */
    public function del($id = 0) {
        $adver_model = new \app\index\model\Cards();
        $adver_model->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }
}
