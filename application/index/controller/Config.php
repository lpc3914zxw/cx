<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-11
 * Time: 10:06
 */

namespace app\index\controller;


use app\service\ConfigService;

class Config extends Base
{
    /***
     *
     * @return mixed
     */
    public function index() {
        if($this->request->isPost()) {
            $params = $this->request->param();
            
           $data= ConfigService::ConfigSave($params);
            if($data['code']==0){
                $this->success('保存成功');
            }$this->error($data['msg']);
        }
        $data = ConfigService::ConfigList();
        // 用户注册类型列表
        $this->assign('common_weeks_list', lang('common_weeks_list'));
        $this->assign('data',$data);
        return $this->fetch();
    }
}
