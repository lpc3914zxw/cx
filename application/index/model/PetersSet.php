<?php
namespace app\index\model;
use app\service\RegionService;
use think\App;
use think\Model;

/**
 * 荣誉值
 * Class HonorLog
 * @package app\index\model
 */
class PetersSet extends Model {
    protected $table = 'peters_set';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        foreach ($list as $k=>$val) {
            $list[$k]['type'] = self::getStatusName($val['type']);
        }

        return page_data($total, $list);
    }
    public static function getStatusName($status = 0)
    {
        $arr =  [
            1 => '堂主权益', 2 => '申请条件',3=>'等级'
        ];
        return $arr[$status];

    }
}
