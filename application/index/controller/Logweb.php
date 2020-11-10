<?php


namespace app\index\controller;
use app\index\controller\Base;
use app\index\model\HonorSet;
use app\service\LogMemberService;
use think\Db;


/**
 * 荣誉值设置
 * Class Xcscourse
 * @package app\index\controller
 */
class Logweb extends Base
{
        /*
         * 后台日志
         */
    public function index() {
        $logweb =new  \app\index\model\Logweb();
        if($this->request->isAjax()){
            $params = input('param.');
            $where = [];
            if(!empty($uid)){
                $where = ['h.uid'=>$uid];
            }
            if(!empty($params['type'])){
                $where['method'] = $params['type'];
            }
            if(isset($params['scoretime'])&&$params['scoretime']!=''){
                $time = explode(' - ',$params['scoretime']);
                $time1 = strtotime($time[0]);
                $time2 = strtotime($time[1]);
                $where['create_at'] = array('between',$time1.','.$time2);
            }
            if(!empty($params['name'])){
                if(!empty($params['name'])){
                    $map = array(
                        'username'=>array('like','%'.$params['name'].'%'),
                    );
                    $res=Db::name('member')->where($map)->column('uid');

                    if(!empty($res))
                    {
                        $where['uid'] = [ 'in', $res];
                    } else {
                        // 避免空条件造成无效的错觉
                        $where['uid'] = [ '=', 0];
                    }
                }
            }

            return $logweb->getList($where);
        }
        return $this->fetch('index');
    }
    public function value() {
        if($this->request->isAjax()){
            $where = [];
            return LogMemberService::getList($where);
        }
        return $this->fetch('value');
    }




}
