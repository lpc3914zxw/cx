<?php
// +----------------------------------------------------------------------
// | 功能：
// +----------------------------------------------------------------------
// | 作者: 
// +----------------------------------------------------------------------
// | 日期：
// +----------------------------------------------------------------------
namespace app\index\model;
use think\Model;

/**
 * 学财商
 * 进阶
 * Class Advanced
 * @package app\index\model
 */
class Advanced extends Model{
    protected $table = 'advanced';

    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        $opentj = '无条件';
        foreach ($list as $k=>$val) {
            $pay_types = explode(',',$val['pay_type']);
            $pay_typeText = '';
            foreach ($pay_types as $value) {
                if($value == 1) {
                    $pay_typeText.= '学分兑换'.',';
                }else if($value == 2) {
                    $pay_typeText.= '支付宝支付'.',';
                }else{
                    $pay_typeText.= "微信支付";
                }
            }
            $list[$k]['pay_type'] = $pay_typeText;
            switch ($val['open_tj']) {
                case 0:
                    $opentj = '无条件';
                    break;
                case 1:
                    $opentj = '一星以上开放';
                    break;
                case 2 :
                    $opentj = '二星以上开放';
                    break;
                case 3:
                    $opentj = '三星以上开放';
                    break;
                case 4:
                    $opentj = '四星以上开放';
                    break;
                default:
                    $opentj = '发生错误';
                    break;
            }
            $list[$k]['open_tj'] = $opentj;
        }
        return page_data($total, $list);
    }
}
