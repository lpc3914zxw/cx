<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2019 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace app\service;

use think\Db;
use app\service\UserService;
use app\service\ResourcesService;
use app\service\WalletService;
use app\service\CashService;
use think\helper\Time;


/**
 * 基础服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class BaseService
{
    /**
     * 钱包列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function WalletList($map = [])
    {
        $total = Db::table('wallet')->where($map)->count(1);
        $list = Db::table('wallet')->select(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return self::WalletListWith($total,$list);
    }
    private static function WalletListWith($total,$data)
    {
        $wallet_status_list = WalletService::$wallet_status_list;
        if(!empty($data) && is_array($data))
        {
            foreach($data as &$v)
            {

                if(is_array($v))
                {
                    if(!empty($v['user_id']))
                    {
                        $v['name'] = Db::name('user')->where('id',$v['user_id'])->value('name');
                        $v['headimg'] = Db::name('user')->where('id',$v['user_id'])->value('headimg');
                        $v['tel'] = Db::name('user')->where('id',$v['user_id'])->value('tel');

                    }

                    // 状态
                    $v['status_name'] = (isset($v['status']) && isset($wallet_status_list[$v['status']])) ? $wallet_status_list[$v['status']]['name'] : '未知';

                    // 时间
                    $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                    $v['upd_time_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
                }
            }
        }
        return page_data($total, $data);
    }
    /**
     * 钱包明细列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function WalletlogList($map = [])
    {
        $total = Db::table('wallet_log')->where($map)->count(1);
        $list = Db::table('wallet_log')->order('id','desc')->select(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return self::WalletlogListWith($total,$list);
    }
    private static function WalletlogListWith($total,$data)
    {
        $wallet_status_list = WalletService::$wallet_status_list;
        if(!empty($data) && is_array($data))
        {
            foreach($data as &$v)
            {

                if(is_array($v))
                {
                    if(!empty($v['user_id']))
                    {
                        $v['name'] = Db::name('user')->where('id',$v['user_id'])->value('name');
                        $v['headimg'] = Db::name('user')->where('id',$v['user_id'])->value('headimg');
                        $v['tel'] = Db::name('user')->where('id',$v['user_id'])->value('tel');
                    }
                    $business_type_list = WalletService::$business_type_list;
                    $operation_type_list = WalletService::$operation_type_list;
                    $money_type_list = WalletService::$money_type_list;
                    if($v['type']==0){
                        $v['type_name']='个人余额';
                    }elseif ($v['type']==1){
                        $v['type_name']='团队余额';
                    }else{
                        $v['type_name']='未知';
                    }
                    // 状态
                    // 业务类型
                    $v['business_type_name'] = (isset($v['business_type']) && isset($business_type_list[$v['business_type']])) ? $business_type_list[$v['business_type']]['name'] : '未知';

                    // 操作类型
                    $v['operation_type_name'] = (isset($v['operation_type']) && isset($operation_type_list[$v['operation_type']])) ? $operation_type_list[$v['operation_type']]['name'] : '未知';

                    // 金额类型
                    $v['money_type_name'] = (isset($v['money_type']) && isset($money_type_list[$v['money_type']])) ? $money_type_list[$v['money_type']]['name'] : '未知';

                    // 时间
                    $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                    $v['upd_time_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
                }
            }
        }
        return page_data($total, $data);
    }
    public static function CashOne($id = 0)
    {
        return  Db::table('wallet_cash')->where('id','=',$id)->find();
        //return self::CashListWith($total,$list);
    }


    /**
     * 钱包明细列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function CashList($map = [])
    {
        $total = Db::table('wallet_cash')->where($map)->count(1);
        $list = Db::table('wallet_cash')->order('id','desc')->select(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return self::CashListWith($total,$list);
    }
    private static function CashListWith($total,$data)
    {
        $wallet_status_list = WalletService::$wallet_status_list;
        if(!empty($data) && is_array($data))
        {
            foreach($data as &$v)
            {

                if(is_array($v))
                {
                    // 提现状态
                    $v['status_name'] = isset($v['status']) ? CashService::$cash_status_list[$v['status']]['name'] : '';
                    if(!empty($v['user_id']))
                    {
                        $v['user'] =  Db::name('User')->field('name,tel')->find($v['user_id']);
                        if(empty($v['user'])){
                                $v['user']='未知';
                        }else{
                            $v['user']=array_values($v['user']);

                            $v['user']=implode('<br/>', $v['user']);
                        }
                        $v['headimg'] = Db::name('user')->where('id',$v['user_id'])->value('headimg');


                    }
                        if($v['type']==0){
                            $v['type_name']='个人余额';
                        }elseif ($v['type']==1){
                            $v['type_name']='团队余额';
                        }else{
                            $v['type_name']='未知';
                        }

                    // 备注
                    $v['msg'] = empty($v['msg']) ? '' : str_replace("\n", '<br />', $v['msg']);

                    // 时间
                    $v['pay_time_time'] = empty($v['pay_time']) ? '' : date('Y-m-d H:i:s', $v['pay_time']);
                    $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                    $v['upd_time_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);

                    $v['cash_msg']="单号：".$v['cash_no']."<br/>金额：".$v['money'].'元';//提现信息
                    $v['cash_inner']=$v['bank_name'].'<br/>'.$v['bank_username'].'<br/>'.$v['bank_accounts'];//收款信息
                    $v['cash_out']=$v['status_name'].'<br/>'.$v['pay_time_time']."<br/>金额：".$v['pay_money'].'元';//提现信息



                }
            }
        }
        return page_data($total, $data);
    }


    /**
     * 基础配置信息
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-12-24
     * @desc    description
     * @param   [boolean]          $is_cache [是否缓存中读取]
     */
    public static function BaseConfig($is_cache = true)
    {
        $ret = PluginsService::PluginsData('wallet', '', $is_cache);
        if(!empty($ret['data']))
        {
            // 会员中心公告
            if(!empty($ret['data']['user_center_notice']))
            {
                $ret['data']['user_center_notice'] = explode("\n", $ret['data']['user_center_notice']);
            }

        }
        return $ret;
    }
    /**
     * 钱包明细列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function WalletLogListApi($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        //var_dump($where);exit;
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::name('walletLog')->field($field)->where($where)->limit($m, $n)->order($order_by)->select();
        //echo Db::name('walletLog')->getLastSql();exit;
            foreach($data as &$v)
            {
                $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y.m.d', $v['add_time']);
            }
        return DataReturn('处理成功', 0, $data);
    }
    /**
     * 钱包明细列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function WalletcashListApi($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        //var_dump($where);exit;
        $m = isset($params['m']) ? intval($params['m']) : 0;
        $n = isset($params['n']) ? intval($params['n']) : 10;
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::name('wallet_cash')->field($field)->where($where)->limit($m, $n)->order($order_by)->select();
        //echo Db::name('wallet_cash')->getLastSql();exit;
        foreach($data as &$v)
        {
            $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y.m.d', $v['add_time']);
        }
        return DataReturn('处理成功', 0, $data);
    }
    /**
     * 钱包明细总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function WalletLogTotalApi($where = [])
    {
        return (int) Db::name('walletLog')->where($where)->count();
    }
    /**
     * 钱包明细总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function WalletcashTotalApi($where = [])
    {
        return (int) Db::name('wallet_cash')->where($where)->count();
    }
    /**
     * 钱包明细总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function WalletLogTotalIncome($where = [])
    {
        return  Db::name('walletLog')->where($where)->where(['operation_type'=>1,'status'=>0])->sum('operation_money');
    }
    /**
     * 累计提现成功
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function WalletCashsum($user_id)
    {
        return  Db::name('wallet_cash')->where('user_id','=',$user_id)->where(['status'=>1])->sum('money');
    }
    /**
     * 今日提现成功
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function WalletCashsumtoday($user_id)
    {
        list($start,$end)=Time::today();
        return  Db::name('wallet_cash')->where('user_id','=',$user_id)->where('add_time','>',$start)->where('add_time','>',$end)->where(['status'=>1])->sum('money');
    }
    /**
     * 钱包明细总数
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $where [条件]
     */
    public static function WalletLogTotalExpend($where = [])
    {
        return  Db::name('walletLog')->where($where)->where(['operation_type'=>0,'status'=>0])->sum('operation_money');
    }

    /**
     * 钱包明细条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function WalletLogWhereApi($params = [],$user_id=0)
    {
        $where ['money_type']= ['in','0,1,2'];

        // id
        if(!empty($params['id']))
        {
            $where['id'] = [ '=', intval($params['id'])];
        }

        // 用户id
        if($user_id!==0)
        {
            $where['user_id'] = ['=', $user_id];
        }

        // 用户
        if(!empty($params['keywords']))
        {
            $user_ids = Db::name('User')->where('username|nickname|mobile|email', '=', $params['keywords'])->column('id');
            if(!empty($user_ids))
            {
                $where['user_id'] = ['user_id', 'in', $user_ids];
            } else {
                // 无数据条件，避免用户搜索条件没有数据造成的错觉
                $where['id'] = [ '=', 0];
            }
        }
        // 业务类型
        if(isset($params['type']) && $params['type'] > -1)
        {
            $where['type'] = [ '=', $params['type']];
        }
        // 业务类型
       /* if(isset($params['business_type']) && $params['business_type'] > -1)
        {   if($params['type']==1){

            }else{
                $where['business_type'] = [ '=', $params['business_type']];
            }

        }*/

        // 操作类型
        if(isset($params['operation_type']) && $params['operation_type'] > -1)
        {
            $where['operation_type'] = [ '=', $params['operation_type']];
        }

        // 金额类型
        if(isset($params['money_type']) && $params['money_type'] > -1)
        {
            $where['money_type'] = ['=', $params['money_type']];
        }

        // 金额类型
        if(isset($params['start_time']) && $params['start_time'] > -1)
        {
            $where['add_time'] = ['between', [$params['start_time'],$params['end_time']]];
        }
        //var_dump($where);exit;
        return $where;
    }
    /**
     * 钱包明细条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function WalletcashWhereApi($params = [],$user_id=0)
    {
        $where = [];

        // id
        if(!empty($params['id']))
        {
            $where['id'] = [ '=', intval($params['id'])];
        }

        // 用户id
        if($user_id!==0)
        {
            $where['user_id'] = ['=', $user_id];
        }

        // 用户
        if(!empty($params['keywords']))
        {
            $user_ids = Db::name('User')->where('username|nickname|mobile|email', '=', $params['keywords'])->column('id');
            if(!empty($user_ids))
            {
                $where['user_id'] = ['user_id', 'in', $user_ids];
            } else {
                // 无数据条件，避免用户搜索条件没有数据造成的错觉
                $where['id'] = [ '=', 0];
            }
        }
        // 业务类型
        if(isset($params['type']) && $params['type'] > -1)
        {
            $where['type'] = [ '=', $params['type']];
        }
        // 业务类型
        if(isset($params['business_type']) && $params['business_type'] > -1)
        {
            $where['business_type'] = [ '=', $params['business_type']];
        }

        // 操作类型
        if(isset($params['operation_type']) && $params['operation_type'] > -1)
        {
            $where['operation_type'] = [ '=', $params['operation_type']];
        }

        // 金额类型
        if(isset($params['money_type']) && $params['money_type'] > -1)
        {
            $where['money_type'] = ['=', $params['money_type']];
        }

        // 金额类型
        if(isset($params['start_time']) && $params['start_time'] > -1)
        {
            $where['add_time'] = ['between', [$params['start_time'],$params['end_time']]];
        }
        //var_dump($where);exit;
        return $where;
    }
    /**
     * 提现列表条件
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-29
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function CashWhere($params = [])
    {
        $where = [];

        // 用户id
        if(!empty($params['user']))
        {
            $where['user_id'] = [ '=', $params['user']['id']];
        }

        // id
        if(!empty($params['id']))
        {
            $where['id'] = [ '=', intval($params['id'])];
        }
        // 订单号
        if(!empty($params['orderno']))
        {
            $where['cash_no'] = [ '=', trim($params['orderno'])];
        }

        // 关键字根据用户筛选
        if(!empty($params['keywords']))
        {
            if(empty($params['user']))
            {
                $user_ids = Db::name('User')->where('name|tel', '=', $params['keywords'])->column('id');
                if(!empty($user_ids))
                {
                    $where['user_id'] = [ 'in', $user_ids];
                } else {
                    // 无数据条件，走单号条件
                    $where['cash_no'] = [ '=', $params['keywords']];
                }
            }
        }

        // 状态
        if(isset($params['status']) && $params['status'] > -1)
        {
            $where['status'] = [ '=', $params['status']];
        }

        return $where;
    }
}
?>
