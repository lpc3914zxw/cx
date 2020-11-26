<?php
// +----------------------------------------------------------------------
// | 功能：基类，查询菜单、校验登录
// +----------------------------------------------------------------------
// | 作者: xiaomage
// +----------------------------------------------------------------------
// | 日期：2017-10-13
// +----------------------------------------------------------------------
namespace app\index\controller;
use app\index\model\AuthGroupAccess;
use think\Controller;
use think\Cookie;
use think\Session;
use app\index\model\Member;
use app\index\model\Menu;
use think\Config;
use think\Request;
class Base extends Controller {
	// 是否开启验证登录
	protected $is_login = true;
	// 用户信息

	protected $partner = [ ];
	protected $systeminfo = [];
    protected $data_post;

    // 输入参数 get
    protected $data_get;

    // 输入参数 request
    protected $data_request;
	public function __construct() {
		parent::__construct ();
		// 验证登录
		$this->is_login && $this->check_login ();
		// 加载菜单
        $this->data_post = input('post.');
        $this->data_get = input('get.');
        $this->data_request = input();
		$this->get_menu();
		$this->getSystemInfo();
		//var_dump($this->is_login);exit;
	}

	public function _empty(){
	    return $this->fetch('/index/empty_page');
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
        $partner = Session::get('memberinfo');
      	//$partner = (array)$partner;

		if (empty ( $partner )) {
			// 如果不存在session则验证cookie
			$this->check_cookie () || $this->redirect ( '/index/index/empty_page' );
			// 验证成功重新获取session
            $partner = Session::get ( 'memberinfo' );
		}
		// 检测数据完整性
		empty ( $partner ) && $this->redirect ( '/index/index/empty_page' );
		$member_model = new Member();
      	$member = $member_model->where('username',$partner['username'])->find();

        if ($partner['password']!= $member ['password']||$member['error_num']>=10) {
           $this->redirect ( '/index/index/empty_page' );
        }

		$this->partner = $partner;
		$this->assign ( 'member', $partner );
	}

	/**
	 * 验证cookie 记录session
	 *
	 * @author Steed
	 * @return bool
	 */
	private function check_cookie() {
		$memberinfo = Cookie::get('memberinfo');
		$memberinfo = json_decode($memberinfo,true);
		// cookie不存在
		if (empty ( $memberinfo )) {
			return false;
		}
		// 验证数据是否正常
		$member_model = new Member ();
		$userinfo = $member_model->where (['username' => $memberinfo ['username']])->find();
		if ($userinfo) {
			$new_pwd = splice_pwd ($memberinfo ['password'], $userinfo ['salt'] );
			if ($new_pwd == $userinfo ['password']) {
				Session::set('memberinfo', $memberinfo );
				return true;
			}
		}
	}


    /**
     * 获取菜单
     * @author Steed
     */
    private function get_menu() {
        $menu_model = new Menu();
        $member = Session::get('memberinfo');
        // 身份
        $authGroupAccess = new AuthGroupAccess();
        $group_id = $authGroupAccess->where('uid',$this->partner['uid'])->value('group_id');
        $menu = $menu_model->getMenu($group_id);
        /*echo $menu_model->getLastSql();exit;
        var_dump($menu);exit;*/
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
