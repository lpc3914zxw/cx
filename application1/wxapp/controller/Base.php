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
        parent::__construct ();
        $token = Request::instance()->header('token');
        
        
		if($token !=''){
		    
		    $this->getSystemInfo();
		    $stop_starttime = $this->systeminfo['stop_starttime'];
		    $stop_endtime = $this->systeminfo['stop_endtime'];
		    $stop_content = $this->systeminfo['stop_content'];
		    if (!empty($stop_starttime)) {
                 $stop_starttime = str_replace('：', ':', $stop_starttime);
             } else {
                 $stop_starttime = '00:00';
             }
             $start = strtotime(date('Y-m-d').' '.$stop_starttime);
             $time = time();
             if (!empty($stop_endtime)) {
                 $stop_endtime = str_replace('：', ':', $stop_endtime);
             } else {
                 $stop_endtime = '00:00';
             }
             $end = strtotime(date('Y-m-d').' '.$stop_endtime);
            // var_dump($stop_starttime);
		   // var_dump($stop_endtime);
		    //var_dump($start);
		    //var_dump($time);
		    //exit;
             //if ($time > $start) {
                 
               //  if ($start < $end || $time > $end) {
                //     echo returnjson(1110,$stop_content,'停服时间');exit;
                // }
             //}
             if ($time < $end && $start < $time) {
                 
                 echo returnjson(1110,$stop_content,'停服时间');exit;
             }
             
             ///eturn returnjson(1110,$stop_content,'停服时间');
		    
            //exit;
            
        }
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
