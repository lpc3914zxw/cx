<?php
namespace app\index\model;
use think\Model;

/**
 * 荣誉值
 * Class HonorLog
 * @package app\index\model
 */
class HonorSet extends Model {
    protected $table = 'honor_set';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        foreach ($list as $k=>$val) {
            $list[$k]['type'] = self::getTypeName($val['type']);
        }
        return page_data($total, $list);
    }

    public static function getTypeName($type = 0)
    {
        $arr =  [
            '1' => '购买《财学堂》课程每支付1元', '2' => '买入1个学分', '3' => '直推实名好友',
            '4' => '分享每日金句', '5' => '转发文章','6'=>'兑换课程每支付1学分'
        ];
        return $arr[$type];

    }
}