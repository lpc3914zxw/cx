<?php

namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\AdverChild;

use app\index\model\KnowledgeCate;
use app\index\model\Tutor;
use app\index\model\TutorCert;
use think\Db;

/**
 * 每日才学
 * Class Advanced
 * @package app\index\controller
 */
class Dayarticle extends Base
{
    

    /*
     * 金句列表
     */
    public function index() {
        $knowledge = new \app\index\model\Dayarticle();
        if($this->request->isAjax()){
            $where = [];
            return $knowledge->getList($where);
        }
        return $this->fetch('index');
    }
     /*
     * 添加
     */
    public function add() {
        return $this->fetch('add');
    }
    /*
     * 编辑金句
     */
    public function adddayarticle(){
        if ($this->request->isPost()) {
            $Dayarticle_model = new \app\index\model\Dayarticle();
            
            $request = $this->request->param();
            
            $id = $request['id'];
            unset($request['id']);
            
            $data = [
                'title'=>$request['title'],
                'content'=>$request['content'],
                
                'contribution_value'=>$request['contribution_value'],
                'imgurl'=>$request['imgurl'],
                'type'=>$request['type']
               
            ];
           	if(empty($data['title'])||empty($data['content'])||empty($data['imgurl'])||empty($data['type'])){
            	$this->error('参数缺失');
            }
            if (empty($id)) {
                $data['addtime'] = time();
                if(!$Dayarticle_model->insert($data)){
                   $this->error('添加失败');
                }
                
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $id];
                
                if (false === $Dayarticle_model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                
                $msg = '修改成功';
            }
           
            $this->success($msg);
        }
        return $this->fetch('add');
    }
    /*
     * 编辑
     */
    public function edit($id = 0) {
        $Dayarticle = new \app\index\model\Dayarticle();
        $info = $Dayarticle->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('id',$id);
        return $this->fetch('add');
    }

    

    /*
     * 删除
     * @param int $id
     */
    public function del($id = 0) {
        $this->request->isAjax() || $this->error('非法请求');
        $Dayarticle = new \app\index\model\Dayarticle();
        false !== $Dayarticle->where('id',$id)->delete() && $this->success('已删除');
        $this->error('删除失败');
    }

    /*
     * 批量删除
     * @param string $ids
     */
    public function piDel($ids = []) {
        $this->request->isAjax() || $this->error('非法请求');
        $knowledge = new \app\index\model\Dayarticle();
        false !== $knowledge->where(['id'=>['in',$ids]])->delete() && $this->success('已删除');
        $this->error('删除失败');
    }

    


   
   

    /**
     * 组装传过来的数据
     * @author Steed
     * @param $request
     * @param int $type 0商品数据，1规格数据
     * @param int $goods_id
     * @return array
     */
    private function disposeData($request, $type = 0, $id = 0) {
        $data = [];
        if ($type === 0) {  // 添加
            $data = [
                'adv_id'  => $id,
                'name'         => $request['name'],
                'idvalue'  => $request['idvalue'],
                'type'      => floatval($request['freight']),
                'addtime'         => time()
            ];
            if (!empty($request['goods_id'])) {
                unset($data['dateline']);
                $data['updateline'] = $this->request->time();
            }
        } else {
            for ($i = 0; $i < count($request['type']); $i++) {
                $data[$i]['adv_id']       = $id;
                $data[$i]['link']          = $request['link'][$i];
                $data[$i]['idvalue']      = $request['idvalue'][$i];
                $data[$i]['type']           = $request['type'][$i];
                $data[$i]['imgurl']           = $request['images'][$i];
                $data[$i]['addtime']    = time();
            }
        }
        return $data;
    }


}