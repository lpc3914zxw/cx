<?php
namespace app\index\validate;

use think\Validate;

class Category extends Validate {
    protected $rule = [
        'name|分类 名称'             => 'require',
        'value|兑换所需学分|金额'             => 'require',
        'chapter_count|课时数'             => 'require',
        'pay_type|支付方式'             => 'require',
        'imgurl|图片'             => 'require',
    ];
    protected $message = [
        'name.require'             => '分类名称不能为空',
        'value.require'             => '兑换所需学分不能为空',
        'chapter_count.require'             => '课时数不能为空',
        'pay_type.require'             => '请选择支付方式',
        'imgurl.require'             => '请上传图片',
    ];
}
