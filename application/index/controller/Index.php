<?php
namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\Faceorder;
use think\Session;
use think\Db;
use think\helper\Time;
/**
 * Class Index
 * @author Steed
 * @package app\index\controller
 */
class Index extends Base {
    public function index() {
        
        return $this->fetch();
    }

    public function empty_page() {
        return $this->fetch('/index/empty_page');
    }

   public function widgets() {
       $user_model = new \app\index\model\User();
       $order_model = new \app\index\model\Orders();
       $faceOrder = new Faceorder();
       //今日开始结束时间戳
        list($beginToday, $endToday) = Time::today();
       //本月开始结束时间戳
        list($beginThismonth, $endThismonth) = Time::month();
        //本年开始结束时间错
        list($beginYear, $endYear) = Time::year();

        //获取用户总数
        $user_all = $user_model->count();
        //今日新增用户
        $user_new = $user_model->where(['regetime'=>['between',[$beginToday,$endToday]]])->count();
        
        //总收入
        $order_all = $order_model->where('status!=0')->where(['pay_type'=>5,'pay_type'=>6])->sum('value');
        $face_all = $faceOrder->where('status=1')->sum('total_amount');
        $money_all = $order_all + $face_all;
        //今日收入
        $order_day = $order_model->where('status!=0')->where(['pay_type'=>5,'pay_type'=>6])->where(['paytime'=>['between',[$beginToday,$endToday]]])->sum('value');
        $face_dayl = $faceOrder->where('status=1')->where(['paytime'=>['between',[$beginToday,$endToday]]])->sum('total_amount');
        $money_day = $order_day + $face_dayl;
        //本月收入
        $order_month = $order_model->where('status!=0')->where(['pay_type'=>5,'pay_type'=>6])->where(['paytime'=>['between',[$beginThismonth,$endThismonth]]])->sum('value');
        $face_month = $faceOrder->where('status=1')->where(['paytime'=>['between',[$beginThismonth,$endThismonth]]])->sum('total_amount');
        $money_month = $order_month + $face_month;
        //年度收入
        $order_year = $order_model->where('status!=0')->where(['pay_type'=>5,'pay_type'=>6])->where(['paytime'=>['between',[$beginYear,$endYear]]])->sum('value');
        $face_year = $faceOrder->where('status=1')->where(['paytime'=>['between',[$beginYear,$endYear]]])->sum('total_amount');
        $money_year = $order_year + $face_year;
        $data = Session::get('memberinfo');
        $data['user_all'] = $user_all;
        $data['user_new'] = $user_new;
        $data['money_all'] = $money_all;
        $data['money_day'] = $money_day;
        $data['money_month'] = $money_month;
        $data['money_year'] = $money_year;
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 用户分析
     */
    public function useranalyze(){

        return $this->fetch();
    }

    /**
     * 报名用户量
     * @return mixed
     */
    public function applyanalyze(){
        return $this->fetch();
    }

    /**
     * 试听用户量
     */
    public function listeneranalyze(){
        return $this->fetch();
    }
    
    //首页统计
   public function statistics(){
        return $this->fetch();
    }
}
