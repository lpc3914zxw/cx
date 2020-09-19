<?php

namespace app\wxapp\model;

use app\index\model\Knowledge;
use think\Model;
use think\Db;
class KnowledgeArticleBehav extends Model {
    protected $table = 'knowledge_articel_behavior';

    /*
   * 收藏列表
   * @param $where
   * @param $limit
   */
    public function getList($where,$limit) {
        $data = $this::field('article_id,addtime')->where($where)->limit($limit)->select();
        $knowledge_model = new Knowledge();
        $tutor_model = new Tutor();
        if(empty($data)) {
            return returnjson(1000,[],'获取成功');
        }
        foreach ($data as $k=>$val) {
            $knowledgeInfo = $knowledge_model->field('title,uid')->where('id',$val['article_id'])->find();
            $data[$k]['name'] = $knowledgeInfo['title'];
            $data[$k]['addtime'] = tranTime($val['addtime']);
          	$data[$k]['txt'] = Db::name('tutor')->where('uid',$knowledgeInfo['uid'])->value('name');
            $tutorImg = $tutor_model->where('uid',$knowledgeInfo['uid'])->value('imgurl');
            $data[$k]['teacherImg'] = $tutorImg;
        }
        return returnjson(1000,$data,'获取成功');
    }
}