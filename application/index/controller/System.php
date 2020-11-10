<?php
namespace app\index\controller;
use app\index\model\HonorSet;
use app\index\model\PosterTemp;
use think\Controller;
use app\index\controller\AdminBase;

use think\Cookie;
use think\Session;
use app\index\model\Message;
use think\Db;
/**
 * Class Index
 * @author Steed
 * @package app\index\controller
 */
class System extends AdminBase {
    /*
     * 腾讯云公共参数设置
     * @return mixed
     */
    public function cloudset(){
        $system_model = new \app\index\model\System();
        if($this->request->isPost()){
            $params = $this->request->param();
            $id = $params['id'];
            unset($params['id']);
            $data = [
                'bucket'=>$params['bucket'],'accesskey'=>$params['accesskey'],'endpoint'=>$params['endpoint'],
                'secretsecret'=>$params['secretsecret'],'ossurl'=>$params['ossurl']
            ];
            if($id){
                false !== $system_model->where('id',$id)->update($data) && $this->success('修改成功');
                $this->error('修改失败');
            }else{
                $system_model->insert($data) && $this->success('添加成功');
                $this->error('添加失败');
            }
        }
        $setinfo = $system_model->find();
        $this->assign('setinfo',$setinfo);
        return $this->fetch();
    }

    /*
     * 修改密码
     * @return mixed
     */
    public function editpwd($oldpwd = '',$newpwd= '',$newpwd2 = ''){
        if($this->request->isPost()) {
            $oldpwd = trim($oldpwd);
            $newpwd2 = trim($newpwd2);
            $newpwd = trim($newpwd);
            $member_model = new \app\index\model\Member();
            $this->request->isAjax() || $this->error('非法请求');
            (empty($oldpwd) || empty($newpwd) || empty($newpwd2)) && $this->error('缺少参数');
            if($newpwd != $newpwd2){
                $this->error('两次密码不一致');
            }
            $memberinfo = $member_model->field('salt,password')->where('uid',$this->partner['uid'])->find();
            $salt_pwd = splice_pwd($oldpwd, $memberinfo ['salt']);
            if($memberinfo['password'] != $salt_pwd){
                //$this->error('原密码错误');
                return DataReturn('原密码错误', 1000);
            }
            $new_pwd = splice_pwd($newpwd, $memberinfo ['salt']);
            if($member_model->where('uid',$this->partner['uid'])->update(['password'=>$new_pwd]) !== false) {
                Cookie::set('memberinfo', '');
                Session::set('memberinfo', '');
                $this->success('更新成功');
            }
            $this->error('更新失败');
        }
        return $this->fetch();
    }

   /*
     * 关于我们
     */
    public function about_us() {
        $about = Db::name('system')->where('id',1)->field('about,useprotocol,privacyprotocol,cxuseprotocol,cxprivacyprotocol,TOS,peters_contert')->find();
        if($this->request->isPost()) {
            $post = $this->request->post();
          	$post['tos'] = $post['TOS'];
          unset($post['TOS']);
            if(Db::name('system')->where('id',1)->update($post) !== false) {
                $this->success('更新成功');
            }
            $this->error('更新失败');
        }
        $this->assign('about',$about);
        return $this->fetch('aboutus');
    }
    /*
     * 关于我们
     */
    public function aboutus() {
        //var_dump($this->request->post());exit;
        if($this->request->isPost()) {
            $post = $this->request->post();
          $post['tos'] = $post['TOS'];
          unset($post['TOS']);

            if(Db::name('system')->where('id',1)->update($post) !== false) {
                if(file_exists('html/clause/tos.html')){
                    unlink('html/clause/tos.html');
                }
                if(file_exists('html/clause/cxprivacyprotocol.html')){
                    unlink('html/clause/cxprivacyprotocol.html');
                }
                if(file_exists('html/clause/cxuseprotocol.html')){
                    unlink('html/clause/cxuseprotocol.html');
                }
                if(file_exists('html/clause/privacyprotocol.html')){
                    unlink('html/clause/privacyprotocol.html');
                }
                if(file_exists('html/clause/useprotocol.html')){
                    unlink('html/clause/useprotocol.html');
                }
                if(file_exists('html/clause/about.html')){
                    unlink('html/clause/about.html');
                }
                if(file_exists('html/clause/peters_contert.html')){
                    unlink('html/clause/peters_contert.html');
                }

                $this->success('更新成功');
            }
            $this->error('更新失败');
        }
        return $this->fetch('aboutus');
    }

