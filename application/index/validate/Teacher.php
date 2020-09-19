<?php
namespace app\index\validate;

use think\Validate;

/*
 * 老师
 */
class Teacher extends Validate {
    protected $rule = [
        'name|老师名称'             => 'require',
        'introduction|老师介绍'      => 'require',
        'headimg|老师头像'      => 'require',
        'imgurl|大图'      => 'require',
        'tel|手机号'      => 'require',
    ];
    protected $message = [
        'name.require'             => '老师名称不能为空',
        'introduction.require'      => '老师介绍不能为空',
        'headimg.require'      => '请上传老师头像',
        'imgurl.require'      => '请上传大图',
        'tel.require'      => '请填写用户注册APP绑定的手机号',
    ];
}