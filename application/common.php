<?php
// 应用公共文件
use think\Loader;
use Qcloud\Cos\Client;
use think\Config;
use think\Db;
use think\Response;
/**
 * 验证图形验证码
 * @author Steed
 * @param $code
 * @param $id
 * @return bool
 */

date_default_timezone_set('PRC');
function check_verify($code, $id) {
    $verify = new \think\captcha\Captcha();
    return $verify->check($code, $id);
}

//密码加密
function splice_pwd($pwd,$salt){
    return md5(md5($pwd).$salt);
}
/**
 * 生成url地址
 * @author   Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2018-06-12
 * @desc    description
 * @param   string          $path      [路径地址]
 * @param   array           $params    [参数]
 */
function MyUrl($path, $params=[])
{
    // 调用框架生成url
    $url = url($path, $params, true, true);

    // 是否根目录访问项目
    if(defined('IS_ROOT_ACCESS'))
    {
        $url = str_replace('public/', '', $url);
    }

    // tp框架url方法是否识别到https
    if(__MY_HTTP__ == 'https' && substr($url, 0, 5) != 'https')
    {
        $url = 'https'.mb_substr($url, 4, null, 'utf-8');
    }

    // 避免从后台生成url入口错误
    $script_name = CurrentScriptName();
    if($script_name != 'index.php' && substr($path, 0, 6) != 'admin/')
    {
        $url = str_replace($script_name, 'index.php', $url);
    }

    return $url;
}
/**
 * 获取当前脚本名称
 * @author  Devil
 * @blog    http://gong.gg/
 * @version 1.0.0
 * @date    2019-06-20
 * @desc    description
 */
function CurrentScriptName()
{
    $name = '';
    if(empty($_SERVER['SCRIPT_NAME']))
    {
        if(empty($_SERVER['PHP_SELF']))
        {
            if(!empty($_SERVER['SCRIPT_FILENAME']))
            {
                $name = $_SERVER['SCRIPT_FILENAME'];
            }
        } else {
            $name = $_SERVER['PHP_SELF'];
        }
    } else {
        $name = $_SERVER['SCRIPT_NAME'];
    }

    if(!empty($name))
    {
        $loc = strripos($name, '/');
        if($loc !== false)
        {
            $name = substr($name, $loc+1);
        }
    }

    return $name;
}
function tranTime($time)
{
    $rtime = date("m-d",$time);
    $htime = date("H:i",$time);
    $time = time() - $time;
    if ($time < 60)
    {
        $str = '刚刚';
    }
    elseif ($time < 60 * 60)
    {
        $min = floor($time/60);
        $str = $min.'分钟前';
    }
    elseif ($time < 60 * 60 * 24)
    {
        $h = floor($time/(60*60));
        $str = $h.'小时前 ';
    }
    elseif ($time < 60 * 60 * 24 * 3)
    {
        $d = floor($time/(60*60*24));
        if($d==1)
            $str = '昨天 '.$htime;
        else
            $str = '前天 '.$htime;
    }
    else
    {
        $str = $rtime;
    }
    return $str;
}

// 获取当前访问url
function GetCurUrl() {
    $url = 'http://';
    if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        $url = 'https://';
    }
    $url .= $_SERVER['SERVER_NAME'];
    return $url;
}

/*
 * 返回json数据
 * @param type $code
 * @param type $data
 * @param type $msg
 * @return type
 */
function returnjson($code = 0, $data, $msg = '') {
    $code = (int) $code;
    return json_encode(['code' => $code, 'data' => $data, 'msg' => $msg]);
}

function object_to_array($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }
    return $obj;
}


/*
 * 拼接密码
 * @author Steed
 * @param $password
 * @param $salt
 * @return string
 */
function splice_password($password, $salt) {
    return md5($password . '-' . $salt);
}

/*
 * 生成随机字符串
 * @param $length
 * @return null|string
 */
function get_rand_char($length) {
    $str = null;
    $strPol = "0123456789";
    $max = strlen($strPol) - 1;

    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];    //rand($min,$max)生成介于min和max两个数之间的一个随机整数
    }
    return $str;
}

/*
 * 获取指定年月的开始和结束时间戳
 * @param int $year 年份
 * @param int $month 月份
 * @return array(开始时间,结束时间)
 */
