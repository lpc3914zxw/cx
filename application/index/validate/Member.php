<?php
namespace app\index\validate;

use think\Validate;

class Member extends Validate {
    protected $rule = [
        'username|登录名称'             => 'require',
        'password|登录密码'      => 'require'
    ];
    protected $message = [
        'username.require'             => '请输入登录名',
        'password.require'      => '请输入登录密码'
    ];
}