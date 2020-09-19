<?php
// +----------------------------------------------------------------------
// | 功能：基类

// +----------------------------------------------------------------------
namespace app\wxapp\controller;
use app\wxapp\model\BrowseRecords;
use think\Controller;
use app\wxapp\model\User;
use think\Request;
use think\helper\Time;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Cache-Control,token");
class Base extends Controller {

	protected $systeminfo = [];
	protected $userInfo = [];
	protected $uid = 0 ;
	protected $num = 8;
	protected $token = '';

	public function __construct() {
	    //echo json_encode(['code' => 1110, 'data' => '改时间是停服时间', 'msg' => '停服时间']);exit;
        parent::__construct ();
        $token = Request::instance()->header('token');

        if($token) {
            $this->token = $token;
            $this->getUserInfo($token);
            $this->browseLog($this->uid);
        }

		$this->getSystemInfo();
	}

	/*
	 * 记录浏览
	 */
	public function browseLog($uid = 0) {
	    $browseRecords_model = new BrowseRecords();
        list($start, $end) = Time::today();
        if(!$browseRecords_model->where(['addtime'=>['between',[$start,$end]],'uid'=>$uid])->find()) {
            $data = ['uid'=>$uid,'ip'=>Request::instance()->ip(),'addtime'=>time()];
            $browseRecords_model->insert($data);
        }
    }

    /*
     *获取系统信息
     */
	public function getSystemInfo(){
	    $system_model = new \app\wxapp\model\System();
	    $systeminfo = $system_model->find();
	    $this->systeminfo = $systeminfo;
    }


    /*
     * 获取用户信息
     */
    public function getUserInfo($token = ' ') {
        $user_model = new User();
        $userInfo = $user_model->where('token',$token)->find();
        if(empty($userInfo)) {
            $this->uid = 0;
            $this->userInfo = [];
        }else{
            $this->userInfo = $userInfo;
            $this->uid = $userInfo['id'];
        }
    }
}
