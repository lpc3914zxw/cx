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
class PetersLog extends Model {
    protected $table = 'peters_log';
    public function getList($map = []) {
        $join = [
           ['user u','u.id=d.uid','left']
        ];
        $total = $this::alias('d')->join($join)->where($map)->count(1);
       // $total = $this::where($map)->count(1);
        
        $list = $this::all(function($query) use($map,$join) {
            $query->alias('d')->join($join)->where($map)->order('d.status','asc')->order('d.id','desc')->field('d.*,u.name,u.tel')->limit(page());
        });
        //foreach ($list as $k=>$val) {
            //$list[$k]['status'] = self::getStatusName($val['status']);
        //}
        foreach ($list as $k=>$val) {
            $list[$k]['province'] = self::getProvince($val['province']);
        }
        foreach ($list as $k=>$val) {
            $list[$k]['city'] = self::getCity($val['city']);
        }
        foreach ($list as $k=>$val) {
            $list[$k]['county'] = self::getCounty($val['county']);
        }
        //foreach ($list as $k=>$val) {
            //$list[$k]['name'] = self::getUserName($val['uid']);
        //}
        //var_dump($list);exit;
        return page_data($total, $list);
    }

    public static function getStatusName($status = 0)
    {
        $arr =  [
            0 => '待审核', 1 => '审核通过',2=>'拒绝'
        ];
        return $arr[$status];

    }
    public static function getProvince($province = 0)
    {

        $name=RegionService::RegionName($province);
        return $name;
    }
    public static function getCity($getCity = 0)
    {

        $name=RegionService::RegionName($getCity);
        return $name;
    }
    public static function getCounty($county = 0)
    {

        $name=RegionService::RegionName($county);
        return $name;
    }
    public static function getUserName($uid = 0)
    {

       $user_model=new \app\index\model\User();
       $name=$user_model->where('id','=',$uid)->value('name');
        return $name;
    }
}
