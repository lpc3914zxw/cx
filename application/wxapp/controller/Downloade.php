<?php

namespace app\wxapp\controller;
use app\common\Common;
use app\wxapp\model\Collection;
use think\Config;
use app\wxapp\controller\Base;
use think\Db;
class Clause extends Base{

    /**
     * @param $filePath //下载文件的路径
     * @param int $readBuffer //分段下载 每次下载的字节数 默认1024bytes
     * @param array $allowExt //允许下载的文件类型
     * @return void
     */
    function downloadFile($filePath, $readBuffer = 1024, $allowExt = ['jpeg', 'jpg', 'peg', 'gif', 'zip', 'rar', 'txt','apk'])
    {
        //检测下载文件是否存在 并且可读
        if (!is_file($filePath) && !is_readable($filePath)) {
            return false;
        }
        //检测文件类型是否允许下载
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowExt)) {
            return false;
        }
        //设置头信息
        //声明浏览器输出的是字节流
        header('Content-Type: application/octet-stream');
        //声明浏览器返回大小是按字节进行计算
        header('Accept-Ranges:bytes');
        //告诉浏览器文件的总大小
        $fileSize = filesize($filePath);//坑 filesize 如果超过2G 低版本php会返回负数
        header('Content-Length:' . $fileSize); //注意是'Content-Length:' 非Accept-Length
        //声明下载文件的名称
        header('Content-Disposition:attachment;filename=' . basename($filePath));//声明作为附件处理和下载后文件的名称
        //获取文件内容
        $handle = fopen($filePath, 'rb');//二进制文件用‘rb’模式读取
        while (!feof($handle) ) { //循环到文件末尾 规定每次读取（向浏览器输出为$readBuffer设置的字节数）
            echo fread($handle, $readBuffer);
        }
        fclose($handle);//关闭文件句柄
        exit;
    
    }
}    
?>