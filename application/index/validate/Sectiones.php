<?php
namespace app\index\validate;

use think\Validate;

/*
 * 课程课时
 */
class Sectiones extends Validate {
    protected $rule = [
        'name|课时名称'             => 'require',
        'audiourl|音频文件'      => 'require',
        'audiotime|课时数量'      => 'require',
        'audiosize|课程亮点'      => 'require',
        'content|过期时间'      => 'require',
        'sort|排序'      => 'require',
    ];
    protected $message = [
        'name.require'             => '课时名称不能为空',
        'audiourl.require'      => '请上传音频',
        'audiotime.require'      => '请填写文件时长',
        'audiosize.require'      => '请填写音频文件大小',
        'content.require'      => '内容不能为空',
        'sort.require'      => '排序不能为空'
    ];
}