    /*
     * 添加海报模板
     */
    public function addposter($id = 0) {
        $post_temp = new PosterTemp();
        if($this->request->isPost()) {
            $params = $this->request->param();
            $data = ['url'=>$params['url'],'name'=>$params['name'],'addtime'=>time(),'type'=>$params['type'],'note'=>$params['note']];
            if($this->request->file('logo')) {
                $file = request()->file('logo'); // 移动到框架应用根目录/public/uploads/ 目录下
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                if($info) {
                    $filename = $info->getSaveName();
                    $url = "https://".$_SERVER['SERVER_NAME']."/uploads/".$filename;
                    $data['url'] = $url;
                }
            }
            if(empty($data['url'])) {
                return json_encode(['code' => 0, 'msg' => '请上传模板图片']);
            }
            if(empty($data['name'])) {
                return json_encode(['code' => 0, 'msg' => '名称不能为空']);
            }
            $editId = $params['id'];
            if($editId) {
                if(false !== $post_temp->where('id',$editId)->update($data)) {
                    return json_encode(['code' => 1, 'msg' => '修改成功']);
                }
                return json_encode(['code' => 0, 'msg' => '名称不能为空']);
            }
            if($post_temp->insert($data)) {
                return json_encode(['code' => 1, 'msg' => '修改成功']);
            }
            return json_encode(['code' => 0, 'msg' => '名称不能为空']);
        }
        if($id) {
            $info = $post_temp->where('id',$id)->find();
            $this->assign('id',$id);
            $this->assign('info',$info);
        }
        return $this->fetch();
    }

    /*
     * 删除海报
     * @param int $id
     */
    public function delPoster($id = 0) {
        $post_temp = new PosterTemp();
        $post_temp->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }

    /*
     * 是否启用
     */
    public function changeStatus($id = 0,$status = 0) {
        $post_temp = new PosterTemp();
        false !== $post_temp->where('id',$id)->update(['status'=>$status]) && $this->success('操作成功');
        $this->error('操作失败');
    }

    /*
     * 海报模板
     */
    public function posterTemp() {
        if($this->request->isAjax()) {
            $post_temp = new PosterTemp();
            if($this->request->isAjax()){
                return $post_temp->getList();
            }
        }
        return $this->fetch('poster_temp');
    }
    /*
     * 上传邀请海报模板
     */
    public function uploadPostImg() {
        $file = request()->file('logo'); // 移动到框架应用根目录/public/uploads/ 目录下
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if($info) {
            $filename = $info->getSaveName();
            $fileurl = "https://".$_SERVER['SERVER_NAME']."/uploads/".$filename;
            return json_encode(['code' => 0,'data' =>$fileurl,'msg'=>'上传成功']);
        }
    }

    public function appSet() {

        $system_model = new \app\index\model\System();
        if($this->request->isPost()) {
            $params = $this->request->param();
            $id = $params['id'];
            $data = [
                'exchangenote'=>$params['exchangenote'],
                'colliers_note'=>$params['colliers_note'],
                'poster_temp'=>$params['poster_temp'],
                'reward_min'=>$params['reward_min'],
                'reward_max'=>$params['reward_max'],
                'adv_step'=>$params['adv_step'],
                'rankimg'=>$params['rankimg'],
                'tel'=>$params['tel'],
                'email'=>$params['email'],
                'app_note'=>$params['app_note'],
                'version'=>$params['version'],
                'inviteimg'=>$params['inviteimg'],
                'introduce'=>$params['introduce'],
                'levels'=>$params['levels'],
                'startlevels'=>$params['startlevels'],
                'classimg'=>$params['classimg'],
                'andown'=>$params['andown'],
              'iosdown'=>$params['iosdown'],
                'authnum'=>$params['authnum']
            ];
           //return ['data' =>$data];
            if($system_model->where('id',$id)->update($data)){
            	$this->success('编辑成功');
            }else{
            // return $system_model->getLastSql();exit;
            	$this->error('编辑失败');
            }

        }
        $info = $system_model->find();
        //var_dump($info);exit;
        $this->assign('info',$info);
        return $this->fetch();
    }
    /*
    * 提示语
    */
    public function notice() {
        $notice_model = new \app\index\model\Notice();
        if($this->request->isAjax()){
            $where = [];
            return $notice_model->getList($where);
        }
        return $this->fetch();
    }
    /*
     *添加提示语
     */
    public function addnotice($id = 0) {
        $notice_model = new \app\index\model\Notice();
        if ($this->request->isPost()) {
            $request = $this->request->param();
            $data = [
                'name'=>$request['name'],
                'sort'=>$request['sort'],
                'addtime'=>time()
            ];
            $id = $request['id'];
            if (empty($id)) {
                if(!$notice_model->insert($data)){
                    $this->error('添加失败');
                }
                $msg = '添加成功';
            } else {                          //修改赛事
                $where = ['id' => $id];
                if (false === $notice_model->where($where)->update($data)) {
                    $this->error('修改失败');
                }
                $msg = '修改成功';
            }
            $this->success($msg);
        }
        $info = $notice_model->where('id',$id)->find();
        $this->assign('info',$info);
        return $this->fetch();
    }


    public function delnotice($id) {
         $Notice_model = new \app\index\model\Notice();
        $Notice_model->where('id',$id)->delete() && $this->success('删除成功');
        $this->error('删除失败');
    }

}
