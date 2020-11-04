<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\wxapp\model\BrowseRecords;
use phpDocumentor\Reflection\DocBlockFactory;
use think\Controller;
use think\Session;
use think\Db;
use think\helper\Time;
use app\index\model\Faceorder;

/**
 * 数据统计
 * Class Datacensus
 * @package app\index\controller
 */
class Datacensus extends Base
{

    /*累计统计报表*/
    public function index(){
        return $this->fetch();
    }
    /*月统计报表*/
    public function month(){
        return $this->fetch();
    }
    /*本周统计报表*/
    public function weeks(){
        return $this->fetch();
    }
    /*今日统计报表*/
    public function day(){
        return $this->fetch();
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
    public function datacensus() {
        if($this->request->isAjax()) {

            $user_model = new \app\index\model\User();
            $browseRecords_model = new BrowseRecords();
            $order_model = new \app\index\model\Orders();
            list($beginToday, $endToday) = Time::today();
            list($beginWeek,$endWeek) = Time::week();
            list($beginMonth,$endMonth) = Time::month();
            $user_new = $user_model->where(['regetime'=>['between',[$beginToday,$endToday]]])->count();
            // 本日访问量
            $todayBrowse = $browseRecords_model->where(['addtime'=>['between',[$beginToday,$endToday]]])->count();
            //获取累计注册用户
            $user_all = $user_model->count();
            // 本周访问量
            $weekBrowse = $browseRecords_model->where(['addtime'=>['between',[$beginWeek,$endWeek]]])->count();
            // 本月访问量
            $monthBrowse = $browseRecords_model->where(['addtime'=>['between',[$beginMonth,$endMonth]]])->count();
            // 今日收入
            $todayMoney = $order_model->where(['pay_type'=>['in',("2,5,6")],'status'=>['neq',1],'paytime'=>['between',[$beginToday,$endToday]]])->sum('value');
            // 本月收入
            $monthMoney = $order_model->where(['pay_type'=>['in',("2,5,6")],'status'=>['neq',1],'paytime'=>['between',[$beginMonth,$endMonth]]])->sum('value');
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


            return ['code'=>1,'daydata'   =>['pv'=>$pv,'uv'=>$uv,'user_all'=>$user_all,'user_new'=>$user_new],
                             'monthdata'  =>['month_pv'=>$month_pv,'month_uv'=>$month_uv],'moneydata'=>['todayMoney'=>$todayMoney,'monthMoney'=>$monthMoney],
                             'browsedata' =>['todayBrowse'=>$todayBrowse,'weekBrowse'=>$weekBrowse,'monthBrowse'=>$monthBrowse]
            ];
        }
        $this->fetch('/index/widgets');
    }
    public function data() {
        // if($this->request->isAjax()) {
        $type=input('type');
        $user_model = new \app\index\model\User();
        $browseRecords_model = new BrowseRecords();
        $order_model = new \app\index\model\Orders();
        $creditSource_model = new \app\index\model\CreditSource();
        $order_model = new \app\index\model\Orders();
        $advanced_model = new \app\index\model\Advanced();
        $honorlog_model=new \app\index\model\HonorLog();
        $learningpowerlog_model=new \app\index\model\LearningPowerLog();
        $dedicationLog_model = new \app\index\model\DedicationLog();
        list($beginToday, $endToday) = Time::today();
        list($beginWeek,$endWeek) = Time::week();
        list($beginMonth,$endMonth) = Time::month();
        $user_total = $user_model->count();
        $user_total_lose = $user_model->where('score','=',0)->count();
        if($type=='today'){


            Db::name('statistical')->where(['start_time'=>['between',[$beginToday,$endToday]]])->select();
        //1今日注册用户
        $user_today_new =  Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginToday,$endToday]]])->sum('user_new');
        //2今日认证用户
        $face_today_user=    Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginToday,$endToday]]])->sum('user_face');
        // 3本日访问量
        $todayBrowse = Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginToday,$endToday]]])->sum('browse');
        //4今日系统学分产出
        $credit_today= round(Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginToday,$endToday]]])->sum('credit'),4);
        //6今日打赏学分产出
        $credit_d_today=Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginToday,$endToday]]])->sum('credit_d');
        //7 今日收入
        $todayMoney = Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginToday,$endToday]]])->sum('money_c');    // 8今日学分收入
        $todayCredit = Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginToday,$endToday]]])->sum('credit_c');    // 8今日学分收入
            $arr[0] = time() - ((date('H') == 0 ? 24 : date('H')) - 1)  * 3600;
            $arr[1] = time() - ((date('H') == 0 ? 24 : date('H')) - 2)  * 3600;
            $arr[2] = time() - ((date('H') == 0 ? 24 : date('H')) - 3)  * 3600;
            $arr[3] = time() - ((date('H') == 0 ? 24 : date('H')) - 4)  * 3600;
            $arr[4] = time() - ((date('H') == 0 ? 24 : date('H')) - 5)  * 3600;
            $arr[5] = time() - ((date('H') == 0 ? 24 : date('H')) - 6)  * 3600;
            $arr[6] = time() - ((date('H') == 0 ? 24 : date('H')) - 7)  * 3600;
            $arr[7] = time() - ((date('H') == 0 ? 24 : date('H')) - 8)  * 3600;
            $arr[8] = time() - ((date('H') == 0 ? 24 : date('H')) - 9)  * 3600;
            $arr[9] = time() - ((date('H') == 0 ? 24 : date('H')) - 10)  * 3600;
            $arr[10] = time() - ((date('H') == 0 ? 24 : date('H')) - 11)  * 3600;
            $arr[11] = time() - ((date('H') == 0 ? 24 : date('H')) - 12)  * 3600;
            $arr[12] = time() - ((date('H') == 0 ? 24 : date('H')) - 13)  * 3600;
            $arr[13] = time() - ((date('H') == 0 ? 24 : date('H')) - 14)  * 3600;
            $arr[14] = time() - ((date('H') == 0 ? 24 : date('H')) - 15)  * 3600;
            $arr[15] = time() - ((date('H') == 0 ? 24 : date('H')) - 16)  * 3600;
            $arr[16] = time() - ((date('H') == 0 ? 24 : date('H')) - 17)  * 3600;
            $arr[17] = time() - ((date('H') == 0 ? 24 : date('H')) - 18)  * 3600;
            $arr[18] = time() - ((date('H') == 0 ? 24 : date('H')) - 19)  * 3600;
            $arr[19] = time() - ((date('H') == 0 ? 24 : date('H')) - 20)  * 3600;
            $arr[20] = time() - ((date('H') == 0 ? 24 : date('H')) - 21)  * 3600;
            $arr[21] = time() - ((date('H') == 0 ? 24 : date('H')) - 22)  * 3600;
            $arr[22] = time() - ((date('H') == 0 ? 24 : date('H')) - 23)  * 3600;
            $arr[23] = time() - ((date('H') == 0 ? 24 : date('H')) - 24)  * 3600;

            $new = array();
            foreach ($arr as $k => $v) {//一天开始到结束的时间戳
                $arr[$k] = date('Y/m/d H', $v);
                $start = mktime(date("H", $v), 0, 0, date("m", $v), date("d", $v), date("Y", $v));
                $new[$k]['begin_date'] = $start;
                $end = mktime(date("H", $v), 59, 59, date("m", $v), date("d", $v), date("Y", $v));
                $new[$k]['end_date'] = $end;
            }
            foreach ($new as $k => $v) {
                // 访问量
                $pv_count = Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('browse');
                $pv[$k] = $pv_count;       // 访问次数
                // 新增人数
                $uv[$k] =  Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('user_new');
            }

            foreach ($new as $k => $v) {
                $honorlog_sum_pv[$k] = round(Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('honor'),4);
                $learningpowerlog_sum_uv[$k]= Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('learningpower');
                $dedicationLog_sum[$k]=  round(Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('dedication'),4);
                $credit_sum[$k]=round(Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('credit'),4);
            }


            $advanced_all=$advanced_model->order('id desc')->column('id');

            $advanced_name=$advanced_model->order('id desc')->column('name');

            $advanced_sum = array();
           foreach ($advanced_all as $k =>$v){
               $advanced_count[$k] =$order_model->where('advanced_id','=',$v)->where(['paytime'=>['between',[$beginToday,$endToday]]])->count();
               $advanced_sum_score[$k] =$order_model->where('advanced_id','=',$v)->where(['paytime'=>['between',[$beginToday,$endToday]]])->sum('score');
               $advanced_sum_value[$k] =$order_model->where('advanced_id','=',$v)->where(['paytime'=>['between',[$beginToday,$endToday]]])->sum('value');


           }



            return ['code'=>1,'daydata'   =>['pv'=>$pv,'uv'=>$uv,
                'hpv'=>$honorlog_sum_pv,'luv'=>$learningpowerlog_sum_uv,
                'dpv'=>$dedicationLog_sum,'cuv'=>$credit_sum,
                                                'user_today_new'=>$user_today_new, //1今日注册用户
                                                'face_today_user'=>$face_today_user, //2今日认证用户
                                                'credit_today'=>$credit_today,//4今日系统学分产出
                                                'credit_d_today'=>$credit_d_today, //6今日打赏学分产出
                                                'todayCredit'=>$todayCredit,  // 8今日学分收入
                                                'user_total'=>$user_total,//总用户
                                                'todayMoney'=>$todayMoney, //7 今日收入
                                                'todayBrowse'=>$todayBrowse ,//7 今日访问
                                                'user_total_lose'=>$user_total_lose //7 今日访问
                ],
                    'order'=>[
                        'advanced_name'=>$advanced_name,
                        'advanced_count'=>$advanced_count,
                        'advanced_sum_score'=>$advanced_sum_score,
                        'advanced_sum_value'=>$advanced_sum_value

            ]
            ];

        }elseif ($type=='weeks'){
        //1本周注册用户
        $user_weeks_new = Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginWeek,$endWeek]]])->sum('user_new');

            //3本周访问量
        $weekBrowse = Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginWeek,$endWeek]]])->sum('browse');
            //echo Db::name('statistical')->getLastSql();exit;
            //4本周认证用户
        $face_weeks_user= Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginWeek,$endWeek]]])->sum('user_face');

            //5本周系统学分产出
        $credit_weeks=round(Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginWeek,$endWeek]]])->sum('credit'),4);

            //6本周打赏学分产出
        $credit_d_weeks=Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginWeek,$endWeek]]])->sum('credit_d');

            //7本周收入
        $weekMoney = Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginWeek,$endWeek]]])->sum('money_c');    // 8今日学分收入
            //8本周学分收入
        $weekCredit =  Db::name('statistical')
            ->where('type','=',1)->where(['start_time'=>['between',[$beginToday,$endToday]]])->sum('credit_c');
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
                $pv_count = Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('browse');
                $pv[$k] = $pv_count;       // 访问次数
                // 新增人数
                $uv[$k] =  Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('user_new');


            }
            foreach ($new as $k => $v) {
                $honorlog_sum_pv[$k] =round( Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('honor'),2);
                $learningpowerlog_sum_uv[$k]=round( Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('learningpower'),2);
                $dedicationLog_sum[$k]= round(Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('dedication'),2);
                $credit_sum[$k]=round(Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('credit'),2);

            }

            $advanced_all=$advanced_model->order('id desc')->column('id');

            $advanced_name=$advanced_model->order('id desc')->column('name');

            $advanced_sum = array();
            foreach ($advanced_all as $k =>$v){
                $advanced_count[$k] =$order_model->where('advanced_id','=',$v)->where(['paytime'=>['between',[$beginWeek,$endWeek]]])->count();
                $advanced_sum_score[$k] =$order_model->where('advanced_id','=',$v)->where(['paytime'=>['between',[$beginWeek,$endWeek]]])->sum('score');
                $advanced_sum_value[$k] =$order_model->where('advanced_id','=',$v)->where(['paytime'=>['between',[$beginWeek,$endWeek]]])->sum('value');
            }
            return ['code'=>1,'daydata'   =>['pv'=>$pv,'uv'=>$uv,
                'hpv'=>$honorlog_sum_pv,'luv'=>$learningpowerlog_sum_uv,
                'dpv'=>$dedicationLog_sum,'cuv'=>$credit_sum,
                'user_weeks_new'=>$user_weeks_new, //1今日注册用户
                'face_weeks_user'=>$face_weeks_user, //2今日认证用户
                'credit_weeks'=>$credit_weeks,//4今日系统学分产出
                'credit_d_weeks'=>$credit_d_weeks, //6今日打赏学分产出
                'weekCredit'=>$weekCredit,  // 8今日学分收入
                'user_total'=>$user_total,//总用户
                'weekMoney'=>$weekMoney, //7 今日收入
                'weekBrowse'=>$weekBrowse ,//7 今日访问
                'user_total_lose'=>$user_total_lose //7 今日访问
            ],
                'order'=>[
                    'advanced_name'=>$advanced_name,
                    'advanced_count'=>$advanced_count,
                    'advanced_sum_score'=>$advanced_sum_score,
                     'advanced_sum_value'=>$advanced_sum_value

                ]
            ];


        }elseif ($type=='month') {

            //本月注册用户
            $user_moth_new = Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$beginMonth,$endMonth]]])->sum('user_new');

            // 本月访问量
            $monthBrowse = Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$beginMonth,$endMonth]]])->sum('browse');

            //本月认证用户
            $face_month_user =  Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$beginMonth,$endMonth]]])->sum('user_face');

            //本月系统学分产出
            $credit_month =round( Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$beginMonth,$endMonth]]])->sum('credit'),4);

            //本月打赏学分产出
            $credit_d_month = Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$beginMonth,$endMonth]]])->sum('credit_d');

            // 本月收入
            $monthMoney =Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$beginMonth,$endMonth]]])->sum('money_c');
            // 本月学分收入
            $monthCredit = Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$beginMonth,$endMonth]]])->sum('credit_c');

            $arr = array();
            $month_day=date("t",strtotime(date('Y-m',time())));
            $month_day=intval($month_day);
            for ($i = 0;$i<=$month_day; $i++ ){
                    $arr[$i] = time() - ((date('d') == 0 ? $month_day : date('d')) - ($i+1)) * 24 * 3600;
                    $day[$i]=($i+1).'日';
            }
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
                $pv_count = Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('browse');
                $pv[$k] = $pv_count;       // 访问次数
                // 新增人数
                $uv[$k] =  Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('user_new');

            }

            foreach ($new as $k => $v) {
                $honorlog_sum_pv[$k] =round( Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('honor'),2);
                $learningpowerlog_sum_uv[$k]=round( Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('learningpower'),2);
                $dedicationLog_sum[$k]=round( Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('dedication'),2);
                $credit_sum[$k]=round(Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['begin_date'],$v['end_date']]]])->sum('credit'),2);

            }

            $advanced_all=$advanced_model->order('id desc')->column('id');

            $advanced_name=$advanced_model->order('id desc')->column('name');

            $advanced_sum = array();
            foreach ($advanced_all as $k =>$v) {
                $advanced_count[$k] = $order_model->where('advanced_id', '=', $v)->where(['paytime' => ['between', [$beginMonth, $endMonth]]])->count();
                $advanced_sum_score[$k] = $order_model->where('advanced_id', '=', $v)->where(['paytime' => ['between', [$beginMonth, $endMonth]]])->sum('score');
                $advanced_sum_value[$k] = $order_model->where('advanced_id', '=', $v)->where(['paytime' => ['between', [$beginMonth, $endMonth]]])->sum('value');
            }

            return ['code'=>1,'daydata'   =>['pv'=>$pv,'uv'=>$uv,'day'=>$day,
                'hpv'=>$honorlog_sum_pv,'luv'=>$learningpowerlog_sum_uv,
                'dpv'=>$dedicationLog_sum,'cuv'=>$credit_sum,
                'user_moth_new'=>$user_moth_new, //1今日注册用户
                'monthBrowse'=>$monthBrowse, //2今日认证用户
                'face_month_user'=>$face_month_user,//4今日系统学分产出
                'credit_month'=>$credit_month, //6今日打赏学分产出
                'credit_d_month'=>$credit_d_month,  // 8今日学分收入
                'user_total'=>$user_total,//总用户
                'monthMoney'=>$monthMoney, //7 今日收入
                'monthCredit'=>$monthCredit ,//7 今日访问
                'user_total_lose'=>$user_total_lose //7 今日访问
            ],
                'order'=>[
                    'advanced_name'=>$advanced_name,
                    'advanced_count'=>$advanced_count,
                    'advanced_sum_score'=>$advanced_sum_score,
                    'advanced_sum_value'=>$advanced_sum_value

                ]];


        }elseif ($type=='year') {

            //获取累计注册用户
            $user_all = Db::name('statistical')->sum('user_new');

            //获取累计访问量
            $Browse = Db::name('statistical')->sum('browse');
            //获取累计认证用户
            $face_user =Db::name('statistical')->sum('user_face');
            //累计系统学分产出
            $credit_total =round(Db::name('statistical')->sum('credit'),4);
            //累计打赏学分产出
            $credit_d_total =Db::name('statistical')->sum('credit_d');
            // 累计收入
            $totalMoney = Db::name('statistical')->sum('money_c');
            // 累计学分收入
            $totalCredit = Db::name('statistical')->sum('credit_c');
            //学分
            //加成学习力
            //学习力
            //贡献值

            //荣誉值
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
                $month_pv_count =  Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['month_begin_date'],$v['month_end_date']]]])->sum('browse');

                $month_pv[$k] = $month_pv_count;       // 访问次数
                // 新增人数
                $month_uv[$k] = Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['month_begin_date'],$v['month_end_date']]]])->sum('user_new');

            }
            $honorlog_model=new \app\index\model\HonorLog();
            $learningpowerlog_model=new \app\index\model\LearningPowerLog();
            foreach ($newMonth as $k => $v) {
                $honorlog_sum_pv[$k] = round(Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['month_begin_date'],$v['month_end_date']]]])->sum('honor'),2);
                $learningpowerlog_sum_uv[$k]= round(Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['month_begin_date'],$v['month_end_date']]]])->sum('learningpower'),2);
                $dedicationLog_sum[$k]= round(Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['month_begin_date'],$v['month_end_date']]]])->sum('dedication'),2);
                $credit_sum[$k]=round(Db::name('statistical')->where('type','=',1)->where(['start_time'=>['between',[$v['month_begin_date'],$v['month_end_date']]]])->sum('credit'),2);

            }

            $order_model = new \app\index\model\Orders();
            $advanced_model = new \app\index\model\Advanced();
            $advanced_all=$advanced_model->order('id desc')->column('id');

            $advanced_name=$advanced_model->order('id desc')->column('name');

            $advanced_sum = array();
            foreach ($advanced_all as $k =>$v) {
                $advanced_count[$k] = $order_model->where('advanced_id', '=', $v)->count();
                $advanced_sum_score[$k] = $order_model->where('advanced_id', '=', $v)->sum('score');
                $advanced_sum_value[$k] = $order_model->where('advanced_id', '=', $v)->sum('value');
            }
            return ['code'=>1,'daydata'   =>[
                'pv'=>$month_pv,'uv'=>$month_uv,
                'dpv'=>$dedicationLog_sum,'cuv'=>$credit_sum,
                'hpv'=>$honorlog_sum_pv,'luv'=>$learningpowerlog_sum_uv,
                'user_all'=>$user_all, //1今日注册用户
                'Browse'=>$Browse, //2今日认证用户
                'face_user'=>$face_user,//4今日系统学分产出
                'credit_total'=>$credit_total, //6今日打赏学分产出
                'credit_d_total'=>$credit_d_total,  // 8今日学分收入
                'user_total'=>$user_total,//总用户
                'totalMoney'=>$totalMoney, //7 今日收入
                'totalCredit'=>$totalCredit ,//7 今日访问
                'user_total_lose'=>$user_total_lose //7 今日访问

            ]
                ,
                'order'=>[
                    'advanced_name'=>$advanced_name,
                    'advanced_count'=>$advanced_count,
                    'advanced_sum_score'=>$advanced_sum_score,
                    'advanced_sum_value'=>$advanced_sum_value

                ]];

        }



        //}
        //$this->fetch('/index/widgets');
    }
}
