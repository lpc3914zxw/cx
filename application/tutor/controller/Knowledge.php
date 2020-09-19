<?php

namespace app\tutor\controller;
use app\index\model\KnowledgeCate;
use app\index\model\User;
use app\tutor\controller\Base;
use app\tutor\model\Tutor;
use app\wxapp\model\TutorScoreSetting;
use think\Db;
/**
 * 涨知识
 * Class Advanced
 * @package app\index\controller
 */
class Knowledge extends Base
{
    /*
     * 涨知识列表
     */
    public function article() {
        $knowledge = new \app\index\model\Knowledge();
        if($this->request->isAjax()){
            $params = $this->request->param();
            $where['uid'] = $this->tutor_id;
            $where['is_delete'] = 0;
            (isset($params['title']) && !empty($params['title'])) && $where['title'] = ['like', '%' . $params['title'] . '%'];
            if(isset($params['status'])){
                $where['status'] = $params['status'];
            }
            if(isset($params['is_check'])){
                $where['is_check'] = $params['is_check'];
            }
            return $knowledge->getList($where);
        }
        return $this->fetch('index');
    }

   /*
   * 批量操作
   * @param string $ids
   */
    public function piOpt($ids = [],$type = 'up') {
        $this->request->isAjax() || $this->error('非法请求');
        $knowledge = new \app\index\model\Knowledge();
        $tutor_model = new Tutor();
        if($type == 'up') {  // 上架
            false !== $knowledge->where(['id'=>['in',$ids]])->update(['status'=>1]) && $this->success('已上架');
            $this->error('上架失败');
        }elseif($type == 'down') {  // 下架架
            false !== $knowledge->where(['id'=>['in',$ids]])->update(['status'=>0]) && $this->success('已下架');
            $this->error('下架失败');
        }else if($type == 'delete'){
            $num = count($ids);
            Db::startTrans();
            if(false === $knowledge->where(['id'=>['in',$ids]])->update(['is_delete'=>1])) {
                $this->error('删除失败');
            }
            if(false === $tutor_model->where('uid',$this->tutor_id)->setDec('article_num',$num)) {
                Db::rollback();
                $this->error('删除失败');
            }
            Db::commit();
            $this->success('删除成功');
        }else{
            $this->error('非法操作');
        }
    }

    /*
     * 学分设置
     */
    public function scoreSetting() {
        $scoreSetting = new TutorScoreSetting();
        if($this->request->isAjax()){
            $where = ['tutor_id'=>$this->tutor_id];
            return $scoreSetting->getSettingList($where);
        }
        return $this->fetch('scoreset');
    }

    /*
     * 添加赞赏学分
     */
    public function addScoreSet($id = 0) {
        $scoreSetting = new TutorScoreSetting();
        if($this->request->isPost()) {
            $params = $this->request->param();
            $data = [
                'tutor_id'=>$this->tutor_id,
                'name'=>$params['name'],
                'value'=>$params['value'],
                'sort'=>$params['sort']
            ];
            $editId = $params['id'];
            if($editId) {
                if(false !== $scoreSetting->where('id',$editId)->update($data)) {
                    $this->error('修改成功');
                }
                $this->error('修改失败');
            }
            if($scoreSetting->insert($data)){
                $this->success('添加成功');
            }
            $this->error('添加失败');
        }
        if($id) {
            $info = $scoreSetting->where('id',$id)->find();
            $this->assign('id',$id);
            $this->assign('info',$info);
        }
        return $this->fetch('addscore');
    }

    /*
     * 删除赞赏学分
     */
    public function delScoreSet($id = 0) {
        $this->request->isAjax() || $this->error('非法请求');
        $scoreSetting = new TutorScoreSetting();
        $scoreSetting->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }

    /*
   * 删除文章
   * @param int $id
   */
    public function del($id = 0) {
        $this->request->isAjax() || $this->error('非法请求');
        $knowledge = new \app\index\model\Knowledge();
        false !== $knowledge->where('id',$id)->update(['is_delete'=>1]) && $this->success('已删除');
        $this->error('删除失败');
    }

    /*
     * 上下架文章
     * @param int $id
     * @param int $status
     */
    public function check($id = 0,$status = 0) {
        $this->request->isAjax() || $this->error('非法请求');
        $knowledge = new \app\index\model\Knowledge();
        if($status == 0) {
            $msg = '下架成功';
        }else{
            $msg = '上架成功';
        }
        false !== $knowledge->where('id',$id)->update(['status'=>$status]) && $this->success($msg);
        $this->error('操作失败');
    }

    /*
     * 添加文章
     */
    public function addKnow($id = 0){
        $knowledge = new \app\index\model\Knowledge();
        if($this->request->isPost()) {
            $tutor_model = new Tutor();
            $params = $this->request->param();
            $data = [
                'title'=>$params['title'],'cat_id'=>$params['cat_id'],
                'imgurl'=>$params['imgurl'],'content'=>$params['content'],
                'uid'=>$this->tutor_id,'addtime'=>time(),'is_check'=>0,'check_time'=>''
            ];
            $validate = new \app\tutor\validate\Knowledge();
            if(!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $editid = $params['id'];
            if($editid) {
                if(false === $knowledge->where('id',$editid)->update($data)) {
                    $this->error('修改失败');
                }
                $this->success('修改成功');
            }
            Db::startTrans();
            if(!$knowledge->insert($data)) {
                $this->error('添加失败');
            }
            if(false === $tutor_model->where('uid',$this->tutor_id)->setInc('article_num',1)){
                Db::rollback();
                $this->error('添加失败');
            }
            Db::commit();
            $this->success('添加成功');
        }
        if($id) {
            $info = $knowledge->where('id',$id)->find();
            $this->assign('id',$id);
            $this->assign('info',$info);
        }
        $cate_model = new KnowledgeCate();
        $catelist = $cate_model->select();
        $this->assign('catelist',$catelist);
        return $this->fetch('add');
    }

    /*
     * 编辑
     */
    public function edit($id = 0) {
        $advanced_model = new \app\index\model\Advanced();
        $info = $advanced_model->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('id',$id);
        return $this->fetch('add');
    }

    /*
     * 导师证书
     */
    public function tutorCert() {
        $this->error('开发中');
        if($this->request->isAjax()){
            $cert = new TutorCert();
            $where = [];
            return $cert->getList($where);
        }
        return $this->fetch('tutorcert');
    }
}