<?php
namespace app\index\model;
use think\Model;
class Helps extends Model {
    protected $table = 'helps';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->order('addtime desc')->limit(page());
        });
        return page_data($total, $list);
    }


    public function getApiList($where,$limit) {
        $data = $this::field('id,title')->where($where)->limit($limit)->order('addtime desc')->select();
        if(!empty($data)){
            foreach($data as $key=>$value){
                $data[$key]['url'] = 'https://'.$_SERVER['SERVER_NAME'].'/wxapp/helps/helpDetaile/id/'.$value['id'];
            }
            
        }
        
        return returnjson(1000,$data,'获取成功');
    }
    
    public function getDetile($id) {
        $data = $this::where(['id'=>$id])->find();
        return $data;
    }
}