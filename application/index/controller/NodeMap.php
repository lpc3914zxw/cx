<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-02
 * Time: 11:03
 */

namespace app\index\controller;



use app\service\NodeMapService;

class NodeMap extends Base
{

    /*
 * 会员等级列表
 */
    public function index() {
        if($this->request->isAjax()){

            return NodeMapService::nodemapList();
        }
        return $this->fetch();
    }
    public function indexa() {
        $level_model = new \app\index\model\Nodemap();
        if($this->request->isAjax()){
            return $level_model->getList();
        }
        return $this->fetch();
    }
    /*
    * 编辑
    */
    public function edit($id = 0) {
        if(!empty($id))
        {
            // 条件
            $where = [
                'id'=>  intval($id)
            ];

            $ret = NodeMapService::nodemapListDefault($where);;
            $data = (empty($ret['data']) || empty($ret['data'][0])) ? [] : $ret['data'][0];

            $this->assign('info',$data);
            $this->assign('id',$id);
        }
        return $this->fetch('add');
    }
    /*
       * 编辑
       */
    public function add() {
        // 开始处理
        $params = input();
        //return NodeMapService::nodemapAdd($params);
        return $this->fetch('add');
    }
    public function save() {
        // 开始处理
        $params = input();
        //var_dump($params);exit;
        return NodeMapService::nodemapAdd($params);
    }
    public function del() {
        // 开始处理
        $params = input();
        //var_dump($params);exit;
        return NodeMapService::nodemapAdd($params);
    }
}
