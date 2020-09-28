<?php
namespace app\index\controller;
use app\index\controller\AdminBase;
use app\index\model\AuthGroup;
use think\Session;
use app\index\model\Menu;
/**
 * Class Index
 * @author Steed
 * @package app\index\controller
 */
class FeeddbackCategory extends AdminBase {

    /**
     * 添加权限
     */
    public function add(){
        $data = input('post.');
        unset($data['id']);
        $menu_model = new Menu();
        $result = $menu_model->insert($data);
        if ($result) {
            $this->success('添加成功','/index/power/rule_list');
        }else{
            $this->error('添加失败');
        }
    }

    /**
     * 修改权限
     */
    public function edit(){
        $data=input('post.');
        $menu_model = new Menu();
        $info = ['name'=>$data['name'],'controller'=>$data['controller'],'action'=>$data['action']];
        $result = $menu_model->where(["id"=>$data['id']])->update($info);
        if ($result) {
            $this->success('修改成功','/index/power/rule_list');
        }else{
            $this->error('您没有做任何修改');
        }
    }

    /**
     * 删除权限
     */
    public function delete($id){
        $menu_model = new Menu();
        if($menu_model->where('parent_id',$id)->find()){
            $this->error('请先删除子权限');
        }
        $result = $menu_model->where('id',$id)->delete();
        if($result){
            $this->success('删除成功','/index/power/rule_list');
        }else{
            $this->error('删除失败','/index/power/rule_list');
        }
    }

    /**
     * 添加角色
     */
    public function add_group(){
        $data = input('post.');
        unset($data['id']);
        $auth_group_model = new AuthGroup();
        $auth_group_model->insert($data) && $this->success('添加成功','index/power/rule_group');
        $this->error('添加失败');
    }

    /**
     * 修改角色
     */
    public function edit_group(){
        $data = input('post.');
        $auth_group_model = new AuthGroup();
        $result = $auth_group_model->where(["id"=>$data['id']])->update(['title'=>$data['title']]);
        if ($result) {
            $this->success('修改成功','index/power/rule_group');
        }else{
            $this->error('您没有做任何修改');
        }
    }

    /**
     * 删除角色
     */
    public function delete_group($id){
        if ($id == 1) {
            $this->error('该分组不能被删除');
        }
        $map = array('id'=>$id);
        $auth_group_model = new AuthGroup();
        $auth_group_model->where($map)->delete() && $this->success('删除成功','index/power/rule_group');
        $this->error('删除失败');
    }


    /**
     * 分配权限
     */
    public function rule_distribution($id){
        $auth_group_model = new AuthGroup();
        $menu_model = new Menu();
        if($this->request->isPost()){
            $data = input('post.');
            $datas['rules'] = implode(',', $data['rule_ids']);
            $result = $auth_group_model->where(['id'=>$data['id']])->update($datas);
            if ($result) {
                $this->success('操作成功','index/power/rule_group');
            }else{
                $this->error('操作失败');
            }
        }else{
            $group_data = $auth_group_model->where(array('id'=>$id))->find();
            $group_data['rules'] = explode(',', $group_data['rules']);
            // 获取规则数据
            $rule_data = $menu_model->getTreeData('level','id','name');
            $assign=array(
                'group_data'=>$group_data,
                'rule_data'=>$rule_data
            );
            $this->assign($assign);
            return $this->fetch();
        }
    }
}
