<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-09
 * Time: 10:10
 */

namespace app\service;

use think\Db;
class CreditSoureService
{
    /***
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function userEveryValue($uid){
        return  Db::name('credit_source')->where('id',$uid)->find();
    }

    public static function addCreditSource($params){
        return  Db::name('credit_source')->insert($params);
    }
}
