<?php
// +----------------------------------------------------------------------
// | 功能：会员
// +----------------------------------------------------------------------
// | 作者: han
// +----------------------------------------------------------------------
// | 日期：2018-04-26
// +----------------------------------------------------------------------
namespace app\index\controller;
use app\index\controller\AdminBase;
use app\index\model\DedicationLog;
use app\index\model\HonorLog;
use app\index\model\LearningPowerLog;
use app\index\model\PulsLearnPowerLog;
use app\index\model\Faceorder;
use app\index\model\Cardorder;

use app\index\model\Course;
use app\index\model\CreditSource;
use app\service\BaseService;
use app\service\CreditSoureService;
use app\service\DedicationLogService;
use app\service\HonorLogService;
use app\service\LearnPowerLogService;
use app\service\LogMemberService;
use app\service\MemberService;
use app\service\RecommendService;
use app\service\UserOverlogService;
use app\service\UserService;
use app\wxapp\model\LearnPowerLog;
use think\Db;
use think\Session;
use think\Loader;
use think\Config;
use app\index\model\Levels;
use app\index\model\Orders;
class User extends AdminBase {

    /**
     * 用户列表
     */
    public function index(){
        $user_model = new \app\index\model\User();
        $level_model = new Levels();
        $where = [];
        $levs = $level_model->select();
        $this->assign('levels',$levs);
        $params = input('param.');
        $startLevel_model = new \app\index\model\StartLevel();
        $where = [];
        $stleves =  $startLevel_model->select();
        $this->assign('stleves',$stleves);
        //课程列表
        $courselist = Db::name('course')->where(['is_delete'=>0])->field('id,name')->order('sort')->select();
        $this->assign('courselist',$courselist);
        if($this->request->isAjax()){
            $where['u'] = [];
            if(!empty($params['is_auth'])){
                $where['u']['u.is_auth'] = $params['is_auth'];
            }
            if(!empty($params['level'])){
                $where['u']['u.level'] = $params['level'];
            }
            if(!empty($params['start_level'])){
                $where['u']['u.start_level'] = $params['start_level'];
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['u']['u.regetime'] = array('between',$time1.','.$time2);
            }
            $where['s'] = [];
            if(!empty($params['name'])){
                $where['s']['u.id'] = $params['name'];
                $where['s']['u.name'] = $params['name'];
                $where['s']['u.tel'] = $params['name'];
            }else if(!empty($params['pname'])){
            	if($this->is_mobile($params['pname'])){
                  	$pname = Db::name('user')->where('tel',$params['pname'])->find();
                  	if($pname){
                    	$where['u']['u.pid'] = $pname['id'];
                    }

                }else{
                	$where['u']['u.pid'] = $params['pname'];
                }
            }

            $list = $user_model->get_list($where);
            return $list;
        }
        return $this->fetch();
    }
  function is_mobile( $text ) {
    $search = '/^0?1[3|4|5|6|7|8|9][0-9]\d{8}$/';
    if ( preg_match( $search, $text ) ) {
        return ( true );
    } else {
        return ( false );
    }
}

    /*
     * 编辑等级
     */
    public function editLevel($id = 0,$level =0) {
        $partner = Session::get('memberinfo');
       if(false !== UserService::userUpdateOneById('level',$id,$level)){
           $original_level=UserService::userByLevel($id);
           LogMemberService::MemberLogAdd($id,$original_level,$level,0,$partner['uid'],5);
           $this->success('更改成功');
        }else{
           $this->error('更改失败');
       }

    }

    /*
     * 编辑等级
     */
    public function editStartLevel($id = 0,$level =0) {
        $partner = Session::get('memberinfo');
       if(false !== UserService::userUpdateOneById('start_level',$id,$level)){
           $original_level=UserService::userByLevel($id);
           LogMemberService::MemberLogAdd($id,$original_level,$level,0,$partner['uid'],6);
           $this->success('更改成功');
       }else{
           $this->error('更改失败');
       }
    }

    /*
     * 赠送课程
     */
    public function give($id = 0,$course_id ='',$pay_passwordr = '') {
        if(empty($course_id)){
            $this->error('参数缺失');
        }
        //$pay_passwordr = $params['pay_password'];
         $partner = Session::get('memberinfo');
          if(empty($pay_passwordr)){
          	$this->error('密码不能为空');
          }
           $num = 0;
          	$member = Db::name('member')->where('uid',$partner['uid'])->find();
          $new_pwd = splice_pwd ($pay_passwordr, $member ['pay_salt'] );
          //echo $new_pwd;exit;
          	if($member['pay_password']!=$new_pwd){
              $num = (int)$member['error_num']+1;

              	$res = Db::name('member')->where('uid',$partner['uid'])->update(['error_num'=>$num]);
             if($num>=3){
              	$this->redirect ( '/index/index/empty_page');
              }
            	$this->error('密码错误');

            }else{
            	Db::name('member')->where('uid',$partner['uid'])->update(['error_num'=>0]);
            }
        $ishasorder = Db::name('order')->where('uid',$id)->where('course_id',$course_id)->where('status','neq',0)->find();
        if($ishasorder){
            $this->error('该用户已有此课程');
        }
        $course_model = new Course();
        $courseInfo = $course_model->field('imgurl,name,advanced_id,stock')->where('id',$course_id)->find();
        if(!empty($courseInfo['stock'])){
             $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $countwhere['status'] = array('neq',0);
            $countwhere['paytime'] = array('between',$beginToday.','.$endToday);
            $kcount = Db::name('order')->where($countwhere)->count();
            if($courseInfo['stock']<=$kcount){
                $this->error('今天的课程已兑完');
            }
        }
        Db::startTrans();

        $common = new \app\common\Common();

        if(false === $common->userChangeLevel_admin($id,$course_id,3,$course_id)){
            Db::rollback();
            $this->error('赠送失败');
        }
        Db::commit();
        $this->success('赠送成功');
    }

