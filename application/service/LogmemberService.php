<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-04
 * Time: 10:54
 */

namespace app\service;


use think\Db;

class LogMemberService
{
    /**
     * @param $uid
     * @param $original_integral
     * @param $operation_integral
     * @param string $msg
     * @param int $type
     * @param int $operation_id
     * @return bool
     */
    public static function MemberLogAdd($uid, $original_value, $operation_value, $type = 0, $operation_id = 0,$field=0)
    {
        $name=Db::name('user')->where('id',$uid)->value('name');
        $operation_name=Db::name('member')->where('uid',$operation_id)->value('username');
        $data = array(
            'uid'               => intval($uid),
            'original_value'     => intval($original_value),
            'operation_value'    => intval($operation_value),
            'field'                   => $field,
            'type'                  => intval($type),
            'operation_id'          => intval($operation_id),
            'add_time'              => time(),
            'name'=>$name,
            'operation_name' =>$operation_name
        );
        $data['new_value'] = ($data['type'] == 1) ? $data['original_value']+$data['operation_value'] : $data['original_value']-$data['operation_value'];
        $log_id = Db::name('log_member')->insertGetId($data);

        if($log_id > 0)
        {
            return true;
        }
        return false;
    }
    public static function getList($map = []) {
        $total = Db::table('log_member')->where($map)->count(1);
        $list = Db::table('log_member')->select(function($query) use($map) {
            $query->where($map)->limit(page());
        });
        return self::MemberLogListWith($total,$list);
        //return page_data($total, $list);
    }
    private static function MemberLogListWith($total,$data)
    {
        if(!empty($data) && is_array($data))
        {
            foreach($data as &$v)
            {
                if(is_array($v))
                {
                    if(isset($v['type']))
                    {
                        if($v['type']==1){
                            $v['type'] = '增加';
                        }elseif($v['type']==2){
                            $v['type'] = '减少';
                        }else{
                            $v['type'] = '未知';
                        }

                    }
                    if(isset($v['field']))
                    {
                        if($v['field']==1){
                            $v['field'] = '学分';
                        }elseif($v['field']==2){
                            $v['field'] = '贡献';
                        }elseif($v['field']==3){
                            $v['field'] = '荣誉';
                        }elseif($v['field']==4){
                            $v['field'] = '学习力';
                        }elseif($v['field']==5){
                            $v['field'] = '更改等级';
                        }elseif($v['field']==6){
                            $v['field'] = '更改星际';
                        }
                        else{
                            $v['field'] = '未知';
                        }

                    }
                }
            }
        }
       return page_data($total, $data);
    }
}