function getMonthBeginAndEnd($year = 0, $month = 0) {
    $year = $year ? $year : date('Y');
    $month = $month ? $month : date('m');
    $d = date('t', strtotime($year . '-' . $month));
    return ['startTime' => strtotime($year . '-' . $month), 'endTime' => mktime(23, 59, 59, $month, $d, $year)];
}

/*
 * 获取指定年月的开始和结束时间戳
 * @param int $year 年份
 * @param int $month 月份
 * @return array(开始时间,结束时间)
 */
function getDayBeginAndEnd($year = 0, $month = 0,$day = 0) {
    $year = $year ? $year : date('Y');
    $month = $month ? $month : date('m');
    return ['startTime' => mktime(0, 0, 0, $month, $day, $year), 'endTime' => mktime(23, 59, 59, $month, $day, $year)];
}


/**
 * 把本地变量的内容到文件
 * 简单上传,上传指定变量的内存值作为object的内容
 */
function uploadLocalToOss($perferimg, $dir = '',$filename) {
    \think\Loader::import('OSS.Oss', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.OssClient', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Core.OssUtil', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Core.MimeTypes', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Http.RequestCore', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Http.ResponseCore', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Result.Result', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Result.PutSetDeleteResult', VENDOR_PATH, EXT);

    $oss = \think\Config::get('oss');
    $ossClient = new \OSS\OssClient($oss['accessKeyId'],$oss['accessKeySecret'],$oss['endpoint']);
    $content = file_get_contents($perferimg); // 把当前文件的内容获取到传入文件中
    $options = array();
    try {
        $filename = $dir."/".$filename;
        $ossClient->putObject($oss['bucket'],$filename , $content, $options);
    } catch (OssException $e) {
        return $e->getMessage();
    }
    return $oss['url']."/".$filename;
}

// 自动转换字符集 支持数组转换
function autoCharset($fContents, $from='gbk', $to='utf-8') {
    $from  = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to   = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($fContents, $to, $from);
    } elseif (function_exists('iconv')) {
        return iconv($from, $to, $fContents);
    } else {
        return $fContents;
    }
}


/**
 * 阿里云OSS图片上传
 * @author Steed
 * @param $file
 * @param $rootpath
 * @return array
 */
function uploadOss($file, $rootpath) {
    $data = [];
    $savepath = '/' . date('Ymd');
    \think\Loader::import('OSS.Oss', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.OssClient', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Core.OssUtil', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Core.MimeTypes', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Http.RequestCore', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Http.ResponseCore', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Result.Result', VENDOR_PATH, EXT);
    \think\Loader::import('OSS.Result.PutSetDeleteResult', VENDOR_PATH, EXT);

    $oss = new \OSS\Oss(\think\Config::get('oss'));
    //单文件上传
    if (is_object($file)) {
        $file = $file->getInfo();
        //获取文件后缀
        $file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);
        /* 检查文件后缀 */
        if (!$oss->checkExt($file['ext'])) return ['status' => false, 'msg' => $oss->getError()];
        /* 检查文件大小 */
        if (!$oss->checkSize($file['size'])) return ['status' => false, 'msg' => $oss->getError()];
        $file['savepath'] = $rootpath . $savepath . '/';
        /* 生成文件名 */
        $file['savename'] = time() . rand(1, 10000) . '.' . $file['ext'];
        /* 上传文件 */
        if (!$oss->save($file)) return ['status' => false, 'msg' => $oss->getError()];

        $data = ['savepath' => $file['savepath'] . $file['savename'], 'savename' => $file['savename']];

    } else if (is_array($file)) {
        //多文件上传
        foreach ($file as $key => $value) {
            $value = $value->getInfo();
            //获取文件后缀
            $value['ext'] = pathinfo($value['name'], PATHINFO_EXTENSION);
            /* 检查文件后缀 */
            if (!$oss->checkExt($value['ext'])) {
                return ['status' => false, 'msg' => $oss->getError()];
            }
            /* 检查文件大小 */
            if (!$oss->checkSize($value['size'])) {
                return ['status' => false, 'msg' => $oss->getError()];
            }
            $value['savepath'] = $rootpath . $savepath . '/';
            /* 生成文件名 */
            $value['savename'] = time() . rand(1, 10000) . '.' . $value['ext'];
            /* 上传文件 */
            if (!$oss->save($value)) {
                return ['status' => false, 'msg' => $oss->getError()];
            }
            $data[$key] = ['savepath' => $value['savepath'], 'savename' => $value['savename'], 'ext' => $value['ext'], 'size' => $value['size'], 'name' => $value['name']];
        }
    }
    return ['status' => true, 'msg' => '', 'data' => $data];
}

