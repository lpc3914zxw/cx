<?php
namespace app\tutor\controller;
use app\tutor\controller\Base;
use app\tutor\model\Tutor;
use app\wxapp\model\TutorFollow;
use think\Session;
use think\Cookie;
use app\index\model\KnowledgeCate;
/**
 * Class Index
 * @author Steed
 * @package app\index\controller
 */
class Index extends Base {
    public function index() {
//        $data = Session::get('memberinfo');
//        $this->assign('data',$data);
        return $this->fetch();
    }

    /*
     * 我的信息
     * @return mixed
     */
    public function myInfo() {
        $cate_model = new KnowledgeCate();
        $cate = $cate_model->order('sort desc')->select();
        $tutor_model = new Tutor();
        if($this->request->isPost()) {
          
            $tutor_validate = new \app\tutor\validate\Tutor();
            $params = $this->request->param();
         
         	
            if (!empty($this->request->file('logo'))) {
              	
                $info = uploadOss($this->request->file('logo'), '/images');//路径上传到/goods
             	 if (false === $info['status']) return json_encode(['code' => 0, 'msg' => $info['msg']]);
                $params['imgurl'] = $this->systeminfo['ossurl'].$info['data']['savepath'];
              
            }
          	
            $data = [
                'name'=>$params['name'],
                'imgurl'=>$params['imgurl'],
                'content'=>$params['content'],
                'type' =>$params['type']
            ];
            if(!empty($params['password'])) {
                if($params['password'] != $params['rpwd']) {
                    return json_encode(['code' => 0, 'msg' => '密码不一致']);
                }
                $salt_pwd = splice_pwd($params['password'], $this->tutorinfo['salt']);
                $password = $tutor_model->where('uid',$this->tutor_id)->value('password');
                $data['password'] = $salt_pwd;
            }
            if(!$tutor_validate->check($data)) {
                $this->error($tutor_validate->getError());
            }
            if(false !== $tutor_model->where('uid',$this->tutor_id)->update($data)) {
                if(!empty($data['password']) && ($data['password'] != $password)) {
                    Cookie::set ( 'tutorinfo', '' );
                    Session::set ( 'tutorinfo', '' );
                }
                return json_encode(['code' => 1, 'msg' => '修改成功']);
            }
            return json_encode(['code' => 0, 'msg' => '修改失败']);
        }
        $info = $tutor_model->where('uid',$this->tutor_id)->find();
        
        $this->assign('cate',$cate);
        $this->assign('info',$info);
        return $this->fetch('myinfo');
    }
   
}
