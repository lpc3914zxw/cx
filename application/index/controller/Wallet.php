<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 11/16/20
 * Time: 10:14 AM
 */

namespace app\index\controller;


use app\service\BaseService;
use app\service\CashService;
use app\service\SalelogService;
use app\service\StatisticalService;
use app\service\UserOverlogService;
use think\Db;

class Wallet extends Base
{
    /**钱包统计
     * @param array $params
     * @return array|mixed
     */
        public function index($params = [])
        {
            $ret = BaseService::BaseConfig(false);
            if($this->request->isAjax()){
                if($ret['code'] == 0){
                    $data=StatisticalService::StatisticalData();
                    return DataReturn('处理成功', 0, $data);
                }else{
                    return DataReturn($ret['msg'], -100);
                }
            }
            return $this->fetch();
        }

    /**钱包列表
     * @param array $params
     * @return array|mixed
     */
    public function wallet($params = [])
    {
        if($this->request->isAjax()){
            $where = [];
            /*$where['add_time'] = [
                [ '>=', self::$yesterday_time_start],
                [ '<=', self::$yesterday_time_end],
            ];*/
            return BaseService::WalletList($where);
        }
        return $this->fetch();
    }
    /**提现列表
     * @param array $params
     * @return array|mixed
     */
    public function cash($params = [])
    {
        if($this->request->isAjax()){
            $params= $this->data_get;

            $where=BaseService::CashWhere($params);

            return BaseService::CashList($where);
        }
        return $this->fetch();
    }
    /**钱包明细列表
     * @param array $params
     * @return array|mixed
     */
    public function walletlog($params = [])
    {
        if($this->request->isAjax()){
            $where = [];
            if(!empty(input('business_type'))){
                $business_type = input('business_type');
                $where = ['business_type'=>$business_type];
            }
            return BaseService::WalletlogList($where);
        }
        return $this->fetch();
    }
    /**钱包明细列表
     * @param array $params
     * @return array|mixed
     */
    public function sale($params = [])
    {
        if($this->request->isAjax()){
            $where = [];
            if(!empty(input('status'))){
                $status = input('status');
                if($status==4){
                    $where = ['type'=>array('in','3,4')];
                }else{
                    $where = ['type'=>$status];
                }

            }

            return SalelogService::SaleLogList($where);
        }
        return $this->fetch();
    }
    /**钱包明细列表
     * @param array $params
     * @return array|mixed
     */
    public function cashcheck($id = 0)
    {
        if($this->request->post()){
            $param=$this->data_post;
            if($param['wallet_type']==0){
                $ret=CashService::CashAudit($param);
                if($ret['code'] == 0){
                    return DataReturn('处理成功', 0);
                }else{
                    return DataReturn($ret['msg'], -100);
                }
            }elseif ($param['wallet_type']==1){
                $ret=CashService::CashAuditTeam($param);
                if($ret['code'] == 0){
                    return DataReturn('处理成功', 0);
                }else{
                    return DataReturn($ret['msg'], -100);
                }
            }

        }
                $one=BaseService::CashOne($id);
        $fee=MyC('course_scale_fee');
        $money=$one['money']*intval(100-$fee)/100;
        $money= PriceNumberFormat($money);
        $this->assign('money',$money);
        $this->assign('one',BaseService::CashOne($id));
        $this->assign('id',$id);
        return $this->fetch('cashcheck');
    }
    /**钱包明细列表
     * @param array $params
     * @return array|mixed
     */
    public function overlogcheck($id = 0)
    {
        if($this->request->post()){
            $param=$this->data_post;
            $ret=UserOverlogService::overlogAudit($param);
            if($ret['code'] == 0){
                return DataReturn('处理成功', 0);
            }else{
                return DataReturn($ret['msg'], -100);
            }
        }

        $one=Db::name('user_overlog')->where('id','=',$id)->find();
        $data_url=Db::name('user_overlog_img')->where('user_overlog_id','=',$id)->column('url');
        $this->assign('one',$one);
        $this->assign('data_url',$data_url);
        $this->assign('id',$id);
        return $this->fetch('overlogcheck');
    }
}
