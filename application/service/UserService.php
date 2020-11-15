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
    public static function memberPassword($partner){
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

    /**
     * 获取用户展示信息
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-05-05
     * @desc    description
     * @param   [int]          $user_id     [用户id]
     * @param   [array]        $user        [指定用户信息]
     * @param   [boolean]      $is_privacy  [是否隐私处理展示用户名]
     */
    public static function GetUserViewInfo($user_id, $user = [], $is_privacy = false)
    {
        // 是否指定用户信息
        if(empty($user) && !empty($user_id))
        {
            $user = Db::name('user')->field('name,tel,realname,headimg,identityid')->find($user_id);
        }

        // 开始处理用户信息
        if(!empty($user))
        {
            $user['user_name_view'] = isset($user['username']) ? $user['username'] : '';
            if(empty($user['user_name_view']) && isset($user['nickname']))
            {
                $user['user_name_view'] = $user['nickname'];
            }
            if(empty($user['user_name_view']) && isset($user['mobile']))
            {
                $user['user_name_view'] = $user['mobile'];
            }
            if(empty($user['user_name_view']) && isset($user['email']))
            {
                $user['user_name_view'] = $user['email'];
            }

            // 处理展示用户
            if($is_privacy === true && !empty($user['user_name_view']))
            {
                $user['user_name_view'] = substr($user['user_name_view'], 0, 3).'***'.substr($user['user_name_view'], -3);
            }
        }

        return $user;
    }
}
