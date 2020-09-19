<?php
namespace app\index\validate;

use think\Validate;

class Advertisement extends Validate {
    protected $rule = [
        'name|名称'             => 'require',
        'imgurl|轮播图'             => 'require',
        'link|链接地址'             => 'require',
    ];
    protected $message = [
        'name.require'             => '轮播名称不能为空',
        'imgurl.require'             => '轮播图不能为空',
        'link.require'             => '链接不能为空',
    ];
}
