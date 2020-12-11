<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-11
 * Time: 10:06
 */

namespace app\index\controller;


use app\service\ConfigMailService;
use app\service\ConfigService;

class Config extends Base
{
    /***
     *分下配置
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
    /***
     *邮箱配置
     * @return mixed
     */
    public function mail() {
        if($this->request->isPost()) {
            $params = $this->request->param();

            $data= ConfigMailService::ConfigSave($params);
            if($data['code']==0){
                $this->success('保存成功');
            }$this->error($data['msg']);
        }
        $data = ConfigMailService::ConfigList();
        // 用户注册类型列表
        $this->assign('common_weeks_list', lang('common_weeks_list'));
        $this->assign('data',$data);
        $this->assign('common_is_text_list', lang('common_is_text_list'));
        return $this->fetch();
    }

    /**
     * [EmailTest 邮件测试]
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  0.0.1
     * @datetime 2017-03-10T15:30:10+0800
     */
    public function EmailTest()
    {
        // 验证码公共基础参数
        $verify_param = array(
            'expire_time' => MyCMail('common_verify_expire_time'),
            'time_interval'	=>	MyCMail('common_verify_time_interval'),
        );

        $obj = new \base\Email($verify_param);
        //var_dump($this->data_);exit;
        $email_param = array(
            'email'		=>	input('email'),
            'content'	=>	'邮件配置-发送测试内容',
            'title'		=>	MyC('home_site_name').' - '.'test',
        );
        // 发送
        if($obj->SendHtml($email_param))
        {
            return DataReturn('发送成功',1);
        }
        return DataReturn('发送失败'.'['.$obj->error.']', -100);
    }
}
