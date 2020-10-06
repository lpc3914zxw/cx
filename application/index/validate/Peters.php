<?php
namespace app\index\validate;

use think\Validate;

/*
 * 荣誉值
 */
class Peters extends Validate {
    protected $rule = [
        'name|名称'             => 'require',
        'type|类别值'             => 'require',
        'content|内容'      => 'require'
    ];
    protected $message = [
        'name.require'             => '名称不能为空',
        'type.require'             => '类别值不能为空',
        'content.require'      => '内容不能为空'
    ];
}
