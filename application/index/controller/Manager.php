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
use app\index\model\Message;
use app\index\model\Browsrecord;
use think\Db;
use think\Loader;
use think\Config;
use think\Url;
class Manager extends AdminBase {

    /**
     * 用户访问量分析
     */
    public function index(){
        $member_model = new \app\index\model\Member();
        if ($this->request->isAjax()) {
            $where = ['username'=>['neq','admin']];
            return $member_model->getList($where);
        }
        return $this->fetch();
    }

    /*
     * 编辑管理员
     * @param int $id
     * @return mixed
     */
    public function edit($uid = 0){
        $member_model = new \app\index\model\Member();
        $memberinfo = $member_model->where('uid',$uid)->find();
        $this->assign('managerinfo',$memberinfo);
        $this->assign('uid',$uid);
        return $this->fetch('add');
    }

    /*
     * 删除管理员
     * @param int $uid
     */
    public function del($uid = 0){
        $member_model = new \app\index\model\Member();
        $member_model->where('uid',$uid)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }

    /*
     * 添加管理员
     */
    public function add(){
        $member_model = new \app\index\model\Member();
        if ($this->request->isPost()) {
            $member_validate = new \app\index\validate\Member();
            $request = $this->request->param();
            //验证字段
            $data = [
                'username' => $request['username'],
                'password' => $request['password'],
            ];
            if (!$member_validate->check($data)) {
                return json_encode(['code' => 0, 'msg' => $member_validate->getError()]);
            }
            if($request['username'] == 'admin'){
                return json_encode(['code' => 0, 'msg' => '请勿添加admin账号']);
            }

            //存在图片则上传图片
            if (!empty($this->request->file('logo'))) {
                $info = uploadOss($this->request->file('logo'), '/username');
                if (false === $info['status']) {
                    $this->error($info['msg']);
                }
                $request['logo'] = $this->systeminfo['ossurl'].$info['data']['savepath'];
            }
            $msg = null;
            $id = $request['uid'];
            unset($request['uid']);
            if (empty($id)) { // 添加活动
                if($member_model->where('username',$request['username'])->find()){
                    return json_encode(['code' => 0, 'msg' => '该账号已添加']);
                }
                $salt = get_rand_char(6);
                $salt_pwd = splice_pwd($request['password'], $salt);
                $request['password'] = $salt_pwd;
                $request['salt'] = $salt;
                $member_model->insert($request);
                $msg = '赛事发布成功';
            } else {                          //修改赛事
                $where = ['uid' => $id];
                $memberinfo = $member_model->where($where)->find();
                $salt_pwd = splice_pwd($request['password'], $memberinfo['salt']);
                $request['password'] = $salt_pwd;
                if (false === $member_model->where($where)->update($request)) {
                    $this->error('服务器错误，请重试');
                }
                $msg = '赛事修改成功';
            }
            return json_encode(['uid' => $id, 'code' => 1, 'msg' => $msg, 'url' => Url::build('manager/index')]);
        }
        return $this->fetch();
    }
}
