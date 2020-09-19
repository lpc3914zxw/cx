<?php
namespace app\index\validate;

use think\Validate;

/*
 * 荣誉值
 */
class Honor extends Validate {
    protected $rule = [
        'name|名称'             => 'require',
        'type|类别值'             => 'require',
        'contribution|单次获得荣誉值'      => 'require',
        'note|说明'      => 'require'
    ];
    protected $message = [
        'name.require'             => '名称不能为空',
        'type.require'             => '类别值不能为空',
        'contribution.require'      => '单次获得荣誉值不能为空',
        'note.require'      => '说明不能为空'
    ];
}