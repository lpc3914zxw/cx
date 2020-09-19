<?php

namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\AdverChild;
use app\index\model\KnowledgeAdver;
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
    /**
     * 分类
     * @return array|mixed
     */
    public function category() {
        $cate = new KnowledgeCate();
        if($this->request->isAjax()){
            $where = [];
            return $cate->getList($where);
        }
        return $this->fetch();
    }

    /**
     * 编辑分类
     */
    public function addCategory($id = 0) {
        $cate = new KnowledgeCate();
        if($this->request->isPost()) {
            $param = $this->request->param();
            $data = [
                 'name'=>$param['name'],
                 'sort'=>$param['sort']
            ];
            $editId = $param['id'];
            if($editId) {
                false !== $cate->where('id',$editId)->update($data) && $this->success('修改成功');
                $this->error('修改失败');
            }
            $cate->insert($data) && $this->success('添加成功');
            $this->error('添加失败');
        }
        if($id) {
            $info = $cate->where('id',$id)->find();
            $this->assign('id',$id);
            $this->assign('info',$info);
        }
        return $this->fetch('add');
    }

    /*
     * 删除分类
     * @param int $id
     */
    public function delCate($id = 0) {
        $cate = new KnowledgeCate();
        $knowledge = new \app\index\model\Knowledge();
        if($knowledge->where('cat_id',$id)->find()) {
            $this->error('该分类下有文章,请先删除文章');
        }
        $cate->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }

    /*
     * 涨知识列表
     */
    public function index() {
        $knowledge = new \app\index\model\Dayarticle();
        if($this->request->isAjax()){
            $where = ['is_delete'=>0];
            return $knowledge->getList($where);
        }
        return $this->fetch('index');
    }

    /*
     * 编辑
     */
    public function edit($id = 0) {
        $knowledge = new \app\index\model\Knowledge();
        $info = $knowledge->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('id',$id);
        return $this->fetch('add');
    }

    /*
     * 上下架文章
     * @param int $id
     * @param int $status
     * @throws \think\Exception
     * @throws \think\exception\PDOException
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
     * 批量删除
     * @param string $ids
     */
    public function piDel($ids = []) {
        $this->request->isAjax() || $this->error('非法请求');
        $knowledge = new \app\index\model\Knowledge();
        false !== $knowledge->where(['id'=>['in',$ids]])->update(['is_delete'=>1]) && $this->success('已删除');
        $this->error('删除失败');
    }

    /*
     * 导师
     */
    public function tutor() {
        if($this->request->isAjax()){
            $tutor = new Tutor();
            $where = [];
            $params = $this->request->param();
            (isset($params['name']) && !empty($params['name'])) && $where['name'] = ['like', '%' . $params['name'] . '%'];
            (isset($params['status']) && !empty($params['status'])) && $where['status'] = $params['status'];
            return $tutor->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 审核导师
     */
    public function checkTutor($id = 0,$status = 0) {
        $this->request->isAjax() || $this->error('非法请求');
        (empty($id) || empty($status)) && $this->error('缺少参数');
        $tutor = new Tutor();
        $msg = '';
        if($status == 2) { // 审核通过
            false === $tutor->where('id',$id)->update(['status'=>$status]) && $this->error('审核失败');
            $msg = '审核成功';
        }else if($status == 3) {
            false === $tutor->where('id',$id)->update(['status'=>$status]) && $this->error('驳回失败');
            $msg = '驳回成功';
        }else if($status == 4) {
            false === $tutor->where('id',$id)->update(['status'=>$status]) && $this->error('操作失败');
            $msg = '已加入黑名单';
        }else if($status == 1) {
            false === $tutor->where('id',$id)->update(['status'=>$status]) && $this->error('操作失败');
            $msg = '已移出黑名单';
        }
        $this->success($msg);
    }

    /*
     * 导师证书
     */
    public function tutorCert() {
        if($this->request->isAjax()){
            $cert = new TutorCert();
            $where = [];
            return $cert->getList($where);
        }
        return $this->fetch('tutorcert');
    }

    /**
     * 颁发证书
     */
    public function awardCert() {

        return $this->fetch('awardcert');
    }

    /*
     * 轮播图
     * @return mixed
     */
    public function adver() {
        $adver = new KnowledgeAdver();
        if($this->request->isAjax()) {
            $where = [];
            return $adver->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 开启/关闭广告
     * @param int $id
     * @param int $is_open
     */
    public function openAdver($id = 0,$is_open = 0) {
        $adver = new KnowledgeAdver();
        false !== $adver->where('id',$id)->update(['is_open'=>$is_open]) && $this->success('操作成功');
        $this->error('操作失败');
    }


    /*
     * 编辑广告
     */
    public function addAdver($id = 0) {
        $adver = new KnowledgeAdver();
        if($this->request->isPost()) {
            $params = $this->request->param();
            $data = [
                'name' =>$params['name'],
                'kind'=>$params['kind'],
                'sort'=>$params['sort'],
                'addtime'=>time()
            ];
            if (!empty($this->request->file('logo'))) {
                $info = uploadCos($this->request->file('logo'), '/images');//路径上传到/goods
                if (false === $info['status']) return json_encode(['code' => 0, 'msg' => $info['msg']]);
                $params['images'] = $this->disposeImg($info['data']);
            }
            $adv_id = null;
            $adverChild = new AdverChild();
            if (empty($params['id'])) {
                //添加商品
                $adv_id = $adver->addAdv($data);
                if (!$adv_id) {
                    return json_encode(['code' => 0, 'msg' => '添加失败']);
                }
                $msg = '添加成功';
            } else {
                //修改商品
                $adv_id = $params['id'];
                $where = ['id' => $adv_id];
                if (!$adver->updateAdv($data, $where)) {
                    return json_encode(['code' => 0, 'msg' => '更新失败']);
                }
                $msg = '商品修改成功';
            }

            $children = $this->disposeData($params, 1, $adv_id);
            Db::startTrans();
            if (!empty($params['id'])) {
                //如果是修改商品，先删除原有的规格，重新添加
                $where = ['adv_id' => $params['id']];
                if(!$adverChild->delChilds($where)) {
                    Db::rollback();
                    return json_encode(['code' => 0, 'msg' => '编辑失败']);
                }
            }
            if (!$adverChild->addChilds($children)) {
                Db::rollback();
                return json_encode(['code' => 0, 'msg' => '编辑失败']);
            }
            Db::commit();
            return json_encode(['code' => 1, 'msg' => $msg]);
        }
        if($id) {
            $info = $adver->where('id',$id)->find();
            $adverChild = new AdverChild();
            $children = $adverChild->where('adv_id',$id)->select();
            $info['children'] = $children;
            $this->assign('id',$id);
            $this->assign('info',$info);
        }
        return $this->fetch();
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


    /**
     * 处理上传的图片
     * @author Steed
     * @param $img
     * @return string
     */
    private function disposeImg($img) {
        $tmp = [];
        foreach ($img as $value) {
            $tmp[] = $this->systeminfo['cosurl'].$value['savepath'] . $value['savename'];
        }
        return $tmp;
    }


    /*
     * 广告列表
     */
    public function adverChild($adv_id = 0) {
        if($this->request->isAjax()) {
            $adverChild = new AdverChild();
            $where = ['adv_id'=>$adv_id];
            return $adverChild->getList($where);
        }

    }
}