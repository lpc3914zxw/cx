<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-09
 * Time: 10:10
 */

namespace app\service;

use think\Db;
class LearnPowerLogService
{

    public static function addLearnPowerLog($params){
        return  Db::name('learning_power_log')->insert($params);
    }
}
