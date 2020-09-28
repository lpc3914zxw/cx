<?php
namespace app\wxapp\validate;

use think\Validate;

class Tutor extends Validate {
    protected $rule = [
        'apply_type|申请类型'             => 'require',
        'name|专栏名称'      => 'require',
        'imgurl|专栏头像'      => 'require',
        'content|专栏介绍'      => 'require'
    ];
    protected $message = [
        'apply_type.require'             => '请选择申请类型',
        'name.require'      => '请输入专栏名称',
        'imgurl.require'      => '请上传专栏头像',
        'content.require'      => '请输入专栏介绍'
    ];
}