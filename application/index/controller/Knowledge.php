<?php

namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\AdverChild;
use app\index\model\KnowledgeAdver;
use app\index\model\KnowledgeCate;
use app\index\model\Tutor;
use app\index\model\TutorCert;
use think\Db;
use think\Loader;

/**
 * 涨知识
 * Class Advanced
 * @package app\index\controller
 */
class Knowledge extends Base
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
    public function article() {
        $knowledge = new \app\index\model\Knowledge();
        if($this->request->isAjax()){
            $params = $this->request->param();
            $where['is_delete'] = 0;
            (isset($params['title']) && !empty($params['title'])) && $where['title'] = ['like', '%' . $params['title'] . '%'];
            if(isset($params['status']) && $params['status'] != ''){
                $where['status'] = $params['status'];
            }
            if(isset($params['is_check']) && $params['is_check'] != ''){
                $where['is_check'] = $params['is_check'];
            }
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
     * @param string $type   up 上架  down下架  pass 通过  nopass 驳回
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function check($id = 0,$status = 0,$type = 'up',$refuse = '') {
        $this->request->isAjax() || $this->error('非法请求');
        $knowledge = new \app\index\model\Knowledge();
        if($type == 'up') {
            false !== $knowledge->where('id',$id)->update(['status'=>$status]) && $this->success('上架成功');
            $this->error('操作失败');
        }else if($type == 'down') {
            false !== $knowledge->where('id',$id)->update(['status'=>$status]) && $this->success('下架成功');
            $this->error('操作失败');
        }else if($type == 'pass') {
            false === $knowledge->where('id',$id)->update(['is_check'=>1,'status'=>1,'check_time'=>time()]) && $this->error('操作失败');
        }else if($type == 'nopass') {
            false === $knowledge->where('id',$id)->update(['is_check'=>2,'status'=>$status,'check_time'=>time()]) && $this->error('操作失败');
        }else{
            $this->error('非法类型');
        }
        $this->success('操作成功');
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
     * 导出用户
     */
    public function export(){
        $tutor = new Tutor();
        $where = [];
        $params = $this->request->param();
        (isset($params['name']) && !empty($params['name'])) && $where['name'] = ['like', '%' . $params['name'] . '%'];
        (isset($params['status']) && !empty($params['status'])) && $where['status'] = $params['status'];
        $list = $tutor->getList($where);
        $data = $list['rows'];
        Loader::import('PHPExcel.Classes.PHPExcel',VENDOR_PATH);
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objPHPExcel->getActiveSheet()->setCellValue('A1','导师名称');
        $objPHPExcel->getActiveSheet()->setCellValue('B1','专栏');
        $objPHPExcel->getActiveSheet()->setCellValue('C1','文章数量');
        $objPHPExcel->getActiveSheet()->setCellValue('D1','点赞数量');
        $objPHPExcel->getActiveSheet()->setCellValue('E1','赞赏作者数量');
        $objPHPExcel->getActiveSheet()->setCellValue('F1','关注人数');
        $objPHPExcel->getActiveSheet()->setCellValue('G1','粉丝人数');
        $objPHPExcel->getActiveSheet()->setCellValue('H1','状态');
        $objPHPExcel->getActiveSheet()->setCellValue('I1','申请时间');
        $objPHPExcel->getActiveSheet()->setCellValue('J1','审核时间');
        foreach ($data as $k=>$val){
            if($val['status'] == 1) {
                $status = '审核中';
            }else if($val['status'] == 2) {
                $status = '审核通过';
            }else if($val['status'] == 3) {
                $status = '审核驳回';
            }else if($val['status'] == 4) {
                $status = '拉黑';
            }else{
                $status = '状态异常';
            }
            $addtime = date('Y-m-d H:i:s',$val['addtime']);
            if($val['checktime']) {
                $checktime = date('Y-m-d H:i:s',$val['checktime']);
            }else{
                $checktime = '';
            }
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,$val['tutorname']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,$val['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,$val['article_num']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,$val['like_num']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,$val['comment_num']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,$val['follownum']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,$val['befollownum']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.$i,$status);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.$i,$addtime);
            $objPHPExcel->getActiveSheet()->setCellValue('J'.$i,$checktime);
        }
        // 1.保存至本地Excel表格
        $objWriter->save('导师数据.xls');
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="导师数据.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    /*
     * 审核导师
     */
    public function checkTutor($id = 0,$status = 0,$refuse = '') {
        $this->request->isAjax() || $this->error('非法请求');
        (empty($id) || empty($status)) && $this->error('缺少参数');
        $tutor = new Tutor();
        $user_model = new \app\index\model\User();
        $msg = '';
        Db::startTrans();
        $message_model = new \app\wxapp\model\Message();
        $uid = $tutor->where('id',$id)->value('uid');
        $tel = $user_model->where('id',$uid)->value('tel');
      	//echo $uid;exit;
        if($status == 2) { // 审核通过
            if(false === $tutor->where('id',$id)->update(['status'=>$status,'check_time'=>time()])) {
                $this->error('审核失败');
            }
            if(false === $user_model->where('id',$uid)->update(['is_tutor'=>1])) {
                Db::rollback();
                $this->error('审核失败');
            }
            $msg = '审核成功';
            $name = $tutor->where('id',$id)->value('name');
            $data = ['type'=>3,'title'=>'导师审核结果','abstract'=>'导师审核结果'.',审核成功，审核时间:'.date('Y-m-d H:i:s',time()),
                'content'=>'您已成为导师，快去发布文章吧。后台地址为:'.GetCurUrl()."/tutor/login/index,登录名为:".$name." 密码默认为:123456",
                'uid'=>$uid,'is_send'=>1,'send_time'=>time(),'addtime'=>time()];
            $pwd = "123456";
            $content = ['name' => $name,'pwd'=>$pwd];
            //var_dump($content);exit;
            vendor('aliyun-dysms-php-sdk.api_demo.SmsDemo');
          	if(empty($tel)){
            	Db::rollback();
                $this->error('审核失败,手机号不能为空');
            }
            $response = \SmsDemo::sendSms1($tel, $content);//echo 11111111111;exit;
            //$response = object_to_array($response);
            //if ($response['Message'] == 'OK') {
                if(!$message_model->insert($data)) {
                    Db::rollback();
                    $this->error('审核失败');
                }
            //} else {
             //   Db::rollback();
             //   $this->error($response['Message']);
            //}

        }else if($status == 3) {
            false === $tutor->where('id',$id)->update(['status'=>$status,'refuse'=>$refuse,'check_time'=>time()]) && $this->error('驳回失败');
            $msg = '驳回成功';
            $data = ['type'=>3,'title'=>'导师审核结果','abstract'=>'导师审核结果'.',审核失败，审核时间:'.date('Y-m-d H:i:s',time()),
                'content'=>"审核未通过，驳回原因:".$refuse,
                'uid'=>$uid,'is_send'=>1,'send_time'=>time(),'addtime'=>time()];
            if(!$message_model->insert($data)) {
                Db::rollback();
                $this->error('驳回失败');
            }
        }else if($status == 4) {
            if(false === $tutor->where('id',$id)->update(['status'=>$status,'check_time'=>time()])) {
                $this->error('审核失败');
            }
            if(false === $user_model->where('id',$uid)->update(['is_tutor'=>0])) {
                Db::rollback();
                $this->error('审核失败');
            }
            $data = ['type'=>3,'title'=>'警告','abstract'=>'你已被加入黑名单，时间:'.date('Y-m-d H:i:s',time()),
                'content'=>"加入黑名单原因:".$refuse,
                'uid'=>$uid,'is_send'=>1,'send_time'=>time(),'addtime'=>time()];
            if(!$message_model->insert($data)) {
                Db::rollback();
                $this->error('操作失败');
            }
            $msg = '已加入黑名单';
        }else if($status == 1) {
            if(false === $tutor->where('id',$id)->update(['status'=>1,'check_time'=>time()])) {
                $this->error('审核失败');
            }
            $msg = '已移出黑名单';
        }
        Db::commit();
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
     * 删除广告
     * @param int $id
     */
    public function delAdver($id = 0) {
        $this->request->isAjax() || $this->error('非法请求');
        $adver = new KnowledgeAdver();
        $adverChild = new AdverChild();
        $adver->where('id',$id)->delete() || $this->error('删除失败');
        $adverChild->where('adv_id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
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
     * 编辑子分类
     * @param int $id
     */
    public function editChildren($id = 0) {
        $adverChild = new AdverChild();
        if($this->request->isPost()) {
            $params = $this->request->param();
            if($params['type'] == 1) {
                $idvalue = 0;
            }else{
                $idvalue = $params['idvalue'];
            }
            $data = [
                'type'=>$params['type'],'link'=>$params['link'],
                'idvalue'=>$idvalue,'imgurl'=>$params['imgurl']
            ];
            $editId = $params['id'];
            if($editId) {
                if(false !== $adverChild->where('id',$editId)->update($data)){
                    $this->success('修改成功');
                }
                $this->error('修改错误');
            }
            $this->error('缺少参数');
        }
        $info = $adverChild->where('id',$id)->find();
        $this->assign('info',$info);
        $this->assign('id',$id);
        return $this->fetch('editchildren');
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
            $arrImgs = [];
            if (!empty($this->request->file('logo'))) {
                $info = uploadOss($this->request->file('logo'), '/images');//路径上传到/goods
                if (false === $info['status']) return json_encode(['code' => 0, 'msg' => $info['msg']]);
                $arrImgs = $this->disposeImg($info['data']);
            }
          	//var_dump($arrImgs);exit;
            foreach ($arrImgs as $val) {
                for ($i = 0; $i < count($params['type']); $i++) {
                    if(empty($params['imgurl'][$i])) {
                      
                        $params['imgurl'][$i] = $val;
                    }
                }

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
                $msg = '修改成功';
            }
			
            $children = $this->disposeData($params, 1, $adv_id);
            Db::startTrans();
            if (!empty($params['id'])) {
                //如果是修改，先删除原有的规格，重新添加
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

    /*
     * 组装传过来的数据
     * @author Steed
     * @param $request
     * @param int $type 0
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
                $data[$i]['imgurl']           = $request['imgurl'][$i];
                $data[$i]['addtime']    = time();
            }
        }
        return $data;
    }


    /*
     * 处理上传的图片
     * @author Steed
     * @param $img
     * @return string
     */
    private function disposeImg($img) {
        $tmp = [];
        foreach ($img as $value) {
            $tmp[] = $this->systeminfo['ossurl'].$value['savepath'] . $value['savename'];
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