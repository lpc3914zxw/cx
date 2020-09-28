<?php

namespace app\wxapp\model;

use think\Model;

/**
 *贡献值记录中心
 * Class DedicationLog
 * @package app\wxapp\model
 */
class DedicationLog extends Model {
    protected $table = 'dedication_log';

    /*
    * 获取学习力明细
    * @param $where
    */
    public function getApiDedicationList($where = [],$limit = '') {
        $data = $this::where($where)->limit($limit)->order('addtime desc')->select();;
        foreach ($data as $k=>$val) {
            $data[$k]['type'] = static::getTypeName($val['type']);
            $data[$k]['addtime'] = date('Y/m/d H:i',$val['addtime']);
        }
        $reciveDedica = $this::where($where)->sum('value');
        $data = ['data'=>$data,'reciveDedica'=>$reciveDedica];
        return returnjson('1000',$data,'获取成功');
    }

    public static function getTypeName($type = 0)
    {
        $arr =  [
            '1' => '金句分享', '2' => '阅读文章', '3' => '点赞文章',
            '4' => '分享', '5' => '反馈意见', '6' => '邀请好友',
            '7' => '财学堂课堂学习', '8' => '文章收藏', '9' => '大社群新增一人',
            '10' => '小社群新增一人', '11' => '大社群新增一个学习力', '12' => '小社群新增一个学习力',
            '13' => '课程收藏', '14' => '课程阅读', '15' => '课程点赞',
            '16' => '课程分享','17' => '旗下会员增加学习力','20'=>'财学堂课程消费1元','21'=>'课程购买'
        ];
        return $arr[$type];

    }
}