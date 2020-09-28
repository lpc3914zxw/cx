<?php
namespace app\wxapp\controller;
use app\wxapp\controller\Base;
use app\wxapp\model\MessageReadLog;
use think\Config;
class Message extends Base {

    /*
     * 才学酒馆
     * @param int $type
     */
    public function getSysMsg($page = 1) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $message_model = new \app\index\model\Message();
        $list = $message_model->field('title,abstract,content,id,addtime')->where(['type'=>1,'is_send'=>1])->page($page,$this->num)->order('addtime','desc')->select();
        foreach ($list as $k=>$val) {
            $list[$k]['addtime'] = date('Y-m-d',$val['addtime']);
            $list[$k]['url'] = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/helps/cxMessginDetail/id/'.$val['id'].'/uid/'.$this->uid;
        }
        return returnjson(1000,$list,'获取成功');
    }

    /*
    * 审核消息
    * @param int $type
    */
    public function getSystemMsg($page = 1) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $message_model = new \app\index\model\Message();
        $list = $message_model->field('title,abstract,content,id,addtime')->where(['type'=>3,'uid'=>$this->uid])->page($page,$this->num)->select();
        foreach ($list as $k=>$val) {
            $list[$k]['url'] = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/message/getMsgDetail/id/'.$val['id'].'/uid/'.$this->uid;
        }
        return returnjson(1000,$list,'获取成功');
    }

    /*
     * 获取消息未读数量
     */
    public function getMsgCount() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }

        $message_model = new \app\index\model\Message();
        $messageReadLog = new MessageReadLog();
        $sysMsgCount = $message_model->getSysMsg($this->uid);
        $invMsgCount = $message_model->getInvMsg($this->uid);
        $systemMsgCount = $message_model->getSystemMsg($this->uid);
        $data = ['sysMsgCount'=>$sysMsgCount,'invMsgCount'=>$invMsgCount,'systemMsgCount'=>$systemMsgCount];
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 获取邀请信息
     */
    public function invMsg($page = 1) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $message_model = new \app\index\model\Message();
        $list = $message_model->field('title,content,id,addtime')->where(['type'=>2,'uid'=>$this->uid])->page($page,$this->num)->select();
        foreach ($list as $k=>$val) {
            $list[$k]['addtime'] = date('Y-m-d H:i:s',$val['addtime']);
        }
        return returnjson(1000,$list,'获取成功');
    }
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
    /*
     * 查看消息
     */
    public function getMsgDetail($id = 0,$uid = 0) {
        /*$message_model = new \app\index\model\Message();
        $messageReadLog = new MessageReadLog();
        $messageInfo = $message_model->field('type,title,abstract,content,send_time')->where('id',$id)->find();
        $messageInfo['send_time'] = date('Y-m-d',$messageInfo['send_time']);
        if(!$messageReadLog->where(['msg_id'=>$id,'uid'=>$uid])->find()) {
            $data = ['type'=>$messageInfo['type'],'uid'=>$uid,'msg_id'=>$id,'addtime'=>time()];
            $messageReadLog->insert($data);
        }
        return returnjson(1000,$messageInfo,'获取成功');*/
        
        if(file_exists('html/message/getmsgdetail'.$id.'.html')){
           include 'html/message/getmsgdetail'.$id.'.html';exit;
           }
           $message_model = new \app\index\model\Message();
           
          $messageReadLog = new MessageReadLog();
          
           $data = $message_model->where('id',$id)->find();
           $data['send_time'] = date('Y-m-d',$data['send_time']);
        if(!$messageReadLog->where(['msg_id'=>$id,'uid'=>$uid])->find()) {
            $data1 = ['type'=>$data['type'],'uid'=>$uid,'msg_id'=>$id,'addtime'=>time()];
            $messageReadLog->insert($data1);
        }
        
           $this->assign('data',$data);
           $this->buildHtml('getmsgdetail'.$id, 'html/message/', 'getmsgdetail');
           return $this->fetch();
        }

    /*
     * 查看消息
     */
    public function msgDetail($id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $message_model = new \app\index\model\Message();
        $messageReadLog = new MessageReadLog();
        $messageInfo = $message_model->field('type,title,abstract,content,send_time')->where('id',$id)->find();
        $messageInfo['send_time'] = date('Y-m-d',$messageInfo['send_time']);
        if(!$messageReadLog->where(['msg_id'=>$id,'uid'=>$this->uid])->find()) {
            $data = ['type'=>$messageInfo['type'],'uid'=>$this->uid,'msg_id'=>$id,'addtime'=>time()];
            $messageReadLog->insert($data);
        }
        return returnjson(1000,$messageInfo,'获取成功');
    }

}

