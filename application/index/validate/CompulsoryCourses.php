<?php
namespace app\index\validate;

use think\Validate;

class CompulsoryCourses extends Validate {
    protected $rule = [
        'name|姓名'             => 'require',
        'abstract|摘要'      => 'require'
    ];
    protected $message = [
        'name.require'             => '姓名必须填',
        'abstract.require'      => '摘要必填',
    ];
}