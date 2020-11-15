<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\HonorSet;

/**
 * 荣誉值设置
 * Class Xcscourse
 * @package app\index\controller
 */
class LogMember extends Base
{
        /*
         *学分学习力等操作记录
         */
    public function index() {
        $logweb =new  \app\index\model\Logweb();
        if($this->request->isAjax()){
            $where = [];
            return $logweb->getList($where);
        }
        return $this->fetch('index');
    }
}