/*
 * 腾讯云COS图片上传
 * @author Steed
 * @param $file
 * @param $rootpath
 * @return array
 */
function uploadCos($file, $rootpath) {
    $data = [];
    $savepath = '/' . date('Ymd');
     Loader::import('COS.cos-autoloader',VENDOR_PATH);
     $systeminfo = Db::table('system')->find();
     $cosClient = new Client(array(
          'region' => $systeminfo['region'],
          'credentials'=> array(
              'appId' => $systeminfo['cos_appid'],
              'secretId'    => $systeminfo['secretid'],
              'secretKey' => $systeminfo['secretkey'])));
    //单文件上传
    if (is_object($file)) {
        $file = $file->getInfo();
        $fileinfo = file_get_contents($file['tmp_name']);
        //获取文件后缀
        $file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);
        /* 检查文件后缀 */
        $extarr = array('jpg', 'gif', 'png', 'jpeg','JPG','GIF','PNG','JPEG','mp3','MP3','WAVE','WAV','wav','APE','wave','ape');
        if(!in_array($file['ext'], $extarr)){
            return ['status' => false, 'msg' => '非法文件类型'];
        }
        /* 检查文件大小 */
        if($file['size'] == 0 && $file['size'] > 5242880){  //大于5m
            return ['status' => false, 'msg' => '文件大小不符合'];
        }
        $file['savepath'] = $rootpath . $savepath . '/';
        /* 生成文件名 */
        $file['savename'] = time() . rand(1, 10000) . '.' . $file['ext'];
        /* 上传文件 */

        $result = $cosClient->upload(
            $bucket = $systeminfo['bucket'],
            $key = $file['savepath'].$file['savename'],  //上传路径
            $body =  $fileinfo,
            $options = array(
                //"ACL"=>'public-read',
                'CacheControl' => 'private',
                'ServerSideEncryption' => 'AES256')
        );
        $data = ['savepath' => $file['savepath'] . $file['savename'], 'savename' => $file['savename']];

    } else if (is_array($file)) {
        //多文件上传
        foreach ($file as $k => $value) {
            $value = $value->getInfo();
            $fileinfo = file_get_contents($value['tmp_name']);
            //获取文件后缀
            $value['ext'] = pathinfo($value['name'], PATHINFO_EXTENSION);
            /* 检查文件后缀 */
            $extarr = array('jpg', 'gif', 'png', 'jpeg');
            $ext = pathinfo($value['name'], PATHINFO_EXTENSION);
            if(!in_array($ext, $extarr)){
                return ['status' => false, 'msg' => '非法文件类型'];
            }
            if($value['size'] == 0 && $value['size'] > 5242880){  //大于5m
                return ['status' => false, 'msg' => '文件大小不符合'];
            }
            $value['savepath'] = $rootpath . $savepath . '/';
            /* 生成文件名 */
            $value['savename'] = time() . rand(1, 10000) . '.' . $value['ext'];
            /* 上传文件 */
             $result = $cosClient->upload(
                 $bucket = $systeminfo['bucket'],
                $key = '/images/'.$value['savepath'].$value['savename'],  //上传路径
                $body =  $fileinfo,
                $options = array(
                    //"ACL"=>'public-read',
                    'CacheControl' => 'private',
                    'ServerSideEncryption' => 'AES256')

            );
            $data[$k] = ['savepath' => $value['savepath'], 'savename' => $value['savename'], 'ext' => $value['ext'], 'size' => $value['size'], 'name' => $value['name']];
        }
    }
    return ['status' => true, 'msg' => '', 'data' => $data];
}

/**
 * 将服务器根目录下的图片上传到COS上
 */
