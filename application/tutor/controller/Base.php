<?php
// +----------------------------------------------------------------------
// | 功能：基类，查询菜单、校验登录
// +----------------------------------------------------------------------
// | 作者: xiaomage
// +----------------------------------------------------------------------
// | 日期：2017-10-13
// +----------------------------------------------------------------------
namespace app\tutor\controller;
use app\tutor\model\Tutor;
use think\Controller;
use think\Cookie;
use think\Session;
use app\tutor\model\Menu;
use think\Config;
use think\Request;
class Base extends Controller {
	// 是否开启验证登录
	protected $is_login = true;
	// 用户信息
    protected $tutor_id = 0;
	protected $tutorinfo = [ ];
	protected $systeminfo = [];
	public function __construct() {
		parent::__construct ();
		// 验证登录
		$this->is_login && $this->check_login ();
		// 加载菜单
		$this->get_menu();
		$this->getSystemInfo();
	}

	public function _empty(){
	    $this->error('正在开发中');
    }

    /*
     *获取系统信息
     */
	public function getSystemInfo(){
	    $system_model = new \app\index\model\System();
	    $systeminfo = $system_model->find();
	    $this->systeminfo = $systeminfo;
	    Config::set('sysinfo',$systeminfo);
    }

	
	/**
	 * 验证登录
	 * 
	 * @author Steed
	 */
	protected function check_login() {
		// 获取session信息
        $tutorinfo = Session::get('tutorinfo');
		if (empty ($partner )) {
			// 如果不存在session则验证cookie
			//$this->check_cookie () || $this->redirect ( 'login/index' );
			// 验证成功重新获取session
            $tutorinfo = Session::get ( 'tutorinfo' );
		}
		// 检测数据完整性
		empty ($tutorinfo ) && $this->redirect ( 'login/index' );
		$this->tutorinfo = $tutorinfo;
		$this->tutor_id = $tutorinfo['uid'];
		$this->assign ( 'tutorinfo', $tutorinfo );
	}
	
	/*
	 * 验证cookie 记录session
	 * 
	 * @author Steed
	 * @return bool
	 */
	private function check_cookie() {
		$tutorinfo = Cookie::get('tutorinfo');
        $tutorinfo = json_decode($tutorinfo,true);
		// cookie不存在
		if (empty ( $tutorinfo )) {
			return false;
		}
		// 验证数据是否正常
        $tutor_model = new Tutor();
        $tutorInfo = $tutor_model->where (['name' => $tutorinfo ['name']])->find();
		if ($tutorInfo) {
			$new_pwd = splice_pwd ($tutorInfo['password'], $tutorInfo['salt'] );
			if ($new_pwd == $tutorInfo ['password']) {
				Session::set('tutorInfo', $tutorInfo );
				return true;
			}
		}
	}


    /*
     * 获取菜单
     * @author Steed
     */
    private function get_menu() {
        $menu_model = new Menu();
        $tutorInfo = Session::get('tutorInfo');
        $menu = $menu_model->getMenu();

        //处理数据
        $menu = recursion_data($menu);
        $this->assign('menu', $menu);
        //查询当前请求的菜单信息
        $controller = \think\Request::instance()->controller();
        $action = \think\Request::instance()->action();
        $map = [
            'controller' => $controller,
            'action' => $action
        ];
        $current_menu = $menu_model->findMenu($map);
        $this->assign('current_menu', $current_menu);
        $this->assign('controller', $controller);
        $this->assign('action', $action);
    }
}
