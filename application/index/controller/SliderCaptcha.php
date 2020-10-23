<?php
namespace app\index\controller;
use app\index\model\Member;
use think\Controller;
use think\Cookie;
use think\Session;
use think\Url;
use think\Db;
use think\Build;
use think\Model;
use think\Loader;
class SliderCaptcha extends Controller {
	// 登录
	public function index() {
		return $this->fetch ('slidercaptcha/login');
	}

}
