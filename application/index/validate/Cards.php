<?php
namespace app\index\validate;

use think\Validate;

class Cards extends Validate {
    protected $rule = [
        'name|名称'             => 'require',
        
    ];
    protected $message = [
        'name.require'             => '轮播名称不能为空',
        
    ];
}
