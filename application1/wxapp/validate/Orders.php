<?php
namespace app\wxapp\validate;

use think\Validate;

class Orders extends Validate {
    protected $rule = [
        //'headimg|头像'             => 'require',
        'username|姓名'      => 'require',
        'gender|性别'      => 'require',
        'group_id|参赛组别'      => 'require'
    ];
    protected $message = [
        //'headimg.require'             => '请上传头像',
        'username.require'      => '请输入姓名',
        'gender.require'      => '请选择性别',
        'group_id.require'      => '请选择组别'
    ];
}