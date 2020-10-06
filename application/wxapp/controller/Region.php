<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-09-28
 * Time: 18:03
 */

namespace app\wxapp\controller;
use app\service\RegionService;


class Region
{
    /**
     * 获取地区
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-09-21
     * @desc    description
     */
    public function Index()
    {

        // 获取地区
        $params = [
            'where' => [
                'pid'   => intval(input('pid', 0)),
            ],
        ];
        $data = RegionService::RegionNode($params);
        //return DataReturn('操作成功', 0, $data);
        return returnjson(1000,$data,'获取成功');
    }




}
