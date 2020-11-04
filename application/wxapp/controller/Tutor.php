<?php
namespace app\wxapp\controller;

use app\wxapp\controller\Base;
use app\index\model\KnowledgeCate;
use think\Db;
use think\Request;

/*
 * 导师专栏
 */
class Tutor extends Base{
    /*
     * 导师申请入住
     */
    public function applyTutor($RequestId='') {
        $token = input('token');
        if(!empty($token)) {

            $this->getUserInfo($token);
        }
        $request = Request::instance();
        $ip = $request->ip();

        if($RequestId!==cache($ip)){
            return returnjson(1001,'','验证码错误');
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $name = input('name');
        $imgurl = input('imgurl');
        $content = input('content');
        $apply_type = input('apply_type');
        $tutor_model = new \app\index\model\Tutor();
        if($tutor_model->where('name',$name)->find()){
            return returnjson('1001','','该专栏已被占用');
        }
        $tutorInfo = $tutor_model->where('uid',$this->uid)->find();
        $data = [
            'imgurl'=>$imgurl,
            'name'=>$name,
            'content'=>$content,
            'apply_type'=>$apply_type
        ];
        $pwd = '123456';
        $salt = get_rand_char(4);
        $password = splice_pwd($pwd, $salt);
        $data['uid'] = $this->uid;
        $data['status'] = 1;
        $data['addtime'] = time();
        $data['salt'] = $salt;
        $data['password'] = $password;

        if($tutorInfo) {
            if($tutorInfo['status'] == 4) {
                return returnjson('1001','','申请失败，请联系我们');
            }
            if($tutorInfo['status'] == 1) {
                return returnjson('1001','','请勿重复申请');
            }else{
                if(false === $tutor_model->where('uid',$this->uid)->update($data)) {
                    return returnjson('1001','','申请失败');
                }
                return returnjson('1000','','已提交申请,请耐心等待审核');
            }
        }
        $tutor_validate = new \app\wxapp\validate\Tutor();
        if(!$tutor_validate->check($data)) {
            return returnjson('1001','',$tutor_validate->getError());
        }
        if($tutor_model->insert($data)) {
            return returnjson('1000','','已提交申请,请耐心等待审核');
        }
        return returnjson('1001','','申请失败');
    }
    /*
     * 导师列表
     * @param int $page
     */
    public function tutorList($page = 1,$type = 1) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $tutor = new \app\wxapp\model\Tutor();
        $where = ['status'=>2,'type'=>$type];
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        return $tutor->getApiList($where,$limit);
    }
    /*
     * 关注导师
     * @param int $page
     */
     public function follow_tutor($tutor_id,$type){
         $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson(1100,'','该设备在其他地方登录');
        }
        if(empty($tutor_id)) {
            return returnjson(1001,'','参数缺失');
        }
        if(empty($type)){
            $data = array(
            'uid' =>$this->uid,
            'tutor_id' => $tutor_id,
            'addtime' =>time()
            );
           Db::name('tutor_follow')->insert($data);
           return returnjson(1000,'','已关注');
        }else{
            Db::name('tutor_follow')->where(['uid'=>$this->uid,'tutor_id'=>$tutor_id])->delete();
            return returnjson(1000,'','已取消关注');
        }


     }
    /*
     * 导师类别
     */
    public function tutorType() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $cate = new KnowledgeCate();
        $data = $cate->order('sort')->select();
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 导师详情
     */
    public function tutorDetail($id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $tutor = new \app\index\model\Tutor();
        $data = $tutor->field('id,imgurl,article_num,comment_num,name,content,like_num')->where('id',$id)->find();
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 导师文章
     */
    public function articleList($page = 1,$tutor_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $tutor = new \app\index\model\Tutor();
        $data = $tutor->field('uid')->where('id',$tutor_id)->find();
        $article = new \app\index\model\Knowledge();
        $where = ['uid'=>$data['uid'],'status'=>1,'is_delete'=>0,'is_check'=>1];
        $start = ($page - 1) * $this->num;
        $limit = $start.','.$this->num;
        return $article->getApiArticleList($where,$limit);
    }
}

