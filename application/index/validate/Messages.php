<?php
namespace app\index\validate;

use think\Validate;

/*
 * 系统消息
 */
class Messages extends Validate {
    protected $rule = [
        'title|标题'             => 'require',
        'abstract|摘要'      => 'require',
        'content|内容'      => 'require'
    ];
    protected $message = [
        'title.require'             => '标题不能为空',
        'abstract.require'      => '摘要不能为空',
        'content.require'      => '内容不能为空'
    ];
}