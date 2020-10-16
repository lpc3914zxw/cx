<?php
//index模块配置文件
return [
    // 应用调试模式
    //'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false,
    //视图输出字符串内容替换
    'view_replace_str' => [
        '__STATIC__' => '/static/hplus',
        '__UEDITOR__' => '/static/ueditor'
    ],
//    'COS'=>'https://cloudant-1256594151.cos.ap-shanghai.myqcloud.com/images',
//    'COS1'=>'https://cloudant-1256594151.cos.ap-shanghai.myqcloud.com',
    //验证手机正则表达式
    'regex_phone' => '/^1[3|4|5|7|8][0-9]{9}$/',
    //登录cookie保存时间
    'login_time' => 7*24*3600,

    //阿里云OSS
    'oss' => [
        'accessKeyId' => 'LTAI4G8FpLy191oBhNyd3Vss',
        'accessKeySecret' => 'cKSH1ZPeZt7HKsxjaIp31jU6jN46uS',
        'endpoint' => 'http://oss-cn-shanghai.aliyuncs.com',
        'bucket' => 'caixue'
    ],
    //极光推送
    'jPush' => [
        'appKey' => '8f08de886dd9251c7990893f',
        'masterSecret' => '31878b44c0ee41fca8aaa0b5'
    ],


    /*'cache'                  => [
       // 驱动方式
       'type'   => 'File',
       // 缓存保存目录
       'path'   => CACHE_PATH,
       // 缓存前缀
       'prefix' => '',
       // 缓存有效期 0表示永久缓存
       'expire' => 0,
   ],*/

     //缓存设置
     // 'cache' => [
     //     // 驱动方式
     //     'type'   => 'redis',
     //     'prefix'=>  'sys_',
     //     'host' => '127.0.0.1',
     //     'password' => ''
     // ],
//    'serarchconfig' => [
//        'DB_TYPE' => 'elasticsearch',
//        'DB_HOST' => '116.62.63.155',
//        'DB_PORT'=>'9200',
//        'DB_INDEX' => 'cloudant_api',
//        'DB_TABLE'=>'mall_goods'
//    ]
];
