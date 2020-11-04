<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-02
 * Time: 10:39
 */

namespace app\service;


use think\Db;

class NodeMapService
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
    public static function nodemapList($map=[])
    {
        $total = Db::name('node_map')->where($map)->count(1);
        $list =  Db::name('node_map')->where($map)->limit(page())->select();
        return page_data($total, $list);
    }
    public static function getList($map = []) {
        $total = Db::table('node_map')->where($map)->count(1);
        $list = Db::table('node_map')->select(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return page_data($total, $list);
    }
    public static function nodemapListDefault($where = [])
    {
        //$where = empty($params['where']) ? [] : $params['where'];
        $order_by = 'id desc';

        $data = Db::name('node_map')->where($where)->order($order_by)->select();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                // 时间
                if(isset($v['add_time']))
                {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }
                if(isset($v['upd_time']))
                {
                    $v['upd_time'] = empty($v['upd_time']) ? '' : date('Y-m-d H:i:s', $v['upd_time']);
                }
            }
        }
        return DataReturn('处理成功', 0, $data);
    }
    /**
     * 保存
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-12-18
     * @desc    description
     * @param   [array]          $params [输入参数]
     */
    public static function nodemapAdd($params=[])
    {
        // 请求类型
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'module',
                'checked_data'      => '2,16',
                'error_msg'         => '名称格式 2~16 个字符',
            ],
        ];
        $ret = ParamsChecked($params, $p);
        if($ret !== true)
        {
            return DataReturn($ret, -1);
        }

        // 数据
        $data = [
            'module'        => $params['module'],
            'controller'    => $params['controller'],
            'action'        => $params['action'],
            'method'        => $params['method'],
            'comment'       => $params['comment'],
        ];

        if(empty($params['id']))
        {
            $data['add_time'] = time();
            if(Db::name('node_map')->insertGetId($data) > 0)
            {
                return DataReturn('添加成功', 0);
            }
            return DataReturn('添加失败', -100);
        } else {
            $data['upd_time'] = time();
            if(Db::name('node_map')->where(['id'=>intval($params['id'])])->update($data))
            {
                return DataReturn('编辑成功', 0);
            }
            return DataReturn('编辑失败', -100);
        }
    }

}
