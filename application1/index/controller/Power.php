<?php
// +----------------------------------------------------------------------
// | 功能：积分设置
// +----------------------------------------------------------------------
// | 作者: xiaomage
// +----------------------------------------------------------------------
// | 日期：2018-04-26
// +----------------------------------------------------------------------
namespace app\index\controller;
use app\index\controller\AdminBase;
use app\index\model\IntegralDetails;
use app\index\model\Member;
use app\index\model\Message;
use app\index\model\Browsrecord;
use app\index\model\AuthGroup;
use app\index\model\AuthGroupAccess;
use app\index\model\Menu;
use think\Db;
use think\Loader;
use think\Config;
use think\Url;
use think\Request;
use think\Session;
class Power extends AdminBase {

    /*权限列表*/
    public function rule_list(){
        $auth = new \think\Auth();
        $request = Request::instance();
        $m = $request->module();
        $c = $request->controller();
        $a = $request->action();
        $rule_name = $m.'/'.$c.'/'.$a;

        $result = $auth->check($rule_name,$this->partner['uid']);
        if(!$result){
            $this->error('您没有权限访问');
        }
        $menu_model = new Menu();
        $data = $menu_model->getTreeData('tree','id','name');
       
        $assign = array(
            'data'=>$data
        );
        $this->assign($assign);
        return $this->fetch();
    }

    /**
     * 角色列表
     */
    public function rule_group(){
        $group_model = new AuthGroup();
        $data = $group_model->select();
        $assign=array(
            'data'=>$data
        );
        $this->assign($assign);
        return $this->fetch();
    }

    /*
    * 管理员列表
    */
    public function user() {
        $member_model = new Member();
        if($this->request->isAjax()){
            $where = [];
            return $member_model->getUserList($where);
        }
        return $this->fetch();
    }

    /*
     * 删除管理员
     * @param int $uid
     */
    public function delUser($uid = 0) {
        $member_model = new Member();
        $member_model->where('uid',$uid)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }

    /*
     * 管理员
     */
    public function add_user($id = 0) {
        $group_model = new AuthGroup();
        $member_model = new Member();
        $group_access_model = new AuthGroupAccess();
        if ($this->request->isPost()) {
            Db::startTrans();
            $member_validate = new \app\index\validate\Member();
            $params = $this->request->param();
          	if(!isset($params['group_ids'])){
            	$this->error('请选择管理组');
            }
          if(!isset($params['username'])){
            	$this->error('请输入用户名');
            }
          if(!isset($params['password'])){
            	$this->error('请输入密码');
            }
            $request = [
                'uid'=>$params['uid'],'group_ids'=>$params['group_ids'],
                'username'=>$params['username'],'password'=>$params['password'],
            ];
            //存在图片则上传图片
            if (!empty($this->request->file('logo'))) {
                $file = request()->file('logo');
                $info = $file->move(ROOT_PATH . 'public' . DS . 'Uploads/');
                $url = GetCurUrl();
                $imgurl =  $url . DS . 'Uploads/' .$info->getSaveName();
                $request['logo'] = $imgurl;
            }
            $editId = $params['uid'];
            unset($params['uid']);
            $data = ['username' => $request['username']];
            if(!empty($editId) && empty($request['username'])){
                $this->error('请输入登录姓名');
                if($request['username'] == 'admin'){
                  	
                    return json_encode(['code'=>1,'msg'=>'请勿添加admin账号']);
                }
            }else if(empty($editId)){
                $data = [
                    'username' => $request['username'],
                    'password' => $request['password'],
                ];
                if (!$member_validate->check($data)) {
                    return json_encode(['code'=>1,'msg'=>$member_validate->getError()]);
                }
            }
            $msg = null;
            $group_ids = $request['group_ids'];
            unset($request['group_ids']);
            if (empty($editId)) {
                if($member_model->where('username',$request['username'])->find()){
                    return json_encode(['code'=>1,'msg'=>'该账号已添加']);
                }
                $salt = get_rand_char(6);
                $salt_pwd = splice_pwd($request['password'], $salt);
                $request['password'] = $salt_pwd;
                $request['salt'] = $salt;
                if(!$member_model->insert($request)){
                    return json_encode(['code'=>1,'msg'=>'添加失败']);
                }
                $msg = '添加成功';
            } else {
                $group_access_model->where(array('uid' => $editId))->delete();
                $where = ['uid' => $editId];
                $memberinfo = $member_model->where($where)->find();
                if(!empty($request['password'])){
                    $salt_pwd = splice_pwd($request['password'], $memberinfo['salt']);
                    $request['password'] = $salt_pwd;
                }else{
                    unset($request['password']);
                }
                if (false === $member_model->where($where)->update($request)) {
                    return json_encode(['code'=>1,'msg'=>'修改失败']);
                }
                $memberinfo = $member_model->where($where)->find();
                $partner = Session::set('memberinfo',$memberinfo);
                $msg = '修改成功';
            }

            $datagroup = $member_model->where(['username' => $request['username']])->find();
            if (!empty($group_ids)) {
                foreach ($group_ids as $k => $v) {
                    $group = array(
                        'uid' => $datagroup['uid'],
                        'group_id' => $v
                    );
                    if(!$group_access_model->insert($group)){
                        Db::rollback();
                        return json_encode(['code'=>1,'msg'=>'编辑失败']);
                    }
                }
            }
            Db::commit();
          	$this->success($msg);
            //return json_encode(['code'=>0,'msg'=>$msg]);
        }
        if($id) {
            $user_data = $member_model->where('uid',$id)->find();
            // 获取已加入用户组
            $group_data = $group_access_model->where(array('uid' => $id))->select();
            $groupids = [];
            foreach ($group_data as $val){
                $groupids[] = $val['group_id'];
            }
            $data = $group_model->select();
            $assign = array(
                'data' => $data,
                'user_data' => $user_data,
                'group_data' => $groupids
            );
            $this->assign($assign);
        }else{
            $data = $group_model->select();
            $assign = array(
                'data' => $data
            );
            $this->assign($assign);
        }

        return $this->fetch();
    }


    /**
     * 修改管理员
     */
    public function edit_user($id) {
        $group_model = new AuthGroup();
        $member_model = new Member();
        $group_access_model = new AuthGroupAccess();

        $user_data = $member_model->where('uid',$id)->find();
        // 获取已加入用户组
        $group_data = $group_access_model->where(array('uid' => $id))->select();
        $groupids = [];
        foreach ($group_data as $val){
            $groupids[] = $val['group_id'];
        }
        // 全部用户组
        $data = $group_model->select();
        $assign = array(
            'data' => $data,
            'user_data' => $user_data,
            'group_data' => $groupids
        );
        $this->assign($assign);
        return $this->fetch();
    }



    /*
     * 获取用户权限
     * @param int $uid
     */
    public function getpower($uid = 0){

        $power_model = new \app\index\model\Power();
        $member_model = new \app\index\model\Member();

            $memberinfo = $member_model->where(['uid'=>$uid])->find();

            if(empty($memberinfo['power_ids'])){
                $where = [];
                return $power_model->getList($where);
            }else{
                $power_ids = rtrim($memberinfo['power_ids'],',');
                $where = ['id'=>['in',$power_ids]];
                return $power_model->getList($where);
            }

        $this->assign('uid',$uid);
        return $this->fetch('userpower');
    }

    /*
     * 获取权限列表
     */
    public function getPowerList(){
        $power_model = new \app\index\model\Power();
        if ($this->request->isAjax()) {
            $where = [];
            return $power_model->getPowerList($where);
        }
        return $this->fetch('powerlist');
    }
}
