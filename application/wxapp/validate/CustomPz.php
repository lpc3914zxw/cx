<?php
namespace app\wxapp\validate;

use think\Validate;

/*
 * 合同上传凭证
 */
class CustomPz extends Validate {
    protected $rule = [
        'zcard|身份证正面照片'             => 'require',
        'fcard|身份证反面照片'      => 'require',
        'bankcard|银行卡正面'      => 'require',
        'dkimg|打款凭证'      => 'require'
    ];
    protected $message = [
        'zcard.require'             => '请上传身份证正面照片',
        'fcard.require'      => '请上传身份证反面照片',
        'bankcard.require'      => '请上传银行卡正面',
        'dkimg.require'      => '请上传打款凭证'
    ];
}