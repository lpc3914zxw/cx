<?php
namespace app\index\model;
use think\Model;

/**
 * 荣誉值记录
 * Class HonorLog
 * @package app\index\model
 */
class HonorLog extends Model {
    protected $table = 'honor_log';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }

    public function get_list($map = [],$is_export = 0) {
        $join = [
           ['user u','u.id = h.uid','left']
        ];
        $total = $this::alias('h')->join($join)->where($map)->count(1);
        if($is_export == 0) {
            $list = $this::all(function($query) use($map,$join) {
                $query->alias('h')->join($join)->where($map)->field('h.*,u.name,u.tel')->order('addtime desc')->limit(page());
            });
        }else{
            $list = $this::alias('h')->field('h.*,u.name,u.tel')->join($join)->where($map)->order('addtime desc')->select();
        }
        return page_data($total, $list);
    }

    /*
    * 获取荣誉值明细
    * @param $where
    */
    public function getApiHornorList($where = [],$limit = '') {
        $data = $this::where($where)->limit($limit)->order('addtime desc')->select();;
        foreach ($data as $k=>$val) {
            $data[$k]['type'] = static::getTypeName($val['type']);
            $data[$k]['addtime'] = date('Y/m/d H:i',$val['addtime']);
        }
        $reciveDedica = $this::where($where)->where(['type'=>['in',("1,2,3,4,5,7,9")]])->sum('value');
        $payDedica = $this::where($where)->where(['type'=>10])->sum('value');
        $data = ['data'=>$data,'reciveDedica'=>$reciveDedica,'payDedica'=>$payDedica];
        return returnjson('1000',$data,'获取成功');
    }

    public static function getTypeName($type = 0)
    {
        $arr =  [
            '1' => '购买课程', '2' => '加成学习', '3' => '直推1名实名好友',
            '4' => '分享每日金句1次', '5' => '转发文章一次','6'=>'兑换TLT','7'=>'平台充值','8'=>'平台扣除','9'=>'赠送课程','10'=>'学分兑换tlt'
        ];
        return $arr[$type];

    }
}