<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-04
 * Time: 11:44
 */

namespace app\service;


use think\Db;

class SalelogService
{
    /**
     * 添加用户佣金记录
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-21
     * @desc    description
     * @param    [int]              $user_id        [用户id]
     * @param    [string]           $title          [标题]
     * @param    [string]           $detail         [内容]
     * @param    [int]              $business_type  [业务类型（0默认, 1订单, 2充值, 3提现, ...）]
     * @param    [int]              $business_id    [业务id]
     * @param    [int]              $type           [类型（默认0  普通消息）]
     * @return   [boolean]                          [成功true, 失败false]
     */
    public static function SalelogAdd($user_id, $title, $detail, $business_type = 0, $business_id = 0, $type = 0)
    {
        $data = array(
            'title'             => $title,
            'detail'            => $detail,
            'user_id'           => intval($user_id),
            'business_type'     => intval($business_type),
            'business_id'       => intval($business_id),
            'type'              => intval($type),
            'is_read'           => 0,
            'add_time'          => time(),
        );
        return Db::name('soaleLog')->insertGetId($data) > 0;
    }
    /**
     * 分销记录列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function SaleLogList($map = [])
    {
        $total = Db::table('sale_log')->where($map)->count(1);
        $list = Db::table('sale_log')->order('id','desc')->select(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return self::SaleLogListWith($total,$list);
    }
    private static function SaleLogListWith($total,$data)
    {
        if(!empty($data) && is_array($data))
        {
            foreach($data as &$v)
            {
                if($v['order_type']==0){
                    
                    $v['advanced_name'] = Db::name('advanced')->where('id','=',$v['advanced_id'])->value('name');
                    $v['course_name'] = Db::name('course')->where('id','=',$v['course_id'])->value('name');
                    $v['order_code'] = Db::name('order')->where('id','=',$v['order_id'])->value('order_id');
                }else{
                    $v['advanced_name'] = '会员卡';
                    $v['course_name'] = '会员卡';
                    $v['order_code'] = Db::name('cards_order')->where('id','=',$v['order_id'])->value('orderid');
                }
                 $v['order_user_name'] = Db::name('user')->where('id','=',$v['order_uid'])->value('name');
                 $v['sale_user_name'] = Db::name('user')->where('id','=',$v['user_id'])->value('name');
                    if(isset($v['type']))
                    {
                        if($v['type']==1){
                            $v['type'] = '直接推荐';
                        }elseif($v['type']==2){
                            $v['type'] = '间接推荐';
                        }elseif($v['type']==3){
                            $v['type'] = '平级奖';
                        }elseif($v['type']==4){
                            $v['type'] = '极差奖';
                        }
                    }
            }
        }
        return page_data($total, $data);
    }
}
