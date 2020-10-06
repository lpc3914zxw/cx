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

/**
 * 地区服务层
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-01T21:51:08+0800
 */
class PetersService
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
    public static function Save($params = [],$uid)
    {

        // 数据
        $data = [
            'uid'=>$uid,
            'province'=>$params['province'],
            'city'=>$params['city'],
            'county'=>$params['county'],
        ];

        if(empty($params['id']))
        {
            $data['addtime'] = time();
            $data['status'] = 0;
            if(Db::name('peters_log')->insertGetId($data) > 0)
            {
                return DataReturn('申请已提交', 1000);
            }
            return DataReturn('申请提交失败,请重试', 1001);
        }
    }

}
?>
