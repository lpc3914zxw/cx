<?php
namespace app\tutor\validate;

use think\Validate;

/*
 * 星际会员等级
 */
class Tutor extends Validate {
    protected $rule = [
        'name|专栏名称'             => 'require',
        'imgurl|专栏头像'      => 'require',
        'content|专栏介绍'      => 'require'

    ];
    protected $message = [
        'name.require'             => '专栏名称不能为空',
        'imgurl.require'      => '请上传专栏头像',
        'content.require'      => '专栏介绍不能为空'
    ];
}
