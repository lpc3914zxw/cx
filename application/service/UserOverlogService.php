<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-04
 * Time: 11:44
 */

namespace app\service;


use think\Db;

class UserOverlogService
{
    private $status;//0待审核 1审核通过 2拒绝
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
    public static function UserOverlogAdd($user_id,$params,$status=0)
    {
        $user = Db::name('user_overlog')->where('user_id','=',$user_id)->order('id desc')->find();
        //var_dump($user);exit;

        if ($user['status'] == 0 and isset($user)){
            return DataReturn('已提交等待审核', -100);
        }elseif ($user['status']==1){
            return DataReturn('已审核', -100);
        }
        $data = array(
            'user_id'             => $user_id,
            'real_name'            => $params['real_name'],
            'card_number'           => $params['card_number'],
            'status'              => $status,
            'add_time'          => time(),
        );
        // 相册
        $photo = self::GetFormGoodsPhotoParams($params);
        if($photo['code'] != 0)
        {
            return $photo;
        }
        Db::startTrans();
        // 添加/编辑
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            $user_overlog_id = Db::name('user_overlog')->insertGetId($data);
        } else {
            $user_overlog = Db::name('user_overlog')->find($params['id']);
            $data['upd_time'] = time();
            if(Db::name('user_overlog')->where(['id'=>intval($params['id'])])->update($data))
            {
                $user_overlog_id = $params['id'];
            }
        }
        if(isset($user_overlog_id) && $user_overlog_id > 0) {
            // 相册
            $ret = self::UserOverlogImgInsert($params['photo'], $user_overlog_id);
            if($ret['code'] != 0)
            {
                // 回滚事务
                Db::rollback();
                return $ret;
            }
            Db::commit();
            return DataReturn('成功提交', 0);
        }
        Db::rollback();
        return DataReturn('操作失败', -100);
    }
    /**
     * 获取商品相册
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-07-10
     * @desc    description
     * @param   [array]          $params [输入参数]
     * @return  [array]                  [一维数组但图片地址]
     */
    private static function GetFormGoodsPhotoParams($params = [])
    {
        if(empty($params['photo']))
        {
            return DataReturn('请上传相册', -1);
        }
        if(is_array($params['photo']) && count($params['photo'],COUNT_NORMAL)>1 )
        {
            return DataReturn('success', 0, '');
        } else {
            return DataReturn('请上传相册两张以上', -1);
        }

    }
    /**
     * 商品分类添加
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-07-10
     * @desc    description
     * @param   [array]          $data     [数据]
     * @param   [int]            $goods_id [商品id]
     * @return  [array]                    [boolean | msg]
     */
    private static function UserOverlogImgInsert($data, $user_overlog_id)
    {
        Db::name('user_overlog_img')->where(['user_overlog_id'=>$user_overlog_id])->delete();
        if(!empty($data))
        {
            foreach($data as $k=> $photo)
            {
                $temp_photo = [
                    'user_overlog_id'      => $user_overlog_id,
                    'url'   => $photo,
                    'add_time'      => time(),
                    'type'      => $k,
                ];
                if(Db::name('user_overlog_img')->insertGetId($temp_photo) <= 0)
                {
                    return DataReturn('图片添加失败', -1);
                }
            }
        }
        return DataReturn('添加成功', 0);
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
    public static function OverlogWhere($params = [])
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

        // 关键字根据用户筛选
        if(!empty($params['keywords']))
        {
            if(empty($params['user']))
            {
                $user_ids = Db::name('User')->where('name|email', '=', $params['keywords'])->column('id');
                //var_dump($user_ids);exit;
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
        if(isset($params['status']) && $params['status'] > -1 && $params['status']!=='')
        {
            $where['status'] = [ '=', $params['status']];
        }

        return $where;
    }

    /**
     * 钱包明细列表
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-04-30T00:13:14+0800
     * @param   [array]          $params [输入参数]
     */
    public static function OverlogList($map = [])
    {
        $total = Db::table('user_overlog')->where($map)->count(1);
        $list = Db::table('user_overlog')->order('id','desc')->select(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return self::OverlogListWith($total,$list);
    }
    private static function OverlogListWith($total,$data)
    {
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
                        $v['user'] =  Db::name('User')->field('name,email')->find($v['user_id']);
                        if(empty($v['user'])){
                            $v['user']='未知';
                        }else{
                            $v['user']=array_values($v['user']);

                            $v['user']=implode('<br/>', $v['user']);
                        }
                        $v['headimg'] = Db::name('user')->where('id',$v['user_id'])->value('headimg');


                    }
                    if($v['status']==0){
                        $v['status_name']='待审核';
                    }elseif ($v['status']==1){
                        $v['status_name']='审核通过';
                    }elseif($v['status']==2){
                        $v['status_name']='审核拒绝';
                    }else{
                        $v['status_name']='未知';
                    }
                    $data_url=Db::name('user_overlog_img')->where('user_overlog_id','=',$v['id'])->column('url');

                    $topicid = ' '; //变量赋值为空
                    if(is_array($data_url)){
                        foreach($data_url as $key=>$vals){

                            $topicid.=$vals.',';

                        }
                        $topicid = rtrim($topicid, ',');

                        $v['imgurls'] = $topicid;
                    }else{
                        $v['imgurls'] = '';
                    }


                    // 时间
                    //$v['pay_time_time'] = empty($v['pay_time']) ? '' : date('Y-m-d H:i:s', $v['pay_time']);
                    $v['add_time_time'] = empty($v['add_time']) ? '' : date('Y-m-d H:i:s', $v['add_time']);
                    //$v['upd_time_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);




                }
            }
        }
        return page_data($total, $data);
    }
    /**
     * 提现申请审核
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-10
     * @desc    description
     * @param    [array]          $params [输入参数]
     */
    public static function overlogAudit($params = [])
    {
        // 参数验证
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '申请id有误',
            ],
            [
                'checked_type'      => 'length',
                'key_name'          => 'note',
                'checked_data'      => '180',
                'error_msg'         => '备注最多 180 个字符',
            ],
            [
                'checked_type'      => 'in',
                'key_name'          => 'type',
                'checked_data'      => ['agree', 'refuse'],
                'error_msg'         => '操作类型有误，同意或拒绝操作出错',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }
        // 获取认证数据数据
        $user_data=Db::name('user_overlog')->where('id','=',$params['id'])->find();
        //校验
        if($user_data['status']!==0){
            return DataReturn('操作失败已审核或已拒绝', -101);
        }
        // 开始处理
        Db::startTrans();

        // 数据处理
        if($params['type'] == 'agree')
        {
            $status=array('status'=>1,'note'=>$params['note'],'up_time'=>time());
            $res=Db::name('user_overlog')->where('id','=',$params['id'])->update($status);
            //var_dump($res);exit;
            //echo Db::name('user_overlog')->getLastSql();exit;
            if(!$res){
                Db::rollback();
                return DataReturn('操作失败', -101);
            }
            $updata_user=['realname'=>$user_data['real_name'],'identityid'=>$user_data['card_number'],'is_auth'=>1];
            $ret=Db::name('user')->where('id','=',$user_data['user_id'])->update($updata_user);
            if(!$ret){
                Db::rollback();
                return DataReturn('操作失败', -101);
            }

        } else {

            $status=array('status'=>2,'note'=>$params['note'],'up_time'=>time());
            $res=Db::name('user_overlog')->where('id','=',$params['id'])->update($status);
            //echo Db::name('user_overlog')->getLastSql();exit;
            if(!$res){
                Db::rollback();
                return DataReturn('操作失败', -101);
            }
        }


        // 处理成功
        Db::commit();
        return DataReturn('操作成功', 0);
    }
}
