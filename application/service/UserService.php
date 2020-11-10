<?php
/**
 * Created by PhpStorm.
 * User: lupengcheng
 * Date: 2020-11-04
 * Time: 11:44
 */

namespace app\service;


use think\Db;

class UserService
{
    public static function memberPassword(){
        if(empty($pay_passwordr)){
           // $this->error('充值密码不能为空');
            return DataReturn('充值密码不能为空', -100);
        }
        $member = Db::name('member')->where('uid',$partner['uid'])->find();
        $new_pwd = splice_pwd ($pay_passwordr, $member ['pay_salt'] );
        if($member['pay_password']!=$new_pwd){
            $num = (int)$member['error_num']+1;

            Db::name('member')->where('uid',$partner['uid'])->update(['error_num'=>$num]);

        }else{
            Db::name('member')->where('uid',$partner['uid'])->update(['error_num'=>0]);
        }
    }
    public static function userEveryValue($uid){
      return  Db::name('user')->field('score,dedication_value,learning_power,honor_value')->where('id',$uid)->find();
    }
    public static function userSetincValue($uid,$field,$value){
      return  Db::name('user')->where('id',$uid)->setInc($field,$value);
    }
    public static function userSetdecValue($uid,$field,$value){
        return  Db::name('user')->where('id',$uid)->setDec($field,$value);
    }
    public static function userFieldsOne($field,$uid){
        return  Db::name('user')->field($field)->where('id',$uid)->find();
    }
    public static function userByLevel($uid){
        return  Db::name('user')->where('id',$uid)->value('level');
    }

    public static function userUpdateOneById($field,$uid,$date){
        return  Db::name('user')->where('id',$uid)->update([$field=>$date]);
    }
}
