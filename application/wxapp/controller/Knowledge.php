<?php
namespace app\wxapp\controller;
use app\index\model\AdverChild;
use app\index\model\Tutor;
use app\index\model\KnowledgeAdver;
use app\index\model\KnowledgeCate;
use app\wxapp\controller\Base;
use app\wxapp\model\Colliers;
use app\wxapp\model\CreditSource;
use app\wxapp\model\DedicationLog;
use app\wxapp\model\KnowledgeArticleBehav;
use app\wxapp\model\TutorScoreSetting;
use think\Db;
use think\helper\Time;
use app\common\Common;

class Knowledge extends Base{

    /*
     * 涨知识分类 /  轮播
     */
    public function knowledgeCateAdver() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $cate = new KnowledgeCate();
        $adver = new KnowledgeAdver();
        $adverChild = new AdverChild();
        $data = $cate->order('sort')->select();
        $adver = $adver->where(['kind'=>1,'is_open'=>1])->order('sort desc,addtime desc')->find();
        $adverlist = $adverChild->field('link,imgurl,idvalue,type')->where('adv_id',$adver['id'])->select();
        foreach ($adverlist as $k=>$val) {
            $adverlist[$k]['link'] = $val['link']."?id=".$val['idvalue'];
        }
        $data = [
            'adverCate'=>$data,
            'advlist'=>$adverlist
        ];
        return returnjson(1000,$data,'获取成功');
    }


    /*
     * 获取
     * @param int $cate_id
     * @param int $page
     */
    public function article($cate_id = 0,$page = 1) {
        
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $knowledge = new \app\index\model\Knowledge();
        $where = ['cat_id'=>$cate_id,'is_delete'=>0,'is_check'=>1];
        // 广告的位置插入  每隔3篇文章插入一条
        $adv_step = $this->systeminfo['adv_step'];
        
        $data = $knowledge->getApiList($where,$page,$this->num,$adv_step);
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 阅读文章
     * @param int $id
     */
    public function articleDetail($id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $knowledge = new \app\index\model\Knowledge();
        if(!$knowledge->where(['id'=>$id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','文章已被删除');
        }
        return $knowledge->getApiDetail($id,$this->uid);
    }

    /*
     * 阅读文章
     */
    public function readArticle() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $id = input('id');
        // 获取贡献值
        $common_model = new Common();
        Db::startTrans();
        $res = $common_model->dedicationLog($this->uid,2,$id,'阅读文章');
        $msg = '已阅读';
        if(false !== $res) {
            if($res == 0) {
                $msg = "今日阅读奖励已达上限";
            }else{
                $msg = '阅读文章获得'.$res.'贡献';
            }
        }
        Db::commit();
        return returnjson('1000','',$msg);
    }

    /*
     * 文章点赞、收藏
     * @param int $id 文章id
     * @param int $uid  用户id
     * @param int $type  1 点赞 2 收藏
     * @param int $content 评论内容
     */
    public function doBehavior($id = 0,$type = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $knowledge = new \app\index\model\Knowledge();
        $tutor_model = new \app\wxapp\model\Tutor();
        if(!$knowledge->where(['id'=>$id,'is_delete'=>0])->find()) {
            return returnjson(1001,'','文章已被删除');
        }
        $tutor_id = $knowledge->where('id',$id)->value('uid');
        $knowledgeBehav = new KnowledgeArticleBehav();
        if($type == 1 || $type == 2) {
            $msg = '';
            $da = '';
            if($type == 1) {
                $typeName = '点赞';
                $da = 1;
            }else {
                $typeName = '收藏';
            }
            if($knowledgeBehav->where(['article_id'=>$id,'type'=>$type,'uid'=>$this->uid])->find()) {
                if($knowledgeBehav->where(['article_id'=>$id,'type'=>$type,'uid'=>$this->uid])->delete()) {
                    $msg = '已取消'.$typeName;
                }
                if($type == 1) {
                    $tutor_model->where('uid',$tutor_id)->setDec('like_num');
                    $knowledge->where('id',$id)->setDec('like_num');
                    $da = 2;
                }else{
                    $knowledge->where('id',$id)->setDec('collection_num');
                    $da = 4;
                }
            }else{
                $data = [
                    'article_id'=>$id,
                    'uid'=>$this->uid,
                    'type'=>$type,
                    'addtime'=>time()
                ];
                if($knowledgeBehav->insert($data)) {
                    $msg = '已'.$typeName;
                }else{
                    return returnjson('1001','',$typeName.'失败');
                }
                $common_model = new Common();
                if($type == 1) {
                    $tutor_model->where('uid',$tutor_id)->setInc('like_num');
                    $knowledge->where('id',$id)->setInc('like_num');
                    // 获取贡献值
                    $res = $common_model->dedicationLog($this->uid,3,$id,'文章点赞');
                    if(false !== $res) {
                        if($res == 0) {
                            $msg = '点赞奖励已达上限';
                            $da = 1;
                        }else{
                            $msg = '点赞文章获得'.$res.'贡献';
                            $da = 1;
                        }
                        return returnjson('1000',$da,$msg);
                    }
                    return returnjson('1000',$da,$msg);
                }else{
                    $da = 3;
                    $knowledge->where('id',$id)->setInc('collection_num');
                    // 获取贡献值
                    $res = $common_model->dedicationLog($this->uid,8,$id,'收藏文章');
                    if(false !== $res) {
                        if($res == 0) {
                            $msg = '收藏奖励已达上限';
                        }else{
                            $msg = '收藏文章获得'.$res.'贡献';
                        }
                        return returnjson('1000',$da,$msg);
                    }
                }
            }
            return returnjson('1000',$da,$msg);
        }else{
            return returnjson('1001','','type类型错误');
        }
    }

    /*
     * 赞赏学分设置
     * @param int $tutor_id
     * @return \type
     */
    public function rewardScoreList($tutor_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $setting = new TutorScoreSetting();
        $where = ['tutor_id'=>$tutor_id];
        return $setting->getList($where);
    }

    /*
     * 赞赏作者学分
     */
    public function rewardScore() {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $data = $this->request->param();
        
        $scoreValue = $data['score'];
        $user_model = new \app\wxapp\model\User();
        $creditSource = new CreditSource();
        $score = $user_model->where('id',$this->uid)->value('score');
        if($score < $scoreValue) {
            return returnjson('1001','','学分不够');
        }
        $giveData = [
             'type'=>2, 'uid'=>$data['tutor_id'],
             'give_uid'=>$this->uid, 'score'=>$scoreValue,
             'status'=>1, 'note'=>'文章赞赏', 'addtime'=>time()
        ];
        Db::startTrans();
        if(!$creditSource->insert($giveData)) {
            return returnjson('1001','','赞赏失败');
        }
        $sendData = [
            'type'=>2, 'uid'=>$this->uid,
            'give_uid'=>$data['tutor_id'], 'score'=>"-".$scoreValue,
            'status'=>1, 'note'=>'文章赞赏', 'addtime'=>time()
        ];
        if(!$creditSource->insert($sendData)) {
            Db::rollback();
            return returnjson('1001','','赞赏失败');
        }
        if(false === $user_model->where('id',$this->uid)->setDec('score',$scoreValue)){
            Db::rollback();
            return returnjson('1001','','赞赏失败');
        }
        if(false === $user_model->where('id',$data['tutor_id'])->setInc('score',$scoreValue)){
            Db::rollback();
            return returnjson('1001','','赞赏失败');
        }
        Db::commit();
        return returnjson('1000','','赞赏成功');
    }

    /*
     * @param int $uid
     * @param int $article_id
     */
    public function shareArticle($article_id = 0) {
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $common = new Common();
        $common->dedicationLog($this->uid,4,$article_id,'分享文章');
        return returnjson('1000','','分享成功');
    }


     //h5文章详情页面
    public function h5_articel($id){
        
        $input = input('get.');
        $knowledge = new \app\index\model\Knowledge();
       
        $ainfo = $knowledge->where(['id'=>$id])->find();
        $url =  'https://'.$_SERVER['HTTP_HOST'].'/wxapp/Login/h5_register?p_id='.$input['uid'];
        $ainfo['addtime'] = date('Y-m',$ainfo['addtime']);
        $tutor_model = new Tutor();
        $tutor = $tutor_model->where(['uid'=>$ainfo['uid']])->find();
        $ainfo['imgurl'] = $tutor['imgurl'];
        $ainfo['name'] = $tutor['name'];
      //var_dump($ainfo['uid']);exit;
        $this->assign('url',$url);
        $this->assign('ainfo',$ainfo);
        
        return $this->fetch();
    }

    //h5文章页面
    public function articel($id){
        $input = input('get.');
        $knowledge = new \app\index\model\Knowledge();
       
        $ainfo = $knowledge->where(['id'=>$id])->find();
        $url =  'https://'.$_SERVER['HTTP_HOST'].'/wxapp/Login/h5_register?p_id='.$input['muid'];
        $ainfo['addtime'] = date('Y-m',$ainfo['addtime']);
        $tutor_model = new Tutor();
        $tutor = $tutor_model->where(['id'=>$ainfo['uid']])->find();
      	
        $ainfo['imgurl'] = $tutor['imgurl'];
        $ainfo['name'] = $tutor['name'];
        $this->assign('url',$url);
        $this->assign('ainfo',$ainfo);
        return $this->fetch();
    }

}