function uploadLocalCos($file,$filename,$rootpath){
    $cosinfo = Config::get('cos');
    $fileinfo = file_get_contents($file);
    Loader::import('COS.cos-autoloader',VENDOR_PATH);
    $systeminfo = Config::get('sysinfo');
    $cosClient = new Client(array(
        'region' => $systeminfo['region'],
        'credentials'=> array(
            'appId' => $systeminfo['cos_appid'],
            'secretId'    => $systeminfo['secretid'],
            'secretKey' => $systeminfo['secretkey'])));

    $imagepath = $rootpath . '/'.date('Ymd',time()).'/'.$filename;
    $result = $cosClient->upload(
        $bucket = $systeminfo['bucket'],
        $key = $imagepath,  //上传路径
        $body =  $fileinfo,
        $options = array(
            'CacheControl' => 'private',
            'ServerSideEncryption' => 'AES256')
    );
    unlink($file);
    return $cosinfo['cos'].$imagepath;
}



/**
 * 递归处理数据
 * @author Steed
 * @param array $data
 * @param int $parent_id
 * @return array
 */
function recursion_data($data = [], $parent_id = 0) {
    $tmp = [];
    foreach ($data as $value) {
        if ($value['parent_id'] == $parent_id) {
            $value['child'] = recursion_data($data, $value['id']);
            $tmp[] = $value;
        }
    }
    return $tmp;
}

/*
 * 排序函数
 * @param $sort
 * @param $order
 * @return string
 */
function sort_order($sort = 'id', $order = 'desc') {
    $sort = \think\Request::instance()->has('sort') ? \think\Request::instance()->param('sort') : $sort;
    $order = \think\Request::instance()->has('order') ? \think\Request::instance()->param('order') : $order;
    $sort = empty($sort) ? 'id' : $sort;
    $order = empty($order) ? 'desc' : $order;
    return $sort . ' ' . $order;
}

/*
 * 分页函数
 * @author Steed
 * @param int $rows
 * @return array
 */
function page($rows = 15) {
    //判断是否开始条数
    $start = \think\Request::instance()->has('offset') ? \think\Request::instance()->param('offset') : 0;
    //判断是否存在每页显示的条数
    $rows = \think\Request::instance()->has('limit') ? \think\Request::instance()->param('limit') : $rows;
    //返回开始条数和每页显示的条数
    return $start . ',' . $rows;
}

/*
 * 组成分页之后的数据
 * @author Steed
 * @param $total
 * @param $rows
 * @return array
 */
function page_data($total, $rows) {
    return ['total' => $total, 'rows' => $rows];
}

//图片转base64
function base64EncodeImage ($image_file) {
  $base64_image = '';
  $image_info = getimagesize($image_file);
  $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
  $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
  return $base64_image;
}

//生成6位数验证码
function generate_code($length = 6) {
    $min = pow(10 , ($length - 1));
    $max = pow(10, $length) - 1;
    return rand($min, $max);
}



/*
 * 转换为圆形
 */
function yuan_img($imgpath)
{
    $wh  = getimagesize($imgpath);//pathinfo()不准
    $src_img = null;
    switch ($wh[2]) {
        case 1:
            //gif
            $src_img = imagecreatefromgif($imgpath);
            break;
        case 2:
            //jpg
            $src_img = imagecreatefromjpeg($imgpath);
            break;
        case 3:
            //png
            $src_img = imagecreatefrompng($imgpath);
            break;
    }
    $w   = $wh[0];
    $h   = $wh[1];
    $w   = min($w, $h);
    $h   = $w;
    $img = imagecreatetruecolor($w, $h);
    //这一句一定要有
    imagesavealpha($img, true);
    //拾取一个完全透明的颜色,最后一个参数127为全透明
    $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
    imagefill($img, 0, 0, $bg);
    $r   = $w / 2; //圆半径
    $y_x = $r; //圆心X坐标
    $y_y = $r; //圆心Y坐标
    for ($x = 0; $x < $w; $x++) {
        for ($y = 0; $y < $h; $y++) {
            $rgbColor = imagecolorat($src_img, $x, $y);
            if (((($x - $r) * ($x - $r) + ($y - $r) * ($y - $r)) < ($r * $r))) {
                imagesetpixel($img, $x, $y, $rgbColor);
            }
        }
    }
    return [$img,$w];
}

/*
 * 根据指定尺寸裁剪目标图片，这里统一转成132*132的
 * 注意第一个参数，为了简便，直接传递的是图片资源，如果是绝对地址图片路径，可以加以改造
 */
