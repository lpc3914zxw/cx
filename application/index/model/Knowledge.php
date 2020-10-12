<?php
// +----------------------------------------------------------------------
// | 功能：
// +----------------------------------------------------------------------
// | 作者: 
// +----------------------------------------------------------------------
// | 日期：
// +----------------------------------------------------------------------
namespace app\index\model;
use app\wxapp\model\KnowledgeArticleBehav;
use app\wxapp\model\TutorFollow;
use app\wxapp\model\Tutor;
use think\Model;
/**
 * 涨知识-文章
 * Class Advanced
 * @package app\index\model
 */
class Knowledge extends Model{
    protected $table = 'knowledge_articel';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->order('addtime desc')->limit(page());
        });
        return page_data($total, $list);
    }

    /*
     * 文章列表
     * @param array $where
     * @param string $limit
     */
    public function getApiList($where,$page,$num,$adv_step) {
        $start = ($page - 1) * $num;
        $allCount = $this::where($where)->count();
        $res = $this::field('id,title,readnum,imgurl,check_time,uid')->where($where)->order('check_time desc')->limit($start,$num)->select();
        $adv = new KnowledgeAdver();
        $advChild = new AdverChild();
        $tutor = new Tutor();
        foreach ($res as $k=>$val) {
            $res[$k]['tutorImg'] = $tutor->where('uid',$val['uid'])->value('imgurl');
            $res[$k]['addtime'] = tranTime($val['check_time']);
            $advCount = floor(($start + $k + 1) / $adv_step);
            
            if((($page - 1) * $num + $k + 1) % $adv_step == 0) {
                if($adv->where('kind',2)->limit($advCount,1)->find()){
                    $advInfo = $adv->field('id')->where('kind',2)->limit($advCount,1)->find();
                    $advlist = $advChild->where('adv_id',$advInfo['id'])->select();
                    $adv_num = ((($page - 1) * $num + $k + 1))/$adv_step;
                    $res[$k]['labelCount'] = intval($adv_num);
                    $res[$k]['advlist'] = $advlist;
                }
            }else{
                $res[$k]['labelCount'] = 0;
                $res[$k]['advlist'] = [];
            }
        }
        return $res;
    }

    /*
     * 文章详情
     * @param int $id
     */
    public function getApiDetail($id = 0,$uid = 0) {
        $data = $this::where('id',$id)->find();
        if(empty($data)) {
            return returnjson(1001,'','该文章被删除');
        }
        $data['addtime'] = date('Y-m-d',$data['addtime']);
        $tutor = new Tutor();
        $knowledgeBehav = new KnowledgeArticleBehav();
        $user_model = new \app\wxapp\model\User();
        $tutorFollow = new TutorFollow();
        $tutorInfo = $tutor->field('imgurl,name')->where('uid',$data['uid'])->find();
        $data['tutorImg'] = $tutorInfo['imgurl'];
        $data['tutorName'] = $tutorInfo['name'];
        if($knowledgeBehav->where(['article_id'=>$id,'uid'=>$uid,'type'=>1])->find()){
            $data['is_like'] = 1;
        }else{
            $data['is_like'] = 0;
        }
        if($knowledgeBehav->where(['article_id'=>$id,'uid'=>$uid,'type'=>2])->find()){
            $data['is_collection'] = 1;
        }else{
            $data['is_collection'] = 0;
        }
        if($knowledgeBehav->where(['article_id'=>$id,'uid'=>$uid,'type'=>3])->find()){
            $data['is_comment'] = 1;
        }else{
            $data['is_comment'] = 0;
        }
        if($tutorFollow->where(['tutor_id'=>$data['uid'],'uid'=>$uid])->find()){
            $data['is_follow'] = 1;
        }else{
            $data['is_follow'] = 0;
        }
        $this::where('id',$id)->setInc('readnum');
        $data['tutor_id'] = $data['uid'];
        $userlist = $knowledgeBehav->field('uid')->where(['article_id'=>$id,'type'=>4])->limit(4)->select();
        foreach ($userlist as $k=>$val) {
            $userlist[$k]['headimg'] = $user_model->where('id',$val['uid'])->value('headimg');
        }
        $data['likeUsers'] = $userlist;
        $data['shareLink'] = GetCurUrl() .'/wxapp/Knowledge/h5_articel?id=' .$id ."&uid=".$uid;
        return returnjson(1000,$data,'获取成功');
    }

    /*
     * 导师发表的文章列表
     * @param array $where
     */
    public function getApiArticleList($where = [],$limit = "") {
        $data = $this::field('id,title,uid,imgurl,addtime,readnum')->where($where)->limit($limit)->select();
        $tutor = new Tutor();
        foreach ($data as $k=>$val) {
            $data[$k]['tutorImg'] = $tutor->where('uid',$val['uid'])->value('imgurl');;
            $data[$k]['addtime'] = tranTime($val['addtime']);
        }
        return returnjson(1000,$data,'获取成功');
    }
}
