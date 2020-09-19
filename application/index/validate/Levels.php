<?php
namespace app\index\validate;

use think\Validate;

/*
 * 会员等级
 */
class Levels extends Validate {
    protected $rule = [
        'name|等级名称'             => 'require',
        'service_charge|置换手续费'      => 'require',
        'value|学习力'      => 'require'
    ];
    protected $message = [
        'name.require'             => '等级名称不能为空',
        'service_charge.require'      => '置换手续费不能为空',
        'value.require'      => '等级值不能为空',
    ];
}
