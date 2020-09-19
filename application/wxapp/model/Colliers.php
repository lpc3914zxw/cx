<?php

namespace app\wxapp\model;

use think\Model;
use think\helper\Time;
/*
 * 贡献值
 */
class Colliers extends Model {
    protected $table = 'colliers';

    public function getList($uid = 0,$colliers_note = '') {
        $data = $this::field('id,logo,type,name,contribution,max,note,model')
            ->where(['p_id'=>0,'is_task'=>1])->order('id')->select();

        list($start, $end) = Time::today();
        $dedicationLog = new DedicationLog();
        $totalDedication = 0;   // 各类型获得的贡献值数量
        $dayDedication = 0;     // 单子获得最大贡献值
        $dayGetDedication = 0;  // 今日获得贡献值
        foreach ($data as $k=>$val) {
            $children = $this::field('id,contribution,max,p_id,type')->where('p_id',$val['id'])->select();
            $cids = [];
            $strCids = '';
            if($children) {
                foreach ($children as $value) {
                    $cids[] = $value['type'];
                    $totalDedication += $value['contribution'];
                    $dayDedication += $value['max'];
                }
              	
              	
                $sonInfo = $this::field('type')->where('p_id',$val['id'])->find();
                $data[$k]['type'] = $sonInfo['type'];
            }
         if($val['type']!=0){
                $cids[] = $val['type'];
                $totalDedication += $val['contribution'];
                $dayDedication += $val['max'];
            }
            $strCids = implode(',',$cids);
          
            $data[$k]['totalDedication'] = $totalDedication;
            $totalValue = $dedicationLog->where(['uid'=>$uid,'type'=>['in',($cids)],'addtime'=>['between',[$start,$end]]])->sum('value');
          
            if($totalValue < $totalDedication) {
                $data[$k]['is_complete'] = 0;
            }else{
                $data[$k]['is_complete'] = 1;
            }
            $dayGetDedication += $totalValue;
            $data[$k]['text'] = self::getStatus($data[$k]['type']);
        }
        $percent = round($dayGetDedication / $dayDedication,2) * 100 ."%";
        if(floatval(round($dayGetDedication / $dayDedication,2)) < 100) {
            $is_get = 0;
        }else{
            $is_get = 1;
        }
      	$dayGetDedication = round($dayGetDedication,2);
        $data = ['dayGetDedication'=>$dayGetDedication,'percent'=>$percent,'list'=>$data,'colliers_note'=>$colliers_note,'is_get'=>$is_get];
        return returnjson('1000',$data,'获取成功');
    }

    public function getStatus($type = 1) {
        $arr = [
            '1'=>'去加成','2'=>'去阅读','3'=>'去点赞','4'=>'去分享','5'=>'去反馈','6'=>'去邀请','7'=>'去购买',
            '14'=>'去阅读','15'=>'去点赞','16'=>'去分享'
        ];
        return $arr[$type];
    }
}