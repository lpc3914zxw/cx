<?php
/**
 * Created by 七月
 * Author: 七月
 * 微信公号: 小楼昨夜又秋风
 * 知乎ID: 七月在夏天
 * Date: 2017/2/22
 * Time: 13:49
 */

return [
    //  +---------------------------------
    //  微信相关配置
    //  +---------------------------------

    // 小程序app_id
    'app_id' => 'wxdc7673f0ef6116c6',
    // 小程序app_secret
    'app_secret' => '',

    // 微信使用code换取用户openid及session_key的url地址
    'login_url' => "https://api.weixin.qq.com/sns/jscode2session?" .
        "appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",

    // 微信获取access_token的url地址
    'access_token_url' => "https://api.weixin.qq.com/cgi-bin/token?" .
        "grant_type=client_credential&appid=%s&secret=%s",

    //
    'qrcode_b_url'=>"https://api.weixin.qq.com/wxa/getwxacodeunlimit?".
    "access_token=%s",
    'messages'=>"https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?".
        "access_token=%s",
    'push'=>"https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?".
        "access_token=%s",
];