    /*
     * 增加减少学分学习力荣誉贡献
     * 充值学分/学习力/荣誉值/贡献值
     */
    public function editOption($uid = 0) {
        if($this->request->isPost()) {
            $params = $this->request->param();
            $type = $params['type'];
            $changeType = $params['changeType'];
            $number = $params['number'];
            $pay_passwordr = $params['pay_password'];
            $note = $params['note'];
            $uid = $params['uid'];

          $partner = Session::get('memberinfo');
          if(empty($pay_passwordr)){
          	$this->error('充值密码不能为空');
          }
            $member=MemberService::byMemberOne('uid',$partner['uid']);
            $new_pwd = splice_pwd ($pay_passwordr, $member ['pay_salt'] );
          /*	if($member['pay_password']!=$new_pwd){
              $num = (int)$member['error_num']+1;
              	MemberService::updateMemberPasswordNum($partner['uid'],$num);
             if($num>=3){
              	$this->redirect ( '/index/index/empty_page');
              }
            	$this->error('充值密码错误');

            }else{
            	MemberService::updateMemberPasswordNum($partner['uid'],0);
            }*/
            Db::startTrans();
          	//score学分 dedication贡献 learning_power学习力 honer_value荣誉值


            $userinfo = UserService::userEveryValue($uid);
            if($type == 'score') {
                $data = ['uid'=>$uid,'status'=>1,'addtime'=>time()];
                if($changeType == 1) {
                    if(false === UserService::userSetincValue($uid,'score',$number)) {
                        $this->error('充值失败');
                    }
                    $data['type'] = 7;
                    $data['score'] = $number;
                    $data['note'] = $note;
                }else{
                    if(floatval($userinfo['score']) < floatval($number)) {
                        $this->error('当前学分没有这么多');
                    }
                    if(false ===  UserService::userSetdecValue($uid,'score',$number)) {
                        $this->error('充值失败');
                    }
                    $data['type'] = 8;
                    $data['score'] = "-".$number;
                    $data['note'] = $note;
                }
                if(!CreditSoureService::addCreditSource($data)) {
                    Db::rollback();
                    $this->error('充值失败');
                }else{
                    LogMemberService::MemberLogAdd($uid,$userinfo['score'],$number,$changeType,$partner['uid'],1);
                }
            }else if($type == 'dedication') {
                $data = ['uid'=>$uid,'addtime'=>time()];
                if($changeType == 1) {
                    if(false === UserService::userSetincValue($uid,'dedication_value',$number))
                    {
                        $this->error('充值失败');
                    }
                    $data['type'] = 18;
                    $data['value'] = $number;
                    $data['content'] = $note;
                }else{
                    if(floatval($userinfo['dedication_value']) < floatval($number)) {
                        $this->error('当前贡献值没有这么多');
                    }
                    if(false === UserService::userSetdecValue($uid,'dedication_value',$number)) {
                        $this->error('充值失败');
                    }
                    $data['type'] = 19;
                    $data['value'] = "-".$number;
                    $data['content'] = $note;
                }
                if(!DedicationLogService::addDedicationLog($data)) {
                    Db::rollback();
                    $this->error('充值失败');
                }else{
                    LogMemberService::MemberLogAdd($uid,$userinfo['dedication_value'],$number,$changeType,$partner['uid'],2);
                }
            }else if($type == 'learning_power') {
                $data = ['uid'=>$uid,'status'=>1,'addtime'=>time()];
                if($changeType == 1) {
                    if(false === UserService::userSetincValue($uid,'learning_power',$number)) {
                        $this->error('充值失败');
                    }
                    $data['type'] = 4;
                    $data['value'] = $number;
                    $data['content'] = $note;
                }else{
                    if(floatval($userinfo['learning_power']) < floatval($number)) {
                        $this->error('当前学习力没有这么多');
                    }
                    if(false === UserService::userSetdecValue($uid,'learning_power',$number)) {
                        $this->error('充值失败');
                    }
                    $data['type'] = 5;
                    $data['value'] = "-".$number;
                    $data['content'] = $note;
                }
                if(!LearnPowerLogService::addLearnPowerLog($data)) {
                    Db::rollback();
                    $this->error('充值失败');
                }else{
                    LogMemberService::MemberLogAdd($uid,$userinfo['learning_power'],$number,$changeType,$partner['uid'],4);
                }
            }else if($type == 'honor') {
                $data = ['uid'=>$uid,'addtime'=>time()];
                if($changeType == 1) {
                    if(false === UserService::userSetincValue($uid,'honor_value',$number)) {
                        $this->error('充值失败');
                    }
                    $data['type'] = 7;
                    $data['value'] = $number;
                    $data['content'] = $note;
                }else{
                    if(floatval($userinfo['honor_value']) < floatval($number)) {
                        $this->error('当前荣誉值没有这么多');
                    }
                    if(false === UserService::userSetdecValue($uid,'honor_value',$number)) {
                        $this->error('充值失败');
                    }
                    $data['type'] = 8;
                    $data['value'] = "-".$number;
                    $data['content'] = $note;
                }
                if(!HonorLogService::addHonorLog($data)) {
                    Db::rollback();
                    $this->error('充值失败');
                }else{
                    LogMemberService::MemberLogAdd($uid,$userinfo['honor_value'],$number,$changeType,$partner['uid'],3);
                }
            }
            Db::commit();
            $this->success('充值成功');
        }
        $userInfo=UserService::userFieldsOne('score,learning_power,honor_value,dedication_value',$uid);
        $this->assign('userinfo',$userInfo);
        $this->assign('uid',$uid);
        return $this->fetch('editoption');
    }

