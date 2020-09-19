<?php
//index模块配置文件
return [
    // 应用调试模式
    //'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false,
    //验证手机正则表达式
    'regex_phone' => '/^1[3|4|5|7|8][0-9]{9}$/',
    //登录cookie保存时间
    'login_time' => 7*24*3600,
    'view_replace_str' => [
        '__STATIC__' => '/static/hplus'
    ],
    'oss' => [
        'accessKeyId' => 'LTAI4G8FpLy191oBhNyd3Vss',
        'accessKeySecret' => 'cKSH1ZPeZt7HKsxjaIp31jU6jN46uS',
        'endpoint' => 'http://oss-cn-shanghai.aliyuncs.com',
        'bucket' => 'caixue',
        'url'=>"https://caixue.oss-cn-shanghai.aliyuncs.com"
    ],
    //极光推送
    'jPush' => [
        'appKey' => '81f7ba5173b6759f9aeeb1ab',
        'masterSecret' => '00d743a1d8b4707ebfb92438'
    ],

];