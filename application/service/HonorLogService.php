<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-09
 * Time: 10:10
 */

namespace app\service;

use think\Db;
class HonorLogService
{

    public static function addHonorLog($params){
        return  Db::name('honor_log')->insert($params);
    }
}
