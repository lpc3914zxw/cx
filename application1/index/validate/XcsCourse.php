<?php
namespace app\index\validate;

use think\Validate;

/*
 * 学财商课程
 */
class XcsCourse extends Validate {
    protected $rule = [
        'name|姓名'             => 'require',
        'abstract|摘要'             => 'require',
        'imgurl|顶部图片'      => 'require',
        'course_bright|课程亮点'      => 'require',
        'complete_course|完整课程'      => 'require',
        'samll_imgurl|缩略图'      => 'require',

    ];
    protected $message = [
        'name.require'             => '姓名不能为空',
        'abstract.require'             => '摘要不能为空',
        'imgurl.require'      => '请上传课程顶部图片',
        'course_bright.require'      => '课程亮点不能为空',
        'complete_course.require'      => '请上传完整课程图片',
        'samll_imgurl.require'      => '请上传缩略图',
    ];
    protected $scene = [
        'compulsorycourse' => ['name','imgurl','complete_course','course_bright','abstract']
    ];
}