<?php
namespace app\index\validate;

use think\Validate;

/*
 * 星际会员等级
 */
class StartLevel extends Validate {
    protected $rule = [
        'name|等级名称'             => 'require',
        'value|等级值'      => 'require',
        'invite_people|直接邀请人数'      => 'require',
        'contribution|贡献值'      => 'require',
        //'advanced_id|赠送课程id'      => 'require',
        'bonus|置换手续费分红'      => 'require',
        'learn_accelerate|学习周期加速比例'      => 'require',
        'small_sq|小社群贡献值'      => 'require',
    ];
    protected $message = [
        'name.require'             => '等级名称不能为空',
        'value.require'      => '等级值不能为空',
        'invite_people.require'      => '直接邀请人数不能为空',
        'contribution.require'      => '贡献值不能为空',
        //'advanced_id.require'      => '请选择赠送课程',
        'bonus.require'      => '置换手续费分红百分比不能为空',
        'learn_accelerate.require'      => '学习周期加速比例不能为空',
        'small_sq.require'      => '小社群贡献值不能为空',
    ];
}
