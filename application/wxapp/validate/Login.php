<?php
namespace app\wxapp\validate;

use think\Validate;

class Login extends Validate {
    protected $rule = [
        'tel|手机号'      => 'require',
        'password|密码'      => 'require',
        'rpwd|确认密码'      => 'require',
        'code|验证码'      => 'require',
    ];
    protected $message = [
        'tel.require'             => '请填写手机号',
        'password.require'      => '请输入密码',
        'rpwd.require'      => '请输入确认密码',
        'code.require'      => '请输入验证码'
    ];
}