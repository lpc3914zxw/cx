<?php


namespace app\index\controller;

use app\index\model\PetersLog;
use app\index\model\PetersSet;
use app\service\RegionService;

/**
 * 地区管理
 * Class Xcscourse
 * @package app\index\controller
 */
class Region extends Base
{
        /*
         * 申请列表
         */
    public function index() {

        $res=RegionService::RegionItems();
        $assign = array(
            'data'=>$res
        );
        $this->assign($assign);
        return $this->fetch();
    }
    public function rule_list(){
        $auth = new \think\Auth();
        $request = Request::instance();
        $m = $request->module();
        $c = $request->controller();
        $a = $request->action();
        $rule_name = $m.'/'.$c.'/'.$a;

        $result = $auth->check($rule_name,$this->partner['uid']);
        if(!$result){
            $this->error('您没有权限访问');
        }
        $menu_model = new Menu();
        $data = $menu_model->getTreeData('tree','id','name');

        $assign = array(
            'data'=>$data
        );
        $this->assign($assign);
        return $this->fetch();
    }
    /**
     * [GetNodeSon 获取节点子列表]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2016-12-25T15:19:45+0800
     */
    public function GetNodeSon()
    {

        // 开始操作
        return RegionService::RegionNodeSon(input());
    }

}
