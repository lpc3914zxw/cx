<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-04
 * Time: 15:40
 */

namespace app\service;



use think\Db;

class MemberService
{
    /***
     * @param $uid
     * @param $num 密码错误次数
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function updateMemberPasswordNum($uid,$num){
      return  Db::name('member')->where('uid',$uid)->update(['error_num'=>$num]);
    }

    /***
     * 查找后台用户详情
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function byMemberOne($field,$uid){
        return  Db::name('member')->where($field,$uid)->find();
    }


}
