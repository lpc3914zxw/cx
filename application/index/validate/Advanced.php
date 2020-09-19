<?php
namespace app\index\validate;

use think\Validate;

class Advanced extends Validate {
    protected $rule = [
        'name|进阶名称'             => 'require',
        'difficulty|难度系数'             => 'require',
        'learn_power|学习力'             => 'require',
        'reward|学分奖励'             => 'require',
        'value|兑换所需学分'             => 'require',
        'chapter_count|课时数'             => 'require',
        'deadline|有效期(天)'             => 'require',
        'studying_num|同时在学课程数'             => 'require',
        'pay_type|支付方式'             => 'require',
    ];
    protected $message = [
        'name.require'             => '进阶名称不能为空',
        'difficulty.require'             => '难度系数不能为空',
        'learn_power.require'             => '学习力不能为空',
        'reward.require'             => '学分奖励不能为空',
        'value.require'             => '兑换所需学分不能为空',
        'chapter_count.require'             => '课时数不能为空',
        'deadline.require'             => '有效期(天)不能为空',
        'studying_num.require'             => '同时在学课程数不能为空',
        'pay_type.require'             => '请选择支付方式',
    ];
}
