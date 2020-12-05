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
use app\common\Common;

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
            return returnjson(1001,1,'未设置支付密码');
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
            $bank = Db::name('bank')->where('uid',$user['id'])->order('uptime','desc')->find();
            if($bank){
                $bank['bank_name'] = Db::name('bank_categroy')->where('id',$bank['bank_categroy'])->value('name');
            }else{
                $bank['bank_accounts'] = '';
                $bank['bank_username'] = '';
                $bank['bank_name'] = '';
            }

            $res=array( 'normal_money'=>$ret1['data']['normal_money'],
                'normal_team_money'=>$ret2['data']['normal_team_money'],'bank_accounts'=>$bank['bank_accounts'],'bank_username'=>$bank['bank_username'],'bank_name'=>$bank['bank_name'])

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
            $wall = WalletService::UserWalletsale($user['id']);
            //var_dump($wall);exit;
             $result = [
                 'cash_sum'=>$cash_sum,
                 'cash_sum_today'=>$cash_sum_today,
                 'bank_name' => $wall['data']['bank_name'],
                 'bank_accounts' => $wall['data']['bank_accounts'],
                 'bank_username' => $wall['data']['bank_username'],
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
        $per=Db::name('team_performance')->where('uid',$user['id'])->find();
        $common = new Common();
        $ret['data']['credit'] = $common->get_user_credit($this->uid);
        if(!empty($per)){
            $ret['data']['performance'] = $per['total'];
        }else{
            $ret['data']['performance'] = 0;
        }
        $ret['data']['frozen_money'] = $ret['data']['normal_money']+$ret['data']['frozen_money'];
        $ret['data']['frozen_team_money'] = $ret['data']['normal_team_money']+$ret['data']['frozen_team_money'];
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
                'where'         => ['user_id'=>$user['id'],'type'=>0,'money_type'=>0],
                'field'         =>['add_time,operation_money,operation_type,business_type,money_type,type']
            );
            $res1 = BaseService::WalletLogListApi($data_params1);

            $ret['data']['personalDataList']=$res1['data'];
            $data_params2 = array(
                'm'             => $start,
                'n'             => $number,
                'where'         => ['user_id'=>$user['id'],'type'=>1,'money_type'=>0],
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
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if(empty($this->uid)) {
            return returnjson(1100,'该用户已在其他设备登陆','该用户已在其他设备登陆');
        }
        if(empty($user_id)){
           $user_id =  $this->uid;

        }
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
        //var_dump($user_id);exit;
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
     * 余额详情
     * @return false|string
     */
     public function walletInfo(){
         $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $id = input('id');
        if(empty($id)){
            return returnjson('1001','','参数缺失');
        }
        $info = Db::name('wallet_log')->where('id',$id)->find();
        if($info['business_type']==1){
            $info['business_type']='推荐奖励';
        }elseif($info['business_type']==2){
            $info['business_type']='提现';
        }elseif($info['business_type']==3){
            $info['business_type']='消费';
        }
        elseif($info['business_type']==4){
            $info['business_type']='极差奖励';
        }
        $info['add_time']=date("Y/m/d H:i:s",$info['add_time']);
        $info['type']='学霸卡';
        $info['tel'] = Db::name('user')->where('id',$info['user_id'])->value('tel');
        $info['status'] = '已完成';
        //unset($info['operation_money']);
        unset($info['original_money']);
        unset($info['latest_money']);
         return returnjson(1000,$info,'获取成功');
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
    //银行分类
    public function bank_categroy(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $list = Db::name('bank_categroy')->field('id,name')->select();
        return returnjson(1000,$list,'获取成功');
    }
    //
    //银行卡列表
    public function user_bank(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $number = 10;
        if(empty(input('page'))){
            $page = 1;
        }else{
            $page = input('page');
        }

        $start = intval(($page-1)*$number);
        $list = Db::name('bank')->where('uid',$this->uid)->limit($start,$number)->order('uptime','desc')->select();
        foreach($list as $key=>$val){
            $cate =Db::name('bank_categroy')->where('id',$val['bank_categroy'])->field('img,icon,quota,name')->find();
            $list[$key]['categroy_name'] = $cate['name'];
            $list[$key]['img'] = $cate['img'];
            $list[$key]['icon'] = $cate['icon'];
            $list[$key]['quota'] = $cate['quota'];
        }
        //$list = Db::name('bank_categroy')->field('id,name')->select();
        return returnjson(1000,$list,'获取成功');
    }
    //添加银行卡
    //银行分类
    public function add_bank(){
        $token = input('token');
        if(!empty($token)) {
            $this->getUserInfo($token);
        }
        if($this->uid == 0) {
            return returnjson('1100','','该设备在其他地方登录');
        }
        $post = input('post.');
        if(empty($post['bank_categroy'])||empty($post['bank_accounts'])||empty($post['bank_categroy'])||empty($post['bank_username'])||empty($post['card_type'])||empty($post['card_code'])||empty($post['tel'])){
            return returnjson(1001,'','参数缺失');
        }
        $cate = Db::name('bank_categroy')->where('id',$post['bank_categroy'])->find();
        Db::name('bank')->insert(array('bank_username'=>$post['bank_username'],'bank_accounts'=>$post['bank_accounts'],'bank_categroy'=>$post['bank_categroy'],'card_type'=>$post['card_type'],'card_code'=>$post['card_code'],'tel'=>$post['tel'],'uid'=>$this->uid,'addtime'=>time(),'uptime'=>time()));
        return returnjson(1000,'','添加成功');
    }
    //获取默认地址
    public function get_defult_bank(){

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
