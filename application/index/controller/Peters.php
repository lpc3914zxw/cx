<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\PetersLog;
use app\index\model\PetersSet;
use think\Db;
use think\Loader;
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
            $params = input();
            $where = [];
            //$name = input['name'];
            if(isset($params['status'])&&$params['status']!=''){
                $where['d.status'] = $params['status'];
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['d.addtime'] = array('between',$time1.','.$time2);
            }

            if(!empty($params['name'])){
                if(!empty($params['name'])){
                    $where['u.tel|u.name'] = ['like','%'.$params['name'].'%'];
                }
            }
            return $peterslog_model->getList($where);
        }
        return $this->fetch('index');
    }
    public function list(){
        $user_model = new \app\index\model\User();
        
        $where = [];
        
        $params = input('param.');
        
        $where = [];
       
        if($this->request->isAjax()){
            $where['u'] = [];
            
           $where['u']['u.is_peters'] = 1;
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['u']['u.peterstime'] = array('between',$time1.','.$time2);
            }
            $where['s'] = [];
            
            if(!empty($params['name'])){
                $where['s']['u.id'] = $params['name'];
                $where['s']['u.name'] = $params['name'];
                $where['s']['u.tel'] = $params['name'];
            }

            $list = $user_model->get_list($where);
            return $list;
        }
        return $this->fetch();
    }
    /*
     * 审核
     */
    public function checkPeters($id = 0,$status,$time1='',$refuse='') {
        $this->request->isAjax() || $this->error('非法请求');
        (empty($id)) && $this->error('缺少参数');
        $peterslog_model = new PetersLog();
        $user_model = new \app\index\model\User();
        $msg = '';
        Db::startTrans();
        $message_model = new \app\wxapp\model\Message();
        $uid = $peterslog_model->where('id',$id)->value('uid');
        $countyid = $peterslog_model->where('id',$id)->value('county');
        $tel = $user_model->where('id',$uid)->value('tel');
        
      	
        if($status == 1) { // 审核通过
            $expiretime = $time1;
            if(empty($expiretime)){
                $this->error('结束时间不能为空');
            }
            
            $is_apply = Db::name('region')->where('id',$countyid)->value('is_apply');
            if($is_apply==1){
                // return returnjson(1001,'','该地区已被申请！');
                 $this->error('该地区已被申请');
            }
            $expiretime=strtotime($expiretime);
            
            $peterslog = $peterslog_model->where('id',$id)->find();
            $province = Db::name('region')->where('id',$peterslog['province'])->value('name');
            $city = Db::name('region')->where('id',$peterslog['city'])->value('name');
            $county = Db::name('region')->where('id',$peterslog['county'])->value('name');
            if(false === $peterslog_model->where('id',$id)->update(['status'=>1,'uptime'=>time()])) {
                $this->error('审核失败');
            }
            if(false === $user_model->where('id',$uid)->update(['is_peters'=>1,'peterstime'=>time(),'peters_provinceid'=>$peterslog['province'],'peters_cityid'=>$peterslog['city'],'peters_areaid'=>$peterslog['county'],'peters_province'=>$province,'peters_city'=>$city,'peters_area'=>$county,'peters_expire_time'=>$expiretime])) {
                Db::rollback();
                $this->error('审核失败');
            }
            if(!Db::name('region')->where('id',$countyid)->update(['is_apply'=>1])) {
                Db::rollback();
                $this->error('更改地址状态失败');
            }
            
            $msg = '审核成功';
            $name = $user_model->where('id',$uid)->value('name');
            $data = ['type'=>3,'title'=>'堂主审核结果','abstract'=>'堂主审核结果'.',审核成功，审核时间:'.date('Y-m-d H:i:s',time()),
                'content'=>'您已成为财学堂堂主',
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

        }else if($status == 2) {
            false === $peterslog_model->where('id',$id)->update(['status'=>$status,'refuse'=>$refuse,'uptime'=>time()]) && $this->error('驳回失败');
            $msg = '驳回成功';
            $data = ['type'=>3,'title'=>'堂主审核结果','abstract'=>'堂主审核结果'.',审核失败，审核时间:'.date('Y-m-d H:i:s',time()),
                'content'=>"审核未通过，驳回原因:".$refuse,
                'uid'=>$uid,'is_send'=>1,'send_time'=>time(),'addtime'=>time()];
            if(!$message_model->insert($data)) {
                Db::rollback();
                $this->error('驳回失败');
            }
        }
        Db::commit();
        $this->success($msg);
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
                'val'=>$request['val'],
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
