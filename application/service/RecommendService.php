<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-12
 * Time: 14:35
 */

namespace app\service;
use think\Db;
/***
 * Class RecommendService
 * @package app\service
 * 分销  直接推荐（一级分销） 间接推荐（二级分销）
 *
 */

class RecommendService
{
    /**
     * 保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function recommendList($map=[])
    {
        $total = Db::name('recommend')->where($map)->count(1);
        $list =  Db::name('recommend')->where($map)->limit(page())->select();
        return page_data($total, $list);
    }
    public static function getList($map = []) {
        $total = Db::table('recommend')->where($map)->count(1);
        $list = Db::table('recommend')->select(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return self::recommendListWith($total,$list);
    }
    private static function recommendListWith($total,$data)
    {
        if(!empty($data) && is_array($data))
        {
            foreach($data as &$v)
            {
                if(is_array($v))
                {
                    if(isset($v['type']))
                    {
                        if($v['type']==1){
                            $v['type'] = '直接推荐';
                        }elseif($v['type']==2){
                            $v['type'] = '间接推荐';
                        }else{
                            $v['type'] = '未知';
                        }
                    }
                    if(isset($v['uid']))
                    {
                        $v['username']=Db::name('user')->where('id',$v['uid'])->value('name');
                    }
                    if(isset($v['pid']))
                    {
                        $v['pname']=Db::name('user')->where('id',$v['uid'])->value('name');
                    }
                    if(isset($v['pay_type']))
                    {
                        if($v['pay_type']==5){
                            $v['pay_type'] = '微信';
                        }elseif($v['pay_type']==6){
                            $v['pay_type'] = '支付宝';
                        }else{
                            $v['pay_type'] = '其他';
                        }
                    }
                }
            }
        }
        return page_data($total, $data);
    }
}
