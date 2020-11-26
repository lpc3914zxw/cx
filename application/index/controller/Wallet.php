<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 11/16/20
 * Time: 10:14 AM
 */

namespace app\index\controller;


use app\service\BaseService;
use app\service\SalelogService;
use app\service\StatisticalService;
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
            return SalelogService::SaleLogList($where);
        }
        return $this->fetch();
    }

}