function get_new_size($imgpath,$new_width,$new_height,$w)
{
    $image_p = imagecreatetruecolor($new_width, $new_height);//新画布
    $bg = imagecolorallocatealpha($image_p, 255, 255, 255, 127);
    imagefill($image_p, 0, 0, $bg);
    imagecopyresampled($image_p, $imgpath, 0, 0, 0, 0, $new_width, $new_height, $w, $w);
    return $image_p;
}

/*
 * 根据绝对路径的图片地址获取对应的图片资源，
 */
function get_resource_by_img($img)
{
    $wh  = getimagesize($img);//比pathinfo要准
    $src_img = null;
    switch ($wh[2]) {
        case 1:
            //gif
            $src_img = imagecreatefromgif($img);
            break;
        case 2:
            //jpg
            $src_img = imagecreatefromjpeg($img);
            break;
        case 3:
            //png
            $src_img = imagecreatefrompng($img);
            break;
    }
    return $src_img;
}

//极光推送开发
function send_jpush($message) {
    \think\Loader::import('JPush.Client', VENDOR_PATH, EXT);
    \think\Loader::import('JPush.Config', VENDOR_PATH, EXT);
    \think\Loader::import('JPush.PushPayload', VENDOR_PATH, EXT);
    \think\Loader::import('JPush.Http', VENDOR_PATH, EXT);
    $config = \think\Config::get('jPush');
    $client = new \JPush\Client($config['appKey'], $config['masterSecret']);//实例化对象，读取配置文件中的
    $result = $client->push()
        ->setPlatform('all')
        ->addAllAudience()
        ->setNotificationAlert($message)
        ->send();
    var_dump($result);exit;
    if ($result['http_code'] === 200) return true;
    return false;
}

//指定用户推送消息
  function one_push($message,$regid = '1104a897922223df539') {
    \think\Loader::import('JPush.Client', VENDOR_PATH, EXT);
    \think\Loader::import('JPush.Config', VENDOR_PATH, EXT);
    \think\Loader::import('JPush.PushPayload', VENDOR_PATH, EXT);
    \think\Loader::import('JPush.Http', VENDOR_PATH, EXT);
    $config = \think\Config::get('jPush');
    $client = new \JPush\Client($config['appKey'], $config['masterSecret']);//实例化对象，读取配置文件中的
    //$platform = array('ios', 'android');

    $result = $client->push()
    ->setPlatform('all')
    ->addRegistrationId($regid)//绑定的id   1507bfd3f7c76f87290
    //->addAllAudience()  如果audience为all的话,则不能指定$regid 或者tag
    ->setNotificationAlert($message)//简单地给所有平台推送相同的 alert 消息 */
    ->iosNotification($message, array(
         'sound' => 'sound.caf',
         'badge' => '+1',
         'content-available' => true,
         'mutable-content' => true,
         'category' => '消息推送',

     ))
     ->message('message content', array(
         'title' => 'hello jpush',
         // 'content_type' => 'text',
         'extras' => array(
             'key' => 'value',
             'jiguang'
         ),
     ))
    ->send();
    if ($result['http_code'] === 200) return true;
    return false;
}

function json_post($url, $data = NULL)
    {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(!$data){
            return 'data is null';
        }
        if(is_array($data))
        {
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER,array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length:' . strlen($data),
                'Cache-Control: no-cache',
                'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;

    }

function post($url, $data) {

    //初使化init方法
    $ch = curl_init();

    //指定URL
    curl_setopt($ch, CURLOPT_URL, $url);

    //设定请求后返回结果
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //声明使用POST方式来进行发送
    curl_setopt($ch, CURLOPT_POST, 1);

    //发送什么数据呢
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);


    //忽略证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //忽略header头信息
    curl_setopt($ch, CURLOPT_HEADER, 0);

    //设置超时时间
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    //发送请求
    $output = curl_exec($ch);

    //关闭curl
    curl_close($ch);

    //返回数据
    return $output;
}
/**
 * [ParamsChecked 参数校验方法]
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  1.0.0
 * @datetime 2017-12-12T15:26:13+0800
 * @param    [array]                   $data   [原始数据]
 * @param    [array]                   $params [校验数据]
 * @return   [boolean|string]                  [成功true, 失败 错误信息]
 */
