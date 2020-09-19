<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\wxapp\model\BrowseRecords;
use think\Session;
use think\Db;
use think\helper\Time;

/**
 * 数据统计
 * Class Datacensus
 * @package app\index\controller
 */
class Datacensus extends Base
{
    public function datacensus() {
        if($this->request->isAjax()) {
            $user_model = new \app\index\model\User();
            $browseRecords_model = new BrowseRecords();
            $order_model = new \app\index\model\Orders();
            list($beginToday, $endToday) = Time::today();
            list($beginWeek,$endWeek) = Time::week();
            list($beginMonth,$endMonth) = Time::month();

            //获取用户总数
            $user_all = $user_model->count();
            //今日新增用户
            $user_new = $user_model->where(['regetime'=>['between',[$beginToday,$endToday]]])->count();
            $data = Session::get('memberinfo');
            // 今日收入
            $todayMoney = $order_model->where(['pay_type'=>['in',("2,5,6")],'status'=>['neq',0],'paytime'=>['between',[$beginToday,$endToday]]])->sum('value');
            // 本月收入
            $monthMoney = $order_model->where(['pay_type'=>['in',("2,5,6")],'status'=>['neq',0],'paytime'=>['between',[$beginMonth,$endMonth]]])->sum('value');
            // 本日访问量
            $todayBrowse = $browseRecords_model->where(['addtime'=>['between',[$beginToday,$endToday]]])->count();
            // 本周访问量
            $weekBrowse = $browseRecords_model->where(['addtime'=>['between',[$beginWeek,$endWeek]]])->count();
            // 本月访问量
            $monthBrowse = $browseRecords_model->where(['addtime'=>['between',[$beginMonth,$endMonth]]])->count();

            $arr[0] = time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600;
            $arr[1] = time() - ((date('w') == 0 ? 7 : date('w')) - 2) * 24 * 3600;
            $arr[2] = time() - ((date('w') == 0 ? 7 : date('w')) - 3) * 24 * 3600;
            $arr[3] = time() - ((date('w') == 0 ? 7 : date('w')) - 4) * 24 * 3600;
            $arr[4] = time() - ((date('w') == 0 ? 7 : date('w')) - 5) * 24 * 3600;
            $arr[5] = time() - ((date('w') == 0 ? 7 : date('w')) - 6) * 24 * 3600;
            $arr[6] = time() - ((date('w') == 0 ? 7 : date('w')) - 7) * 24 * 3600;

            $new = array();
            foreach ($arr as $k => $v) {//一天开始到结束的时间戳
                $arr[$k] = date('Y/m/d', $v);
                $start = mktime(0, 0, 0, date("m", $v), date("d", $v), date("Y", $v));
                $new[$k]['begin_date'] = $start;
                $end = mktime(23, 59, 59, date("m", $v), date("d", $v), date("Y", $v));
                $new[$k]['end_date'] = $end;
            }

            foreach ($new as $k => $v) {
                // 访问量
                $pv_count = $browseRecords_model->where(['addtime'=>['between',[$v['begin_date'],$v['end_date']]]])->count();
                $pv[$k] = $pv_count;       // 访问次数
                // 新增人数
                $uv[$k] = $user_model->where(['regetime'=>['between',[$v['begin_date'],$v['end_date']]]])->count();
            }

            // 月数据
            $year = date('Y',time());
            $years = $this->get_days_by_year($year);
            $newMonth = array();
            $arr = [];
            for($i = 0;$i < 12;$i ++){
                $arr[$i] = $years[$i+1]['day'];
            }
            foreach ($arr as $k => $v) {
                if($k < 9){
                    $month = '0'.($k +1);
                }else{
                    $month = $k + 1;
                }
                $month_start = $year . "-".$month.'-01'; //当前年月
                $month_end = $year."-".$month."-".$v;
                $newMonth[$k]['month_begin_date'] = strtotime($month_start);
                $newMonth[$k]['month_end_date'] = strtotime($month_end);
            }

            foreach ($newMonth as $k => $v) {
                // 访问量
                $month_pv_count = $browseRecords_model->where(['addtime'=>['between',[$v['month_begin_date'],$v['month_end_date']]]])->count();
                $month_pv[$k] = $month_pv_count;       // 访问次数
                // 新增人数
                $month_uv[$k] = $user_model->where(['regetime'=>['between',[$v['month_begin_date'],$v['month_end_date']]]])->count();
            }


            return ['code'=>1,'daydata'=>['pv'=>$pv,'uv'=>$uv,'user_all'=>$user_all,'user_new'=>$user_new],
                'monthdata'=>['month_pv'=>$month_pv,'month_uv'=>$month_uv],'moneydata'=>['todayMoney'=>$todayMoney,'monthMoney'=>$monthMoney],
                'browsedata'=>['todayBrowse'=>$todayBrowse,'weekBrowse'=>$weekBrowse,'monthBrowse'=>$monthBrowse]];
        }
        $this->fetch('/index/widgets');
    }


    /*
     * @param $year 年份
     */
    function get_days_by_year($year){
        //首先判断闰年
        if($year % 400 == 0  || ($year % 4 == 0 && $year % 100 !== 0)){
            $rday = 29;
        }else{
            $rday = 28;
        }
        for ($i = 1; $i <= 12;$i ++){
            if($i == 2){
                $days = $rday;
            }else{
                $days = (($i - 1)%7%2) ? 30 : 31;  //判断是大月（31），还是小月（30）
            }
            $dayinfo[$i]['day'] = $days;
        }
        return $dayinfo;
    }
}