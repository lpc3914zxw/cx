<?php
// +----------------------------------------------------------------------
// | 功能：基类，查询菜单、校验登录
// +----------------------------------------------------------------------
// | 作者: xiaomage
// +----------------------------------------------------------------------
// | 日期：2017-10-13
// +----------------------------------------------------------------------
namespace app\index\controller;
use think\Controller;
use think\Cookie;
use think\Session;
use app\index\model\Member;
use app\index\model\Menu;
use think\Config;
use think\Request;
use app\index\controller\Base;
class AdminBase extends Base {
	// 是否开启验证登录
	protected $is_login = true;
	// 用户信息
	protected $partner = [ ];
	protected $systeminfo = [];
	public function __construct() {
		parent::__construct ();
		// 检测是否有权限
        $this->checkPower();
	}

    // 检测是否有权限
	public function checkPower(){
        $auth = new \think\Auth();
        $request = Request::instance();
        $m = $request->module();
        $c = $request->controller();
        $a = $request->action();
        $rule_name = $m.'/'.$c.'/'.$a;
        $result = $auth->check($rule_name,$this->partner['uid']);
        if(!$result){
            $this->error('您没有权限访问');
        }
    }
}