    /*
     * 导出用户
     */
    public function export(){
        $user_model = new \app\index\model\User();
        $where['u'] = [];
        if(!empty($params['is_auth'])){
            $where['u']['u.is_auth'] = $params['is_auth'];
        }
        if(!empty($params['level'])){
            $where['u']['u.level'] = $params['level'];
        }
        if(!empty($params['start_level'])){
            $where['u']['u.start_level'] = $params['start_level'];
        }
        if(isset($params['scoretime'])&&$params['scoretime']!=''){
            $time = explode(' - ',$params['scoretime']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['u']['u.regetime'] = array('between',$time1.','.$time2);
        }
        $where['s'] = [];
        if(!empty($params['name'])){
            $where['s']['u.id'] = $params['name'];
            $where['s']['u.name'] = $params['name'];
            $where['s']['u.tel'] = $params['name'];
        }
        $list = $user_model->get_list($where);
        $data = $list['rows'];
        Loader::import('PHPExcel.Classes.PHPExcel',VENDOR_PATH);
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objPHPExcel->getActiveSheet()->setCellValue('A1','名称');
        $objPHPExcel->getActiveSheet()->setCellValue('B1','学号');
        $objPHPExcel->getActiveSheet()->setCellValue('C1','手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('D1','等级');
        $objPHPExcel->getActiveSheet()->setCellValue('E1','星际等级');
        $objPHPExcel->getActiveSheet()->setCellValue('F1','贡献值');
        $objPHPExcel->getActiveSheet()->setCellValue('G1','学分');
        $objPHPExcel->getActiveSheet()->setCellValue('H1','学习力');
        $objPHPExcel->getActiveSheet()->setCellValue('I1','荣誉值');
        $objPHPExcel->getActiveSheet()->setCellValue('J1','是否认证');
        $objPHPExcel->getActiveSheet()->setCellValue('K1','注册时间');
        $objPHPExcel->getActiveSheet()->setCellValue('L1','上级id');
        foreach ($data as $k=>$val){
            $is_auth = $val['is_auth'] == 1 ? '是' : '否';
            $addtime = date('Y-m-d H:i:s',$val['regetime']);
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->getStyle('B'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->getStyle('C'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,$val['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,$val['student_no']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,$val['tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,$val['lname']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,$val['sname']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,$val['dedication_value']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,$val['score']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.$i,$val['learning_power']);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.$i,$val['honor_value']);
            $objPHPExcel->getActiveSheet()->setCellValue('J'.$i,$is_auth);
            $objPHPExcel->getActiveSheet()->setCellValue('K'.$i,$addtime);
            $objPHPExcel->getActiveSheet()->setCellValue('L'.$i,$val['pid']);
        }
        // 1.保存至本地Excel表格
        $objWriter->save('用户数据.xls');
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="用户数据.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    public function authPass($id = 0,$realname,$identityid) {
        $user_model = new \app\wxapp\model\User();
        $userinfo_ = $user_model->field('is_auth,level')->where('identityid', $identityid)->find();
        if($userinfo_['is_auth']==1){
            $this->error('身份证已被占用');
        }
        $userinfo = $user_model->field('is_auth,level')->where('id', $id)->find();
        if($userinfo['is_auth']==1){
            $this->error('您已实名认证过');
        }
        if(empty($realname)||empty($identityid)){
            $this->error('信息不完整');
        }
        Db::startTrans();
        //var_dump($id);var_dump($realname);var_dump($identityid);exit;
        $user_model->where('id',$id)->update(['is_auth'=>1,'realname'=>$realname,'identityid'=>$identityid]);
        $common = new \app\common\Common();

        $userInfo = $user_model->field('pid')->where('id',$id)->find();
        if(!empty($userInfo['pid'])){
            if(false === $user_model->where('id',$userInfo['pid'])->setInc('invate_num')) {
                Db::rollback();
                $this->error('审核失败');
            }
        }

        if(false === $common->userChangeLevel($id)){
            Db::rollback();
            $this->error('审核失败');
        }
        Db::commit();
        $this->success('审核成功');
    }
    public function authPass_($id = 0) {
        $user_model = new \app\wxapp\model\User();
        $userinfo = $user_model->field('is_auth,level,realname,identityid')->where('id', $id)->find();



       // $userInfo = $user_model->field('pid')->where('id',$id)->find();
        $this->assign('userinfo',$userinfo);
        $this->assign('uid',$id);

        return $this->fetch();
    }
    /*
     * 贡献值
     * @param int $uid
     */
    public function dedica($uid = 0) {
        if($this->request->isAjax()){
            $params = input('param.');
            $where = [];
            if(!empty($uid)){
                $where = ['d.uid'=>$uid];
            }
            if(!empty($params['type'])){
                $where['d.type'] = $params['type'];
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['d.addtime'] = array('between',$time1.','.$time2);
            }

            if(!empty($uid)){
                if(!empty($params['name'])){
                    $where['u.tel|u.name'] = ['like','%'.$params['name'].'%'];
                }
            }else{
                if(!empty($params['name'])){
                    $where['d.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
                }
            }
            $dedica = new DedicationLog();
            return $dedica->get_list($where);
        }
        $this->assign('uid',$uid);
        return $this->fetch();
    }

    /*
     * 导出贡献值明细
     */
    public function exportDedica() {
        $params = input('param.');
        $where = [];
        if($params['uid'] != '' &&  $params['uid'] != 0) {
            $where= ['d.uid'=>$params['uid']];
        }
        if(!empty($params['type'])){
            $where['d.type'] = $params['type'];
        }
        if(isset($params['scoretime'])&&$params['scoretime']!=''){
            $time = explode(' - ',$params['scoretime']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['d.addtime'] = array('between',$time1.','.$time2);
        }
        if(!empty($params['name'])){
            $where['d.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
        }
        $dedica = new DedicationLog();
        $list = $dedica->get_list($where);
        $data = $list['rows'];
        Loader::import('PHPExcel.Classes.PHPExcel',VENDOR_PATH);
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(60);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);

        $objPHPExcel->getActiveSheet()->setCellValue('A1','ID');
        $objPHPExcel->getActiveSheet()->setCellValue('B1','昵称');
        $objPHPExcel->getActiveSheet()->setCellValue('C1','手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('D1','获得贡献值');
        $objPHPExcel->getActiveSheet()->setCellValue('E1','来源说明');
        $objPHPExcel->getActiveSheet()->setCellValue('F1','来源类别');
        $objPHPExcel->getActiveSheet()->setCellValue('G1','时间');
        foreach ($data as $k=>$val){
            $type = self::dedicaSource($val['type']);
            $addtime = date('Y-m-d H:i:s',$val['addtime']);
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->getStyle('A'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->getStyle('C'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,$val['id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,$val['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,$val['tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,$val['value']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,$val['content']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,$type);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,$addtime);
        }
        // 1.保存至本地Excel表格
        $objWriter->save('贡献值明细数据.xls');
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="贡献值明细数据.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    /*
     * 贡献值来源
     */
    public static function dedicaSource($type = 1) {
        $arr = ['1'=>'每日才学','2'=>'阅读文章','3'=>'文章点赞','4'=>'文章分享','5'=>'反馈意见','6'=>'邀请好友',
               '7'=>'购买消费','8'=>'异常类型8','9'=>'大社群新增一人','10'=>'小社群新增一人','11'=>'大社群新增一个学习力',
               '12'=>'小社群新增一个学习力','13'=>'异常类型13','14'=>'课程阅读','15'=>'课程点赞','16'=>'课程分享'
        ];
        return $arr[$type];
    }

    /*
     * 学分明细
     * @param int $uid
     */
    public function score($uid = 0) {
        if($this->request->isAjax()){
            $params = input('param.');
            $where = [];
            if(!empty($uid)){
                $where['c.uid'] = $uid;
            }
            if(!empty($params['type'])){
                $where['c.type'] = $params['type'];
            }
            if(isset($params['status'])){
                if($params['status']!=''){
                    $where['c.status'] = $params['status'];
                }
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['c.addtime'] = array('between',$time1.','.$time2);
            }
            if(!empty($uid)){
                if(!empty($params['name'])){
                    $where['u.tel|u.name'] = ['like','%'.$params['name'].'%'];
                }
            }else{
                if(!empty($params['name'])){
                    $where['c.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
                }
            }
            $creditScore = new CreditSource();
            $is_export = 0;
            return $creditScore->getList($where,$is_export);
        }
        $this->assign('uid',$uid);
        return $this->fetch();
    }

    /*
     * 导出学分明细
     */
    public function exportScore() {
        $params = input('param.');
        $where = [];
        if($params['uid'] != '' &&  $params['uid'] != 0) {
            $where['c.uid'] = $params['uid'];
        }
        if(!empty($params['type'])){
            $where['c.type'] = $params['type'];
        }
        if(isset($params['status'])){
            if($params['status']!=''){
                $where['c.status'] = $params['status'];
            }
        }
        if(isset($params['scoretime'])&&$params['scoretime']!=''){
            $time = explode(' - ',$params['scoretime']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['c.addtime'] = array('between',$time1.','.$time2);
        }
        if(!empty($uid)){
            if(!empty($params['name'])){
                $where['u.tel|u.name'] = ['like','%'.$params['name'].'%'];
            }
        }else{
            if(!empty($params['name'])){
                $where['c.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
            }
        }
        $creditScore = new CreditSource();
        $is_export = 1;
        $list = $creditScore->getList($where,$is_export);
        $data = $list['rows'];
        Loader::import('PHPExcel.Classes.PHPExcel',VENDOR_PATH);
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(60);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);

        $objPHPExcel->getActiveSheet()->setCellValue('A1','ID');
        $objPHPExcel->getActiveSheet()->setCellValue('B1','昵称');
        $objPHPExcel->getActiveSheet()->setCellValue('C1','手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('D1','学分值');
        $objPHPExcel->getActiveSheet()->setCellValue('E1','来源说明');
        $objPHPExcel->getActiveSheet()->setCellValue('F1','来源类别');
        $objPHPExcel->getActiveSheet()->setCellValue('G1','状态');
        $objPHPExcel->getActiveSheet()->setCellValue('H1','时间');
        foreach ($data as $k=>$val){
            $type = self::scoreSource($val['type']);
            $addtime = date('Y-m-d H:i:s',$val['addtime']);
            $status = $val['status'] == 0 ? '未完成' : '已完成';
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->getStyle('C'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,$val['id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,$val['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,$val['tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,$val['score']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,$val['note']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,$type);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,$status);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.$i,$addtime);
        }
        // 1.保存至本地Excel表格
        $objWriter->save('学分明细数据.xls');
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="学分明细数据.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    /*
     * 学分来源
     */
    public static function scoreSource($type = 1) {
        $arr = [
            '1'=>'学习收入(课堂作业)','2'=>'导师专栏文章赞赏获得/扣除','3'=>'兑入','4'=>'兑出','5'=>'课程购买','6'=>'算力银行奖励'
        ];
        return $arr[$type];
    }
    /*
     * 实名支付明细
     * @param int $uid
     */
    public function faceorder($uid = 0) {
        if($this->request->isAjax()){
            $params = input('param.');
            $where['f.status'] = 1;
            if(!empty($uid)){
                $where['c.uid'] = $uid;
            }
            if(!empty($params['paytype'])){
                $where['f.paytype'] = $params['paytype'];
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['f.paytime'] = array('between',$time1.','.$time2);
            }
            if(!empty($params['name'])){
                $where['f.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
            }
            $faceorder = new Faceorder();
            $is_export = 0;
            return $faceorder->getList($where,$is_export);
        }
        $this->assign('uid',$uid);
        return $this->fetch();
    }

    /*
     * 导出实名支付明细
     */
    public function exportFaceorder() {
        $params = input('param.');
        $where['f.status'] = 1;
        if($params['uid'] != '' &&  $params['uid'] != 0) {
            $where['f.uid'] = $params['uid'];
        }
        if(!empty($params['paytype'])){
            $where['f.paytype'] = $params['paytype'];
        }
        if(isset($params['scoretime'])&&$params['scoretime']!=''){
            $time = explode(' - ',$params['scoretime']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['f.paytime'] = array('between',$time1.','.$time2);
        }
        if(!empty($params['name'])){
            $where['f.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
        }
        $faceorder = new Faceorder();
        $is_export = 1;
        $list = $faceorder->getList($where,$is_export);
        $data = $list['rows'];
        Loader::import('PHPExcel.Classes.PHPExcel',VENDOR_PATH);
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objPHPExcel->getActiveSheet()->setCellValue('A1','订单号');
        $objPHPExcel->getActiveSheet()->setCellValue('B1','昵称');
        $objPHPExcel->getActiveSheet()->setCellValue('C1','手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('D1','金额(元)');
        $objPHPExcel->getActiveSheet()->setCellValue('E1','支付方式');
        $objPHPExcel->getActiveSheet()->setCellValue('F1','状态');
        $objPHPExcel->getActiveSheet()->setCellValue('G1','说明');
        $objPHPExcel->getActiveSheet()->setCellValue('H1','支付时间');
        foreach ($data as $k=>$val){
            $paytype = $val['paytype'] == 1 ? '支付宝' : '微信';
            $paytime = date('Y-m-d H:i:s',$val['paytime']);
            $status = $val['status'] == 1 ? '完成' : '未完成';
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->getStyle('A'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->getStyle('C'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,$val['out_trade_no']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,$val['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,$val['tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,$val['total_amount']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,$paytype);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,$status);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,$val['subject']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.$i,$paytime);
        }
        // 1.保存至本地Excel表格
        $objWriter->save('实名支付明细数据.xls');
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="实名支付明细数据.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }
     /*
     * 会员卡购买明细
     * @param int $uid
     */
    public function cardorder($uid = 0) {
        if($this->request->isAjax()){
            $params = input('param.');
            $where['f.status'] = 1;
            if(!empty($uid)){
                $where['c.uid'] = $uid;
            }
            if(!empty($params['paytype'])){
                $where['f.paytype'] = $params['paytype'];
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['f.paytime'] = array('between',$time1.','.$time2);
            }
            if(!empty($params['name'])){
                $where['f.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
            }
            $cardorder = new Cardorder();
            $is_export = 0;
            return $cardorder->getList($where,$is_export);
        }
        $this->assign('uid',$uid);
        return $this->fetch();
    }
    /*
     * 荣誉值明细
     * @param int $uid
     */
    public function honor($uid = 0) {
        if($this->request->isAjax()){
            $params = input('param.');
            $where = [];
            if(!empty($uid)){
                $where = ['h.uid'=>$uid];
            }
            if(!empty($params['type'])){
                $where['h.type'] = $params['type'];
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['h.addtime'] = array('between',$time1.','.$time2);
            }
            if(!empty($uid)){
                if(!empty($params['name'])){
                    $where['u.tel|u.name'] = ['like','%'.$params['name'].'%'];
                }
            }else{
                if(!empty($params['name'])){
                    $where['h.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
                }
            }
            $honorlog = new HonorLog();
            $is_export = 0;
            return $honorlog->get_list($where,$is_export);
        }
        $this->assign('uid',$uid);
        return $this->fetch();
    }

    /*
     * 导出荣誉值明细
     */
    public function exportHonor() {
        $params = input('param.');
        $where = [];
        if($params['uid'] != '' &&  $params['uid'] != 0) {
            $where= ['h.uid'=>$params['uid']];
        }
        if(!empty($params['type'])){
            $where['h.type'] = $params['type'];
        }
        if(isset($params['scoretime'])&&$params['scoretime']!=''){
            $time = explode(' - ',$params['scoretime']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['h.addtime'] = array('between',$time1.','.$time2);
        }
        if(!empty($params['name'])){
            $where['h.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
        }
        $honorlog = new HonorLog();
        $is_export = 1;
        $list = $honorlog->get_list($where,$is_export);
        $data = $list['rows'];
        Loader::import('PHPExcel.Classes.PHPExcel',VENDOR_PATH);
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(60);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);

        $objPHPExcel->getActiveSheet()->setCellValue('A1','ID');
        $objPHPExcel->getActiveSheet()->setCellValue('B1','昵称');
        $objPHPExcel->getActiveSheet()->setCellValue('C1','手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('D1','获得荣誉值');
        $objPHPExcel->getActiveSheet()->setCellValue('E1','来源说明');
        $objPHPExcel->getActiveSheet()->setCellValue('F1','来源类别');
        $objPHPExcel->getActiveSheet()->setCellValue('G1','时间');
        foreach ($data as $k=>$val){
            $type = self::sourceType($val['type']);
            $addtime = date('Y-m-d H:i:s',$val['addtime']);
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->getStyle('A'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->getStyle('C'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,$val['id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,$val['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,$val['tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,$val['value']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,$val['content']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,$type);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,$addtime);
        }
        // 1.保存至本地Excel表格
        $objWriter->save('荣誉值明细数据.xls');
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="荣誉值明细数据.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    public static function sourceType($type = 1) {
        $arr = ['1'=>'购买课程','2'=>'加成学习','3'=>'直推实名好友','4'=>'分享每日金句','5'=>'转发文章'];
        return $arr[$type];
    }


    /*
     * 学习力明细
     * @param int $uid
     */
    public function learningPower($uid = 0) {
        if($this->request->isAjax()){
            $params = input('param.');
            $where = [];
            if(!empty($uid)){
                $where['l.uid'] = $uid;
            }
            if(!empty($params['type'])){
                $where['l.type'] = $params['type'];
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['h.addtime'] = array('between',$time1.','.$time2);
            }

            if(!empty($uid)){
                if(!empty($params['name'])){
                    $where['u.tel|u.name'] = ['like','%'.$params['name'].'%'];
                }
            }else{
                if(!empty($params['name'])){
                    $where['l.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
                }
            }
            $LearningPowerLog = new LearningPowerLog();
            $is_export = 0;
            return $LearningPowerLog->get_list($where,$is_export);
        }
        $this->assign('uid',$uid);
        return $this->fetch();
    }
    /*
     * 加成学习力任务
     * @param int $uid
     */
    public function pulsLearningPower($uid = 0) {
        if($this->request->isAjax()){
            $params = input('param.');
            $where = [];
            if(!empty($uid)){
                $where['l.uid'] = $uid;
            }
            if(!empty($params['status'])){
                $where['l.status'] = $params['status'];
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['l.addtime'] = array('between',$time1.','.$time2);
            }

            if(!empty($uid)){
                if(!empty($params['name'])){
                    $where['u.tel|u.name'] = ['like','%'.$params['name'].'%'];
                }
            }else{
                if(!empty($params['name'])){
                    $where['l.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
                }
            }
            $PulsLearnPowerLog = new PulsLearnPowerLog();
            $is_export = 0;
            return $PulsLearnPowerLog->get_list($where,$is_export);
        }
        $this->assign('uid',$uid);
        return $this->fetch();
    }

    /**
     * 导出学习力明细
     */
    public function exportLearnPower() {
        $params = input('param.');
        $where = [];
        if($params['uid'] != '' &&  $params['uid'] != 0) {
            $where= ['l.uid'=>$params['uid']];
        }
        if(!empty($params['type'])){
            $where['l.type'] = $params['type'];
        }
        if(isset($params['scoretime'])&&$params['scoretime']!=''){
            $time = explode(' - ',$params['scoretime']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['h.addtime'] = array('between',$time1.','.$time2);
        }

        if(!empty($uid)){
            if(!empty($params['name'])){
                $where['u.tel|u.name'] = ['like','%'.$params['name'].'%'];
            }
        }else{
            if(!empty($params['name'])){
                $where['l.uid|u.name|u.tel'] = ['like','%'.$params['name'].'%'];
            }
        }
        $LearningPowerLog = new LearningPowerLog();
        $is_export = 1;
        $list = $LearningPowerLog->get_list($where,$is_export);
        $data = $list['rows'];

        Loader::import('PHPExcel.Classes.PHPExcel',VENDOR_PATH);
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(60);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);

        $objPHPExcel->getActiveSheet()->setCellValue('A1','ID');
        $objPHPExcel->getActiveSheet()->setCellValue('B1','昵称');
        $objPHPExcel->getActiveSheet()->setCellValue('C1','手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('D1','获得学习力');
        $objPHPExcel->getActiveSheet()->setCellValue('E1','来源说明');
        $objPHPExcel->getActiveSheet()->setCellValue('F1','来源类别');
        $objPHPExcel->getActiveSheet()->setCellValue('G1','时间');
        foreach ($data as $k=>$val){
            $type = self::learnSourceType($val['type']);
            $addtime = date('Y-m-d H:i:s',$val['addtime']);
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->getStyle('A'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->getStyle('C'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,$val['id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,$val['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,$val['tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,$val['value']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,$val['content']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,$type);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,$addtime);
        }
        // 1.保存至本地Excel表格
        $objWriter->save('学习力明细数据.xls');
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="学习力明细数据.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    /*
     * 学习力明细
     */
    public static function learnSourceType($type = 1) {
        $arr = ['1'=>'学习课程','2'=>'兑换课程'];
        return $arr[$type];
    }

    /*
     * 才学堂购买课程明细
     * @param int $uid
     */
    public function buyCourseOrder($uid = 0) {
        if($this->request->isAjax()){
            $params = input('param.');
            $where = [];
            if(!empty($uid)){
                $where['o.uid']= $uid;
            }
            if(isset($params['pay_type']) && $params['pay_type'] != '') {
                $where['o.pay_type'] = $params['pay_type'];
            }

            if(isset($params['status'])){
                if($params['status']!=''){
                    $where['o.status'] = $params['status'];
                }
            }
            if(isset($params['cname'])){
                if($params['cname']!=''){
                    $where['c.name'] = $params['cname'];
                }
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['o.addtime'] = array('between',$time1.','.$time2);
            }
            if(!empty($uid)){
                if(!empty($params['name'])){
                    $where['u.name|u.tel|o.order_id'] = ['like','%'.$params['name'].'%'];
                }
            }else{
                if(!empty($params['name'])){
                    $where['o.uid|u.name|u.tel|o.order_id'] = ['like','%'.$params['name'].'%'];
                }
            }
            $where['o.status']=array('gt',3);//财学堂的状态   4 未支付 5 已支付  6 已过期  7 学习完
            //var_dump($where);exit;
            $creditScore = new Orders();
            $is_export = 0;
            //var_dump($where);exit;
            return $creditScore->getLearnCourse($where,$is_export);
        }
        $this->assign('uid',$uid);
        return $this->fetch();
    }
    /*
     * 学才商购买课程明细
     * @param int $uid
     */
    public function buyxcsCourseOrder($uid = 0) {
        if($this->request->isAjax()){
            $params = input('param.');
            $where = [];
            if(!empty($uid)){
                $where['o.uid']= $uid;
            }
            if(isset($params['pay_type']) && $params['pay_type'] != '') {
                $where['o.pay_type'] = $params['pay_type'];
            }

            if(isset($params['status'])){
                if($params['status']!=''){
                    $where['o.status'] = $params['status'];
                }
            }
            if(isset($params['cname'])){
                if($params['cname']!=''){
                    $where['c.name'] = $params['cname'];
                }
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['o.addtime'] = array('between',$time1.','.$time2);
            }
            if(!empty($uid)){
                if(!empty($params['name'])){
                    $where['u.name|u.tel|o.order_id'] = ['like','%'.$params['name'].'%'];
                }
            }else{
                if(!empty($params['name'])){
                    $where['o.uid|u.name|u.tel|o.order_id'] = ['like','%'.$params['name'].'%'];
                }
            }
            $creditScore = new Orders();
            $is_export = 0;
            return $creditScore->getLearnCourse($where,$is_export);
        }
        $this->assign('uid',$uid);
        return $this->fetch();
    }
    /*
     * 课程购买明细
     */
    public function exportCourseOrder() {
        $params = input('param.');
        if($params['uid'] != '' &&  $params['uid'] != 0) {
            $where= ['o.uid'=>$params['uid']];
        }
        $where['o.pay_type'] = array('neq',0);
        if(!empty($params['pay_type'])){
            $where['o.pay_type'] = $params['pay_type'];
        }
        if(isset($params['status'])){
            if($params['status']!=''){
                $where['o.status'] = $params['status'];
            }
        }
        if(isset($params['cname'])){
            if($params['cname']!=''){
                $where['c.name'] = $params['cname'];
            }
        }
        if(isset($params['scoretime'])&&$params['scoretime']!=''){
            $time = explode(' - ',$params['scoretime']);
            $time1 = strtotime($time[0]);
            $time2 = strtotime($time[1]);
            $where['o.addtime'] = array('between',$time1.','.$time2);
        }
        if(!empty($params['uid']) && $params['uid'] != 0){
            if(!empty($params['name'])){
                $where['u.name|u.tel|o.order_id'] = ['like','%'.$params['name'].'%'];
            }
        }else{
            if(!empty($params['name'])){
                $where['o.uid|u.name|u.tel|o.order_id'] = ['like','%'.$params['name'].'%'];
            }
        }
        $creditScore = new Orders();
        $is_export = 1;
        $list = $creditScore->getLearnCourse($where,$is_export);
        $data = $list['rows'];

        Loader::import('PHPExcel.Classes.PHPExcel',VENDOR_PATH);
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);

        $objPHPExcel->getActiveSheet()->setCellValue('A1','订单号');
        $objPHPExcel->getActiveSheet()->setCellValue('B1','课程名称');
        $objPHPExcel->getActiveSheet()->setCellValue('C1','有效期（天）');
        $objPHPExcel->getActiveSheet()->setCellValue('D1','用户呢称');
        $objPHPExcel->getActiveSheet()->setCellValue('E1','用户手机号');
        $objPHPExcel->getActiveSheet()->setCellValue('F1','金额/学分');
        $objPHPExcel->getActiveSheet()->setCellValue('G1','支付类型');
        $objPHPExcel->getActiveSheet()->setCellValue('H1','购买时间');
        foreach ($data as $k=>$val){
            $pay_type = self::getPayType($val['pay_type']);
            $i = $k + 2;
            $objPHPExcel->getActiveSheet()->getStyle('A'.$i)->getNumberFormat();
            $objPHPExcel->getActiveSheet()->setCellValue('A'.$i,$val['order_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.$i,$val['name']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.$i,$val['effective']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.$i,$val['uname']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.$i,$val['tel']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.$i,$val['value']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.$i,$pay_type);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.$i,$val['paytime']);
        }
        // 1.保存至本地Excel表格
        $objWriter->save('课程购买明细数据.xls');
        // 2.接下来当然是下载这个表格了，在浏览器输出就好了
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="课程购买明细数据.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
    }

    public static function getPayType($type) {
        $arr = [
            '1'=>'学分','2'=>'现金','3'=>'实名','4'=>'赠送','5'=>'微信','6'=>'支付宝'
            ];
        return $arr[$type];
    }

    public function importUser(){
        $params = input('');
        //var_dump($params['name']);exit;
        Loader::import('PHPExcel.Classes.PHPExcel',VENDOR_PATH);
        Loader::import('PHPExcel.Classes.PHPExcel.IOFactory',VENDOR_PATH);
        Loader::import('PHPExcel.Classes.PHPExcel.Reader.Excel2007',VENDOR_PATH);
        $ptel = $params['ptel'];
        $user_model = new \app\index\model\User();
        $puserInfo = $user_model->where('tel',$ptel)->find();
        if(empty($puserInfo)){
            $this->error('此号码未注册');
        }
        $objReader=\PHPExcel_IOFactory::createReader('Excel2007');//use excel2007 for 2007 format
        $objPHPExcel=$objReader->load('7787.xlsx');//$file_url即Excel文件的路径
        $sheet=$objPHPExcel->getSheet(0);//获取第一个工作表
        $highestRow=$sheet->getHighestRow();//取得总行数
        $highestColumn=$sheet->getHighestColumn(); //取得总列数
        //循环读取excel文件,读取一条,插入一条
        for($j=1;$j<=$highestRow;$j++){//从第一行开始读取数据
         $str='';
         $str1 = '';
         for($k='A';$k<=$highestColumn;$k++){            //从A列读取数据
         //这种方法简单，但有不妥，以'\\'合并为数组，再分割\\为字段值插入到数据库,实测在excel中，如果某单元格的值包含了\\导入的数据会为空
          $str.=$objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue().'\\';//读取单元格
          if($j !=1 ){
                $jj = $j -1;
                 $str1.=$objPHPExcel->getActiveSheet()->getCell("$k$jj")->getValue().'\\';
             }
         }

         //explode:函数把字符串分割为数组。
         $strs=explode("\\",$str);
         //echo  $strs[0];echo "-------------";

         if($j !=1 ){
                      $strs1=explode("\\",$str1);
                      //echo $strs1[0];echo "<br>";
                      $userInfo = $user_model->where('tel',$strs[0])->find();
                      $info1 = $user_model->where('tel',$strs1[0])->find();


         }elseif($j ==1 ){
             $userInfo = $user_model->where('tel',$strs[0])->find();
             $info1 = $puserInfo;
         }
         if(!empty($userInfo)){
            continue;
         }
        $salt = get_rand_char(4);
        $pay_salt = get_rand_char(4);
        $student_no = $this->studentMake();
        //$where = ['tel' => $tel];
        //$info = $user_model->where($where)->find();
        //if(!preg_match("/^1[3456789]\d{9}$/", $tel)){
           // return returnjson(1001, '', '手机号不合法');
        //}
        $name = "用户".substr($strs[0],-4);
                //if (empty($info)) {
        $data = [
            'tel'=>$strs[0],
                        'name'=>$name,
                        'salt'=>$salt,
                      'pay_salt'=>$pay_salt,
                        'student_no'=>$student_no,
                        'regetime'=>time(),
                      	'pid'=>$info1['id']
        ];

        if($info1['pid'] == 0) {
            $data['parentids'] = "0,";
        }else{
            $data['parentids'] = $info1['parentids'].$info1['id'].',';
        }

        $user_model->insert($data);

        }
        //unlink($file_url); //删除excel文件
    }
    public function recommend(){
        if($this->request->isAjax()){
            $where = [];
            return RecommendService::getList($where);
        }
        return $this->fetch('recommend');
    }
     /*
     * 生成学号
     */
    public function studentMake() {
        $student_no = get_rand_char(8);
        $user_model = new \app\index\model\User();
        if($user_model->where('student_no',$student_no)->find()){
            return $this->studentMake();
        }else{
            return $student_no;
        }
    }
    /**提现列表
     * @param array $params
     * @return array|mixed
     */
    public function overlog($params = [])
    {
        if($this->request->isAjax()){
            $params= $this->data_get;

            $where=UserOverlogService::OverlogWhere($params);
            //var_dump($where);exit;
            return UserOverlogService::OverlogList($where);
        }
        return $this->fetch();
    }

}
