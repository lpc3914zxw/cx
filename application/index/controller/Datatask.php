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
class Datatask
{



    public function index() {
        //获取当前时间 获取上个小时的时间截范围
        $user_model = new \app\index\model\User();
        $browseRecords_model = new BrowseRecords();
        $order_model = new \app\index\model\Orders();
        $creditSource_model = new \app\index\model\CreditSource();
        $order_model = new \app\index\model\Orders();
        $advanced_model = new \app\index\model\Advanced();
        $honorlog_model=new \app\index\model\HonorLog();
        $learningpowerlog_model=new \app\index\model\LearningPowerLog();
        $dedicationLog_model = new \app\index\model\DedicationLog();

        $num=0;
        $hours=date('H')-1;
        $begin = strtotime(date('Y-m-d').$hours.':00:00');
        $end = strtotime(date('Y-m-d').$hours.':59:59');

        list($beginMonth,$endMonth) = Time::month();

        if(Db::name('statistical')->where('type','=',0)->count() == 0){
            //1注册用户
            $user_new = $user_model->where('regetime','<',$beginMonth)->count();
            //2今日认证用户
            $user_face=  $user_model->get_face_count_min($beginMonth);
            // 3本日访问量
            $browse =  $browseRecords_model->where('addtime','<',$beginMonth)->count();
            //4今日系统学分产出
            $credit= $creditSource_model->get_score_sum_min(1, '>',$beginMonth);
            //6今日打赏学分产出
            $credit_d=$creditSource_model->get_score_sum_min(2, '<',$beginMonth);
            //7 今日收入
            $money_c =  $order_model->where(['pay_type' => ['in', ("2,5,6")], 'status' => ['neq', 1]])->where('paytime','<',$beginMonth)->sum('value');
            // 8今日学分收入
            $credit_c =$order_model->where(['pay_type' => ['in', ("1")], 'status' => ['neq', 1]])->where('paytime','<',$beginMonth)->sum('value');
            $honor=$honorlog_model->where('addtime','<',$beginMonth)->sum('value');
            $learningpower=$learningpowerlog_model->where('addtime','<',$beginMonth)->sum('value');
            $dedication=  $dedicationLog_model->where('addtime','<',$beginMonth)->sum('value');

            $data = [
                'browse'=>$browse,
                'user_new'=>$user_new,
                'user_face'=>$user_face,
                'credit'=>$credit,
                'credit_d'=>$credit_d,
                'money_d'=>0,
                'credit_c'=>$credit_c,
                'money_c'=>$money_c,
                'time'=>\time(),
                'start_time'=>0,
                'end_time'=>0,
                'honor'=>$honor,
                'learningpower'=>$learningpower,
                'dedication'=>$dedication,
                'type'=>0,

            ];
            $res1=Db::name('statistical')->insert($data);

            $num++;

        }else{


            //1今日注册用户
            $user_new = $user_model->where(['regetime'=>['between',[$begin,$end]]])->count();
            //2今日认证用户
            $user_face=    $user_model->get_face_time_count($begin,$end);
            // 3本日访问量
            $browse = $browseRecords_model->where(['addtime'=>['between',[$begin,$end]]])->count();
            //4今日系统学分产出
            $credit=$creditSource_model->get_score__time_sum($begin,$end,1,'>');
            //6今日打赏学分产出
            $credit_d=$creditSource_model->get_score__time_sum($begin,$end,2,'<');
            //7 今日收入
            $money_c = $order_model->where(['pay_type'=>['in',("2,5,6")],'status'=>['neq',1],'paytime'=>['between',[$begin,$end]]])->sum('value');
            // 8今日学分收入
            $credit_c = $order_model->where(['pay_type'=>['in',("1")],'status'=>['neq',1],'paytime'=>['between',[$begin,$end]]])->sum('value');
            $honor=$honorlog_model->where(['addtime'=>['between',[$begin,$end]]])->sum('value');
            $learningpower=$learningpowerlog_model->where(['addtime'=>['between',[$begin,$end]]])->sum('value');
            $dedication=  $dedicationLog_model->where(['addtime'=>['between',[$begin,$end]]])->sum('value');
            $data = [
                'browse'=>$browse,
                'user_new'=>$user_new,
                'user_face'=>$user_face,
                'credit'=>$credit,
                'credit_d'=>$credit_d,
                'money_d'=>0,
                'credit_c'=>$credit_c,
                'money_c'=>$money_c,
                'time'=>\time(),
                'start_time'=>$begin,
                'end_time'=>$end,
                'honor'=>$honor,
                'learningpower'=>$learningpower,
                'dedication'=>$dedication,
                'type'=>1,

            ];

            if(Db::name('statistical')->where('start_time','=',$begin)->count()>0){
                $res=0;
            }else{
                $res=Db::name(  'statistical')->insert($data);
                $num++;
            }
        }
        echo $num;




    }
    public function index1(){
        ini_set ("memory_limit","-1");
        //获取当前时间 获取上个小时的时间截范围
        $user_model = new \app\index\model\User();
        $browseRecords_model = new BrowseRecords();
        $order_model = new \app\index\model\Orders();
        $creditSource_model = new \app\index\model\CreditSource();
        $order_model = new \app\index\model\Orders();
        $advanced_model = new \app\index\model\Advanced();
        $honorlog_model=new \app\index\model\HonorLog();
        $learningpowerlog_model=new \app\index\model\LearningPowerLog();
        $dedicationLog_model = new \app\index\model\DedicationLog();

        $num=0;
        $hours=date('H')-1;
        $begin = strtotime(date('Y-m-d').$hours.':00:00');
        $end = strtotime(date('Y-m-d').$hours.':59:59');

        list($beginMonth,$endMonth) = Time::month();
        for($i=$beginMonth;$i<$begin;$i=$i+3600){
            if(Db::name('statistical')->where('start_time','=',$i)->count()>0){
                $res=0;
            }else{
            //1今日注册用户
            $user_new = $user_model->where(['regetime'=>['between',[$i,$i+3600]]])->count();
            //2今日认证用户
            $user_face=    $user_model->get_face_time_count($i,$i+3600);
            // 3本日访问量
            $browse = $browseRecords_model->where(['addtime'=>['between',[$i,$i+3600]]])->count();
            //4今日系统学分产出
            $credit=$creditSource_model->get_score__time_sum($i,$i+3600,1,'>');
            //6今日打赏学分产出
            $credit_d=$creditSource_model->get_score__time_sum($i,$i+3600,2,'<');
            //7 今日收入
            $money_c = $order_model->where(['pay_type'=>['in',("2,5,6")],'status'=>['neq',1],'paytime'=>['between',[$i,$i+3600]]])->sum('value');
            // 8今日学分收入
            $credit_c = $order_model->where(['pay_type'=>['in',("1")],'status'=>['neq',1],'paytime'=>['between',[$i,$i+3600]]])->sum('value');
            $honor=$honorlog_model->where(['addtime'=>['between',[$i,$i+3600]]])->sum('value');
            $learningpower=$learningpowerlog_model->where(['addtime'=>['between',[$i,$i+3600]]])->sum('value');
            $dedication=  $dedicationLog_model->where(['addtime'=>['between',[$i,$i+3600]]])->sum('value');
            $data = [
                'browse'=>$browse,
                'user_new'=>$user_new,
                'user_face'=>$user_face,
                'credit'=>$credit,
                'credit_d'=>$credit_d,
                'money_d'=>0,
                'credit_c'=>$credit_c,
                'money_c'=>$money_c,
                'time'=>\time(),
                'start_time'=>$i,
                'end_time'=>$i+3600,
                'honor'=>$honor,
                'learningpower'=>$learningpower,
                'dedication'=>$dedication,
                'type'=>1,

            ];

                $res=Db::name(  'statistical')->insert($data);
                $num++;
            }
            echo $num;
        }
    }

}