function ParamsChecked($data, $params)
{
    if(empty($params) || !is_array($data) || !is_array($params))
    {
        return '内部调用参数配置有误';
    }

    foreach ($params as $v)
    {
        if(empty($v['key_name']) || empty($v['error_msg']))
        {
            return '内部调用参数配置有误';
        }

        // 是否需要验证
        $is_checked = true;

        // 数据或字段存在则验证
        // 1 数据存在则验证
        // 2 字段存在则验证
        if(isset($v['is_checked']))
        {
            if($v['is_checked'] == 1)
            {
                if(empty($data[$v['key_name']]))
                {
                    $is_checked = false;
                }
            } else if($v['is_checked'] == 2)
            {
                if(!isset($data[$v['key_name']]))
                {
                    $is_checked = false;
                }
            }
        }

        // 是否需要验证
        if($is_checked === false)
        {
            continue;
        }

        // 数据类型,默认字符串类型
        $data_type = empty($v['data_type']) ? 'string' : $v['data_type'];

        // 验证规则，默认isset
        $checked_type = isset($v['checked_type']) ? $v['checked_type'] : 'isset';
        switch($checked_type)
        {
            // 是否存在
            case 'isset' :
                if(!isset($data[$v['key_name']]))
                {
                    return $v['error_msg'];
                }
                break;

            // 是否为空
            case 'empty' :
                if(empty($data[$v['key_name']]))
                {
                    return $v['error_msg'];
                }
                break;

            // 是否存在于验证数组中
            case 'in' :
                if(empty($v['checked_data']) || !is_array($v['checked_data']))
                {
                    return '内部调用参数配置有误';
                }
                if(!isset($data[$v['key_name']]) || !in_array($data[$v['key_name']], $v['checked_data']))
                {
                    return $v['error_msg'];
                }
                break;

            // 是否为数组
            case 'is_array' :
                if(!isset($data[$v['key_name']]) || !is_array($data[$v['key_name']]))
                {
                    return $v['error_msg'];
                }
                break;

            // 长度
            case 'length' :
                if(!isset($v['checked_data']))
                {
                    return '长度规则值未定义';
                }
                if(!is_string($v['checked_data']))
                {
                    return '内部调用参数配置有误';
                }
                if(!isset($data[$v['key_name']]))
                {
                    return $v['error_msg'];
                }
                if($data_type == 'array')
                {
                    $length = count($data[$v['key_name']]);
                } else {
                    $length = mb_strlen($data[$v['key_name']], 'utf-8');
                }
                $rule = explode(',', $v['checked_data']);
                if(count($rule) == 1)
                {
                    if($length > intval($rule[0]))
                    {
                        return $v['error_msg'];
                    }
                } else {
                    if($length < intval($rule[0]) || $length > intval($rule[1]))
                    {
                        return $v['error_msg'];
                    }
                }
                break;

            // 自定义函数
            case 'fun' :
                if(empty($v['checked_data']) || !function_exists($v['checked_data']))
                {
                    return '验证函数为空或函数未定义';
                }
                $fun = $v['checked_data'];
                if(!isset($data[$v['key_name']]) || !$fun($data[$v['key_name']]))
                {
                    return $v['error_msg'];
                }
                break;

            // 最小
            case 'min' :
                if(!isset($v['checked_data']))
                {
                    return '验证最小值未定义';
                }
                if(!isset($data[$v['key_name']]) || $data[$v['key_name']] < $v['checked_data'])
                {
                    return $v['error_msg'];
                }
                break;

            // 最大
            case 'max' :
                if(!isset($v['checked_data']))
                {
                    return '验证最大值未定义';
                }
                if(!isset($data[$v['key_name']]) || $data[$v['key_name']] > $v['checked_data'])
                {
                    return $v['error_msg'];
                }
                break;

            // 相等
            case 'eq' :
                if(!isset($v['checked_data']))
                {
                    return '验证相等未定义';
                }
                if(!isset($data[$v['key_name']]) || $data[$v['key_name']] == $v['checked_data'])
                {
                    return $v['error_msg'];
                }
                break;

            // 数据库唯一
            case 'unique' :
                if(!isset($v['checked_data']))
                {
                    return '验证唯一表参数未定义';
                }
                if(empty($data[$v['key_name']]))
                {
                    return $v['error_msg'];
                }
                $temp = db($v['checked_data'])->where([$v['key_name']=>$data[$v['key_name']]])->find();
                if(!empty($temp))
                {
                    return $v['error_msg'];
                }
                break;
        }
    }
    return true;
}
/**
 * [DataReturn 公共返回数据]
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-07T22:03:40+0800
 * @param    [string]       $msg  [提示信息]
 * @param    [int]          $code [状态码]
 * @param    [mixed]        $data [数据]
 * @return   [json]               [json数据]
 */
