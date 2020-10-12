<?php
namespace app\wxapp\controller;
use app\common\Common;
use app\wxapp\model\Collection;
use think\Config;
use app\wxapp\controller\Base;
use think\Db;
class Clause extends Base{

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
    //关于我们接口
    public function aboutus(){
        
        $about_model = new \app\wxapp\model\Clause();
        $about = $about_model->where('id',1)->field('tel,version,email,app_note')->find();
        $about['privacyprotocol'] = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/privacyprotocol.html';
        $about['TOS'] = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/TOS.html';
        return returnjson(1000,$about,'获取成功');
        
    }
    //关于我们
    public function about(){
        if(file_exists('html/clause/about.html')){
        
           include 'html/clause/about.html';exit;
        }
        $about_model = new \app\wxapp\model\Clause();
        $about = $about_model->where('id',1)->value('about');
        $this->assign('about',$about);
        $this->buildHtml('about', 'html/clause/', 'about');
        return $this->fetch();
        
    }
    //使用协议
    public function useprotocol(){
        if(file_exists('html/clause/useprotocol.html')){
        
           include 'html/clause/useprotocol.html';exit;
        }
        $about_model = new \app\wxapp\model\Clause();
        $about = $about_model->where('id',1)->value('useprotocol');
        $this->assign('about',$about);
        $this->buildHtml('useprotocol', 'html/clause/', 'useprotocol');
        return $this->fetch();
        
    }
    //隐私协议
    public function privacyprotocol(){
        if(file_exists('html/clause/privacyprotocol.html')){
        
           include 'html/clause/privacyprotocol.html';exit;
        }
        $about_model = new \app\wxapp\model\Clause();
        $about = $about_model->where('id',1)->value('privacyprotocol');
        $this->assign('about',$about);
        $this->buildHtml('privacyprotocol', 'html/clause/', 'privacyprotocol');
        return $this->fetch();
        
    }
    //才学专栏使用协议
    public function cxuseprotocol(){
        if(file_exists('html/clause/cxuseprotocol.html')){
        
           include 'html/clause/cxuseprotocol.html';exit;
        }
        $about_model = new \app\wxapp\model\Clause();
        $about = $about_model->where('id',1)->value('cxuseprotocol');
        $this->assign('about',$about);
        $this->buildHtml('cxuseprotocol', 'html/clause/', 'cxuseprotocol');
        return $this->fetch();
        
    }
    //才学专栏隐私协议
    public function cxprivacyprotocol(){
        if(file_exists('html/clause/cxprivacyprotocol.html')){
        
           include 'html/clause/cxprivacyprotocol.html';exit;
        }
        $about_model = new \app\wxapp\model\Clause();
        $about = $about_model->where('id',1)->value('cxprivacyprotocol');
        $this->assign('about',$about);
        $this->buildHtml('cxprivacyprotocol', 'html/clause/', 'cxprivacyprotocol');
        return $this->fetch();
        
    }
    //服务条款
    public function TOS(){
        if(file_exists('html/clause/tos.html')){
        
           include 'html/clause/tos.html';exit;
        }
        $about_model = new \app\wxapp\model\Clause();
        $about = $about_model->where('id',1)->value('TOS');
        $this->assign('about',$about);
        $this->buildHtml('tos', 'html/clause/', 'tos');
        return $this->fetch();
        
    }
    //财学堂代理条款1
    public function peters_contert(){
        if(file_exists('html/clause/peters_contert.html')){
        
           include 'html/clause/peters_contert.html';exit;
        }
        $about_model = new \app\wxapp\model\Clause();
        $about = $about_model->where('id',1)->value('peters_contert');
        $this->assign('peters_contert',$about);
        $this->buildHtml('peters_contert', 'html/clause/', 'peters_contert');
        return $this->fetch();
        
    }
    //获取全部协议
    public function get_protocol(){
        $data=array(
            'about' => 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/about.html',
            'useprotocol' => 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/useprotocol.html',
            'privacyprotocol' => 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/privacyprotocol.html',
            'cxuseprotocol' => 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/cxuseprotocol.html',
            'cxprivacyprotocol' => 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/cxprivacyprotocol.html',
            'peters_contert' => 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/peters_contert.html',
            'TOS' => 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/Clause/useprotocol.html'
            );
        return returnjson(1000,$data,'获取成功');
        
    }
    //才学酒馆通知
    public function cxMessginDetail($id){
        if(file_exists('html/clause/cxmessgindetail'.$id.'.html')){
        
           include 'html/clause/cxmessgindetail'.$id.'.html';exit;
        }
         $message_model = new \app\index\model\Message();
        $info = $message_model->field('title,abstract,id,addtime')->where(['id'=>$id,'type'=>1])->find();
        if($info){
            $info['addtime'] = date('m月d日',$info['addtime']);
        }
        $about_model = new \app\wxapp\model\Clause();
        
        $this->assign('data',$info);
        $this->buildHtml('cxmessgindetail'.$id, 'html/clause/', 'cxmessgindetail');
        return $this->fetch();
        
    }
    


   
}

