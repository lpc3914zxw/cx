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
class Login extends Controller {
	// 登录
	public function index() {
		$managerinfo = Cookie::get('memberinfo' );
		$userinfo = json_decode ( $managerinfo, true );
		$this->assign ( 'member', $userinfo );
		return $this->fetch ('login/login');
	}

    /**
     * 登陆
     * @param string $username 用户名
     * @param string $pwd  密码
     * @param string $verify  验证码
     */
	public function login($username = '', $pwd = '', $verify = '') {
		$this->request->isAjax() || $this->error ( '请求错误' );
		$pwd = trim($pwd);
		$mobile = trim($username);
		// 验证验证码
		check_verify($verify,1) || $this->error( '验证码错误');
		$data = [
		    'username' => $username,
            'password' => $pwd,
            'verify' => $verify
		];
		// 登录逻辑
		$meetinginfo = $this->login_oper($username,$pwd);
		$meetinginfo ['memberinfo'] = $pwd;
		Session::set ('memberinfo', $meetinginfo);
		Cookie::set ('memberinfo', $meetinginfo, 24 * 3600);
		$this->success('登录成功', Url::build('index/index'));
	}

    /*
     * 登陆逻辑
     * @param $username  用户名
     * @param $pwd  密码
     * @return array|false|\PDOStatement|string|Model
     */
	private function login_oper($username, $pwd) {
		$member_model = new Member();
		$member = $member_model->where(['username' => $username])->find ();
        if (empty ($member)) {
            $this->error ( '不存在该用户' );
        }
        // 生成密码
        $salt_pwd = splice_pwd($pwd, $member ['salt']);
        if ($salt_pwd != $member ['password']) {
            //$this->error ( '密码错误' );
        }
		return $member;
	}

	    /* 验证码
    * @author staitc7
    */
	 public function verify() {
        $config = [
            'expire' => 30, // 验证码过期时间（s）
            'fontSize' => 18, // 验证码字体大小(px)
            'useCurve' => false, // 是否画混淆曲线
            'useNoise' => true, // 是否添加杂点
            'imageH' => 38, // 验证码图片高度
            'imageW' => 160, // 验证码图片宽度
            'length'=>4
        ];
        $verify = new \think\captcha\Captcha($config);
        return $verify->entry(1);
    }
    /* 检测验证码
    * @param  integer $id 验证码ID
    * @return boolean     检测结果
    * @author 麦当苗儿 <zuojiazi@vip.qq.com>
    */
    function check_verify($code, $id = 1) {
        $verify = new \think\captcha\Captcha();
        return $verify->check($code, $id);
    }


    /**
     * 修改密码
     * @return mixed
     */
	public function updatepwd(){
	    return $this->fetch();
    }


	// 退出
	public function logout() {
		Cookie::set ( 'memberinfo', '' );
		Session::set ( 'memberinfo', '' );
		$this->success('退出成功');
	}
}
