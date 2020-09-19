<?php
namespace app\index\controller;

use app\index\controller\AdminBase;
use think\Url;
class Orders extends AdminBase
{
    /*
     * 晋级
     * @param int $id
     */
    public function check($id = 0){
        $order_model = new \app\index\model\Orders();
        false !== $order_model->where('id',$id)->update(['is_pass'=>1]) && $this->success('操作成功');
        $this->error('操作失败');
    }

    /*
     * 未晋级
     * @param int $id
     */
    public function nocheck($id = 0){
        $order_model = new \app\index\model\Orders();
        false !== $order_model->where('id',$id)->update(['is_pass'=>2]) && $this->success('操作成功');
        $this->error('操作失败');
    }

    /*
     * 设置编号
     * @param int $id
     */
    public function setNumber(){
        $params = $this->request->param();
        $id = $params['order_id'];
        $number = $params['number'];
        $order_model = new \app\index\model\Orders();
        false !== $order_model->where('id',$id)->update(['number'=>$number]) && $this->success('设置成功');
        $this->error('设置失败');
    }
}
