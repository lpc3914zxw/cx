<?php
namespace app\tutor\controller;
use app\tutor\model\Tutor;
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
		$tutorinfo = Cookie::get('tutorinfo' );
        $tutorinfo = json_decode ( $tutorinfo, true );
		$this->assign ( 'tutorinfo', $tutorinfo );
		return $this->fetch ('login/login');
	}

    /*
     * 登陆
     * @param string $name 用户名
     * @param string $pwd  密码
     * @param string $verify  验证码
     */
	public function login($name = '', $pwd = '', $verify = '') {
		$this->request->isAjax() || $this->error ( '请求错误' );
		$pwd = trim($pwd);
        $name = trim($name);
		// 验证验证码
		check_verify($verify,1) || $this->error( '验证码错误');
		$data = [
		    'name' => $name,
            'password' => $pwd,
            'verify' => $verify
		];
		// 登录逻辑
        $tutorinfo = $this->login_oper($name,$pwd);
        $tutorinfo ['passowrd'] = $pwd;
		Session::set ('tutorinfo', $tutorinfo);
		Cookie::set ('tutorinfo', $tutorinfo, 24 * 3600);
		$this->success('登录成功', Url::build('index/index'));
	}

    /*
     * 登陆逻辑
     * @param $username  用户名
     * @param $pwd  密码
     * @return array|false|\PDOStatement|string|Model
     */
	private function login_oper($name, $pwd) {
		$tutor_model = new Tutor();
		$tutorInfo = $tutor_model->where(['name' => $name])->find ();
        if (empty ($tutorInfo)) {
            $this->error ( '不存在该用户' );
        }
        if($tutorInfo['status'] == 1) {
            $this->error ( '正在审核中...' );
        }else if($tutorInfo['status'] == 3) {
            $this->error ( '您的申请已被驳回...' );
        }else if($tutorInfo['status'] == 4) {
            $this->error ( '该账号存在违规行为，请联系管理员...' );
        }
        // 生成密码
        $salt_pwd = splice_pwd($pwd, $tutorInfo ['salt']);
        if ($salt_pwd != $tutorInfo ['password']) {
            $this->error ( '密码错误' );
        }
		return $tutorInfo;
	}

	    /* 验证码
    * @author staitc7
    */
	 public function verify() {
        $config = [
            'expire' => 30, // 验证码过期时间（s）
            'fontSize' => 18, // 验证码字体大小(px)
            'useCurve' => true, // 是否画混淆曲线
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
		Cookie::set ( 'tutorinfo', '' );
		Session::set ( 'tutorinfo', '' );
		$this->success('退出成功');
	}
}
