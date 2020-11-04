<?php
namespace app\index\model;
use think\Db;
use think\Model;

/**
 * 荣誉值
 * Class HonorLog
 * @package app\index\model
 */
class Logweb extends Model {
    protected $table = 'web_log_all';
    public function getList($map = []) {

        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        foreach ($list as $k=>$val) {
            $list[$k]['username'] = self::getUidName($val['uid']);
        }
        foreach ($list as $k=>$val) {
            $list[$k]['data'] =self::getunserialize($val['data']);
        }
        foreach ($list as $k=>$val) {
            $list[$k]['note'] =self::note($val['module'],$val['controller'],$val['action'],$val['method']);
        }

        return page_data($total, $list);
    }

    public static function getUidName($uid = 0)
    {
        return Member::getUsername($uid);

    }
    public static function getunserialize($data = '')
    {

        return stripslashes(json_encode(unserialize($data)));
        //return json_encode(unserialize($data)));
    }
    public static function note($module,$controller,$action,$method)
    {
        $res=Db::name('node_map')->where('module',$module)->where('controller',$controller)->where('action',$action)->where('method',$method)->value('comment');
        if($res){
            return $res;
        }else{
            return '未知';
        }
    }

}
