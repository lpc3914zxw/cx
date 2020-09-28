<?php
namespace app\index\controller;
use app\index\controller\Base;
use think\Session;
/**
 * 帮助文档
 * @author Steed
 * @package app\index\controller
 */
class Helps extends Base {

    /*
     * 常见问题
     */
    public function comHelp() {
        $model = new \app\index\model\Helps();
        if($this->request->isAjax()){
            $where = ['type'=>1];
            //搜索条件
            $params = $this->request->param();
            (isset($params['title']) && !empty($params['title'])) && $where['title'] = ['like', '%' . $params['title'] . '%'];
            return $model->getList($where);
        }
        return $this->fetch('index');
    }

    /*
    * 热门问题
    */
    public function hotHelps() {
        $model = new \app\index\model\Helps();
        if($this->request->isAjax()){
            $where = ['type'=>2];
            //搜索条件
            $params = $this->request->param();
            (isset($params['title']) && !empty($params['title'])) && $where['title'] = ['like', '%' . $params['title'] . '%'];
            return $model->getList($where);
        }
        return $this->fetch('hothelp');
    }

    /*
     * 新增
     * @param int $type  1 常见问题  2 热门问题
     */
    public function add($type = 1) {
        if($this->request->isPost()) {
            $model = new \app\index\model\Helps();
            $params = $this->request->param();
            $data = [
                'title'=>$params['title'],
                'content'=>$params['content'],
                'type'=>$params['type'],
                'addtime'=>time(),
            ];
            $id = $params['id'];
            if($id) {
                if(false == $model->where('id',$id)->update($data)) {
                    $this->error('修改错误');
                }
                if(file_exists('html/helps/helpdetaile'.$id.'.html')){
                    unlink('html/helps/helpdetaile'.$id.'.html');
                }
                $this->success('修改成功');
            }
            if($model->insert($data)) {
                $this->success('添加成功');
            }
            $this->error('添加失败');
        }
        
        $this->assign('type',$type);
        return $this->fetch();
    }

    /*
     * 修改
     * @param int $id
     * @return mixed
     */
    public function edit($id = 0) {
        
        
        $model = new \app\index\model\Helps();
        
        $info = $model->where('id',$id)->find();
        if($this->request->isPost()) {
             $params = $this->request->param();
             //var_dump($params);exit;
             $model->where('id',$id)->update();
             if(file_exists('html/helps/helpdetaile'.$id.'.html')){
                unlink('html/helps/helpdetaile'.$id.'.html');
            }
        }
        
        $this->assign('info',$info);
        $this->assign('type',$info['type']);
        $this->assign('id',$id);
        return $this->fetch('add');
    }
    /*
    * 联系我们
    */
    public function contactHelps() {
        $model = new \app\index\model\Helps();
        if($this->request->isAjax()){
            $where = ['type'=>3];
            //搜索条件
            $params = $this->request->param();
            (isset($params['title']) && !empty($params['title'])) && $where['title'] = ['like', '%' . $params['title'] . '%'];
            return $model->getList($where);
        }
        return $this->fetch('contacthelp');
    }
	/*
     * 修改
     * @param int $id
     * @return mixed
     */
    public function del($id = 0) {
        
        
        $model = new \app\index\model\Helps();
        
        //$info = $model->where('id',$id)->find();
        $model->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }
}