function DataReturn($msg = '', $code = 0, $data = '')
{
    // ajax的时候，success和error错误由当前方法接收
   /* if(IS_AJAX)
    {
        if(isset($msg['info']))
        {
            // success模式下code=0, error模式下code参数-1
            $result = array('msg'=>$msg['info'], 'code'=>-1, 'data'=>'');
        }
    }*/

    // 默认情况下，手动调用当前方法
    if(empty($result))
    {
        $result = array('msg'=>$msg, 'code'=>$code, 'data'=>$data);
    }

    // 错误情况下，防止提示信息为空
   /* if($result['code'] != 0 && empty($result['msg']))
    {
        $result['msg'] = '操作失败';
    }*/

    return $result;
}
/**
 * 返回本周开始和结束的时间戳
 *
 * @return array
 */
function week()
{
    $timestamp = time();
    //$beginWeek = mktime(0,0,0,date("m"),date("d")-date("w")+1,date("Y"));
    $beginWeek=strtotime(date('Y-m-d', strtotime("this week Monday", $timestamp)));

    #$endWeek = mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y"));

    $endWeek=   strtotime(date('Y-m-d', strtotime("this week Sunday", $timestamp))) + 24 * 3600 - 1 ;
    return [
        $beginWeek,
        $endWeek
    ];
}
function object2array(&$object) {

    $object =  json_decode( json_encode( $object),true);
    return  $object;
}

function delete_fxg(&$array) {
    while(list($k,$v) = each($array)) {
        if (is_string($v)) {
            $array[$k] = stripslashes($v);//去掉反斜杠字符
        }
        if (is_array($v))  {
            $array[$k] = delete_fxg($v);//调用本身，递归作用
        }
    }
    return $array;
}
/**
 * 返回错误json信息
 */
function ajax_return_adv_error($msg = '', $code = 1, $redirect = '', $alert = '', $close = false, $url = '', $data = '', $extend = [])
{
    return ajax_return_adv($msg, $alert, $close, $redirect, $url, $data, $code, $extend);
}
/**
 * 框架内部默认ajax返回
 * @param string $msg      提示信息
 * @param string $redirect 重定向类型 current|parent|''
 * @param string $alert    父层弹框信息
 * @param bool $close      是否关闭当前层
 * @param string $url      重定向地址
 * @param string $data     附加数据
 * @param int $code        错误码
 * @param array $extend    扩展数据
 */
function ajax_return_adv($msg = '操作成功', $redirect = 'parent', $alert = '', $close = false, $url = '', $data = '', $code = 0, $extend = [])
{
    $extend['opt'] = [
        'alert'    => $alert,
        'close'    => $close,
        'redirect' => $redirect,
        'url'      => $url,
    ];

    return ajax_return($data, $msg, $code, $extend);
}
/**
 * ajax数据返回，规范格式
 * @param array $data   返回的数据，默认空数组
 * @param string $msg   信息
 * @param int $code     错误码，0-未出现错误|其他出现错误
 * @param array $extend 扩展数据
 */
function ajax_return($data = [], $msg = "", $code = 0, $extend = [])
{
    $ret = ["code" => $code, "msg" => $msg, "data" => $data];
    $ret = array_merge($ret, $extend);

    return Response::create($ret, 'json');
}
/**
 * [MyC 读取站点配置信息]
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2016-12-29T17:17:25+0800
 * @param    [string]    $key           [索引名称]
 * @param    [mixed]     $default       [默认值]
 * @param    [boolean]   $mandatory     [是否强制校验值,默认false]
 * @return   [mixed]                    [配置信息值,没找到返回null]
 */
function MyC($key, $default = null, $mandatory = false)
{
    $data = cache(config('cx.cache_common_my_config_key'));
    if($mandatory === true)
    {
        return empty($data[$key]) ? $default : $data[$key];
    }
    return isset($data[$key]) ? $data[$key] : $default;
}
