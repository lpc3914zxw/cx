<?php
namespace app\index\controller;
use think\Controller;
/**
 * Class Index
 * @author Steed
 * @package app\index\controller
 */
class Error extends Controller {

    public function __construct()
    {
        $this->error('正在开发中');
    }

}
