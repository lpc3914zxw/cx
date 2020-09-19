<?php
namespace app\wxapp\controller;
use app\common\Common;
use app\wxapp\controller\Base;
use think\Db;
use think\Config;
use app\wxapp\model\MessageReadLog;
/*
 * 帮助文档
 * Class Helps
 * @package app\wxapp\controller
 */
class Helps extends Base {
    
     /**
     *  创建静态页面
     * @access protected
     * @htmlfile 生成的静态文件名称
     * @htmlpath 生成的静态文件路径
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @return string
     */
    protected function buildHtml($htmlfile='',$htmlpath='',$templateFile='') {
        $content=$this->fetch($templateFile);
        $htmlpath=!empty($htmlpath)?$htmlpath:HTML_PATH;
        $htmlfile=$htmlpath.$htmlfile.Config::get('HTML_FILE_SUFFIX');
        if(!is_dir(dirname($htmlfile)))//如果静态目录不存在则创建
        //$htmlfile = 'html/about.html';
            mkdir(dirname($htmlfile));
        if(false===file_put_contents($htmlfile,$content))
            throw_exception(L('_CACHE_WRITE_ERROR_').':'.$htmlfile);
        return $content;
    }
    /**
     * 帮助文档
     * @param int $type  1 热门问题  2 常见问题
     * @param int $page  页码
     * @return array
     */
   public function helpList($type = 1,$page = 1) {
       $token = input('token');
       if(!empty($token)) {
           $this->getUserInfo($token);
       }
       if($this->uid == 0) {
           return returnjson('1100','','该设备在其他地方登录');
       }
       $help_model = new \app\index\model\Helps();
       $start = ($page - 1) * $this->num;
       $where = ['type'=>$type];
       $limit = $start.','.$this->num;
       return $help_model->getApiList($where,$limit);
   }
   
   public function helpDetaile($id = 1) {
       if(file_exists('html/helps/helpdetaile'.$id.'.html')){
           include 'html/helps/helpdetaile'.$id.'.html';exit;
       }
       $help_model = new \app\index\model\Helps();
       $data = $help_model->getDetile($id);
       $this->assign('data',$data);
       $this->buildHtml('helpdetaile'.$id, 'html/helps/', 'helpdetaile');
       return $this->fetch();
   }

    public function uploadImg() {
        $info = uploadOss($this->request->file('file'), '/images');//路径上传到/goods
        if (false === $info['status']) {
            return returnjson('1001','',$info['msg']);
        }
        $arrImgs = $this->disposeImg($info['data']);
        return returnjson('1000',$arrImgs,'上传成功');
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
     * 反馈问题
     */
   public function feedBack() {
       $token = input('token');
       if(!empty($token)) {
           $this->getUserInfo($token);
       }
       if($this->uid == 0) {
           return returnjson('1100','','该设备在其他地方登录');
       }
       $params = $this->request->param();
       $feedBack = new \app\wxapp\model\Feedback();
       //if (!preg_match("/^1[3456789]\d{9}$/", $params['tel'])) {
           //return returnjson('1001', '', '手机号码有误!');
       //}
     $user_model = new \app\wxapp\model\User();
        $tel = $user_model->where('id',$this->uid)->value('tel');
       $data = [
           'uid'=>$this->uid,'content'=>$params['content'],'imgurls'=>$params['imgurls'],
           'tel'=>$tel,'addtime'=>time()
       ];
		
     	
       Db::startTrans();
       if($feedBack->insert($data)) {
           if(mb_strlen($params['content']) > 10) {
               $common = new Common();
               $content = '反馈意见';
               if(false === $common->dedicationLog($this->uid,5,'',$content)) {
                   Db::rollback();
                   return returnjson('1001','','反馈失败');
               }
           }
           Db::commit();
           return returnjson('1000','','反馈成功');
       }
       Db::commit();
       return returnjson('1001','','反馈失败');
   }
   //才学酒馆通知
    public function cxMessginDetail($id){
        $uid = input('uid');
        $messageReadLog = new MessageReadLog();
         $message_model = new \app\index\model\Message();
        $info = $message_model->field('title,abstract,content,id,addtime,send_time,type')->where(['id'=>$id,'type'=>1])->find();
        $info['send_time'] = date('Y-m-d',$info['send_time']);
        if(!$messageReadLog->where(['msg_id'=>$id,'uid'=>$uid])->find()) {
            $data = ['type'=>$info['type'],'uid'=>$uid,'msg_id'=>$id,'addtime'=>time()];
            $messageReadLog->insert($data);
        }
        if(file_exists('html/clause/cxmessgindetail'.$id.'.html')){
           include 'html/clause/cxmessgindetail'.$id.'.html';exit;
        }
        
        if($info){
            $info['addtime'] = date('m月d日',$info['addtime']);
        }
        $about_model = new \app\wxapp\model\Clause();
        $this->assign('data',$info);
        $this->buildHtml('cxmessgindetail'.$id, 'html/clause/', 'cxmessgindetail');

        
        
        
        
        return $this->fetch();
        
    }
}

