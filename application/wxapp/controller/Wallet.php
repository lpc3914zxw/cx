<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 11/17/20
 * Time: 3:31 PM
 */

namespace app\wxapp\controller;
use app\service\BaseService;
use app\service\WalletService;
use think\Db;
use think\Request;
use app\service\CashService;
use app\service\UserService;
use think\helper\Time;


class Wallet extends Base
{
    /**
     *提现申请
     */
    public function Toapply(){
        $token = input('token');
        $password = input('password');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,0,'该用户已在其他设备登陆');
        }
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $where['addtime'] = ['between',"'".$beginToday.",".$endToday."'"];
        $count = Db::name('checkpass')->where('uid',$this->uid)->where($where)->count();
        if($count>=10){
            return returnjson(1001,0,'您已多次输错，请明天再试');
        }
        $user = new \app\wxapp\model\User();
        $userinfo = $user->where('id',$this->uid)->find();
        if(empty($userinfo['pay_password'])){
            return returnjson(1000,1,'未设置支付密码');
        }
        $pass = splice_password($password, $userinfo['pay_salt']);
        if($pass!=$userinfo['pay_password']){
            Db::name('checkpass')->insert(['uid'=>$this->uid,'addtime'=>time()]);
            return returnjson(1001,0,'密码不正确');
        }
        Db::name('checkpass')->where('uid',$this->uid)->delete();
            $params = $this->data_post;
            $params['user'] = UserService::LoginUserInfo();
            $ret=CashService::CashCreate($params);
            if($ret['code']!==0){
                return returnjson(1001,'',$ret['msg']);
            }else{
                return returnjson(1000,'',$ret['msg']);
            }

    }
    /**
     *提现申请
     */
    public function Toapplyhint(){
        $params = $this->data_post;
        $params['user'] = UserService::LoginUserInfo();
        $ret=CashService::CashHint($params);
        if($ret['code']!==0){
            return returnjson(1001,'',$ret['msg']);
        }else{
            return returnjson(1000,'',$ret['data']);
        }
    }
    /**
     *提现申请
     */
    public function Toapplyamount(){
            $user = UserService::LoginUserInfo();

            $ret1=WalletService::UserWalletsale($user['id']);

            //var_dump($ret1);exit;
            $ret2=WalletService::UserWalletteam($user['id']);
            $res=array( 'normal_money'=>$ret1['data']['normal_money'],
                'normal_team_money'=>$ret2['data']['normal_team_money'])

            ;

            return returnjson(1000,$res,'获取成功');
    }

    /**
     * 获取用户余额和冻结金额
     */
    public function wallet(){
        $user = UserService::LoginUserInfo();
        $type=input('type');
        if($type==0){//分销
            $ret=WalletService::UserWalletsale($user['id']);
            if($ret['code']!==0){
                return returnjson(1001,'',$ret['msg']);
            }
            return returnjson(1000,$ret['data'],'获取成功');
        }else if($type==1){//分销
            $ret=WalletService::UserWalletteam($user['id']);
            if($ret['code']!==0){
                return returnjson(1001,'',$ret['msg']);
            }
            return returnjson(1000,$ret['data'],'获取成功');
        }
    }
    /**
     * 获取用户余额和冻结金额
     */
    public function walletcash(){
            $user = UserService::LoginUserInfo();
            $cash_sum=BaseService:: WalletCashsum($user['id']);
            $cash_sum_today=BaseService:: WalletCashsumtoday($user['id']);
             $result = [
                 'cash_sum'=>$cash_sum,
                 'cash_sum_today'=>$cash_sum_today,
             ];
            return returnjson(1000,$result,'获取成功');

    }
    /**
     * 用户钱包 团队 和个人
     */
    public function userwallet(){
        //$params = $this->data_post;
        $user = UserService::LoginUserInfo();
        $ret=WalletService::UserWalletsale($user['id']);
        if($ret['code']!==0){
            return returnjson(1001,'',$ret['msg']);
        }
            // 分页
            $number = 5;
            $page = 1;
            $start = intval(($page-1)*$number);

            // 获取列表
            $data_params1 = array(
                'm'             => $start,
                'n'             => $number,
                'where'         => ['user_id'=>$user['id'],'type'=>0],
                'field'         =>['add_time,operation_money,operation_type,business_type,money_type,type']
            );
            $res1 = BaseService::WalletLogListApi($data_params1);
            $ret['data']['personalDataList']=$res1['data'];
            $data_params2 = array(
                'm'             => $start,
                'n'             => $number,
                'where'         => ['user_id'=>$user['id'],'type'=>1],
                'field'         =>['add_time,operation_money,operation_type,business_type,money_type,type']
            );
            $res2 = BaseService::WalletLogListApi($data_params2);

            $ret['data']['teamDataList']=$res2['data'];
            return returnjson(1000,$ret['data'],'成功');

    }

    /**
     * 个人钱包记录
     * @return false|string
     */
    public function userwalletlog(){
        $user = UserService::LoginUserInfo();
        $user_id=$user['id'];
        $params=$this->data_post;
        $params=$_REQUEST;
        //var_dump($params);exit;
        if($params['datetype']=='' or $params['datetype'] > 2){
            return returnjson(1001,'','参数错误');
        }
        if($params['type']=='' or $params['type'] > 2){
            return returnjson(1001,'','参数错误');
        }
        if($params['datetype'] == 0) {
            list($params['start_time'], $params['end_time']) = Time::month();
        }else  if($params['datetype'] == 1){
            $date = explode('-',$params['date']);
            $timeArr = getMonthBeginAndEnd($date[0], $date[1],0);
            $params['start_time'] = $timeArr['startTime'];
            $params['end_time'] = $timeArr['endTime'];
        }else if($params['datetype'] == 2) {
            $arrDate = explode(',',$params['date']);
            $params['start_time'] = strtotime($arrDate[0]);
            $params['end_time'] = strtotime($arrDate[1]);
        }

        // 分页
        $number = 10;
        $page = max(1, isset($this->data_post['page']) ? intval($this->data_post['page']) : 1);
        // 条件
        $where = BaseService::WalletLogWhereApi($params,$user_id);
        // 获取总数
        $total = BaseService::WalletLogTotalApi($where);
        $income = BaseService::WalletLogTotalIncome($where);
        $expend = BaseService::WalletLogTotalExpend($where);
        $page_total = ceil($total/$number);
        $start = intval(($page-1)*$number);

        // 获取列表
        $data_params = array(
            'm'             => $start,
            'n'             => $number,
            'where'         => $where,
            'field'         =>['id,operation_money,add_time,status,business_type,operation_type']
        );
        $data = BaseService::WalletLogListApi($data_params);

        $result = [
            'income'=>$income,
            'expend'=>$expend,
            'total'             => $total,
            'page'              => $page_total,
            'datalist'              => $data['data'],
        ];
        return returnjson(1000,$result,'获取成功');

    }
    /**
     * 个人钱包记录
     * @return false|string
     */
    public function usercashlog(){
        $params=$this->data_post;
        if($params['datetype'] == 0) {
            list($params['start_time'], $params['end_time']) = Time::month();
        }else  if($params['datetype'] == 1){
            $date = explode('-',$params['date']);
            $timeArr = getMonthBeginAndEnd($date[0], $date[1],0);
            $params['start_time'] = $timeArr['startTime'];
            $params['end_time'] = $timeArr['endTime'];
        }else if($params['datetype'] == 2) {
            $arrDate = explode(',',$params['date']);
            $params['start_time'] = strtotime($arrDate[0]);
            $params['end_time'] = strtotime($arrDate[1]);
        }
        $user = UserService::LoginUserInfo();
        $user_id=$user['id'];
        // 分页
        $number = 10;
        $page = max(1, isset($this->data_post['page']) ? intval($this->data_post['page']) : 1);
        // 条件
        $where = BaseService::WalletcashWhereApi($params,$user_id);

        // 获取总数
        $total = BaseService::WalletcashTotalApi($where);
        $income = BaseService::WalletLogTotalIncome($where);
        $expend = BaseService::WalletLogTotalExpend($where);

        $page_total = ceil($total/$number);
        $start = intval(($page-1)*$number);

        // 获取列表
        $data_params = array(
            'm'             => $start,
            'n'             => $number,
            'where'         => $where,
            'field'         =>['id,money,status,add_time,type']
        );
        $data = BaseService::WalletcashListApi($data_params);

        $result = [
            'income'=>$income,
            'expend'=>$expend,

            'total'             => $total,
            'page'              => $page_total,
            'datalist'              => $data['data'],
        ];
        return returnjson(1000,$result,'获取成功');

    }
    /***测试钱包分销代码块
     *
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
/*    public function fenxiao(){
        $params = $this->data_post;
        //获取订单信息
        $res=Db::name('order')->where(['out_trade_no'=>$params['out_trade_no']])->find();
        //var_dump($res['total_amount']);exit;
        $pid=Db::name('user')->where('id','=',$res['uid'])->value('pid');
        WalletService::AddUserWallet($res['uid']);//自动添加钱包
        if(!empty($pid)){
            //获取一级分销比例
            $one_course_scale =MyC('one_course_scale');
            $money_total=$res['total_amount'];
            $money_one_sale=$money_total*$one_course_scale/100;
            //var_dump(PriceNumberFormat($money_one_sale));exit;
            if($money_one_sale>0.00){
                WalletService::UserWalletMoneyUpdate($res,$one_course_scale,1,$pid,$money_one_sale,1,'normal_money',1);
            }
        }else{
            $pid=0;//没有上级
        }
        $ppid=Db::name('user')->where('id',$pid)->value('pid');
        if(!empty($ppid)){
            $two_course_scale =MyC('two_course_scale');
            $money_total=$res['total_amount'];
            $money_two_sale=$money_total*$two_course_scale/100;
            if($money_one_sale>0.00){
                WalletService::UserWalletMoneyUpdate($res,$two_course_scale,2,$ppid,$money_two_sale,1,'normal_money',1);
            }
        }else{
            $ppid=0;//没有上级
        }
        //is_scale是否分销
        Db::name('order')->where(['out_trade_no'=>$params['out_trade_no']])->update(['status'=>1,'paytime'=>time(),'paytype'=>2,'pid'=>$pid,'ppid'=>$ppid,'is_scale'=>1]);
    }*/

}
