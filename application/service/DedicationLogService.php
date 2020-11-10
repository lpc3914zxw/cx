<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-09
 * Time: 10:10
 */

namespace app\service;

use think\Db;
class DedicationLogService
{

    public static function addDedicationLog($params){
        return  Db::name('dedication_log')->insert($params);
    }
}
