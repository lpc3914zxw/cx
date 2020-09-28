<?php
namespace app\tutor\validate;

use think\Validate;

/*
 * 星际会员等级
 */
class Knowledge extends Validate {
    protected $rule = [
        'title|专栏名称'             => 'require',
        'imgurl|文章图片'      => 'require',
        'content|文章内容'      => 'require',

    ];
    protected $message = [
        'title.require'             => '专栏名称不能为空',
        //'imgurl.require'      => '请上传专栏头像',
        'content.require'      => '专栏介绍不能为空'
    ];
}
