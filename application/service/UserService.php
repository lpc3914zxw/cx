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
    /**
     * 获取用户登录信息
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-02-27
     * @desc    description
     */
    public static function LoginUserInfo()
    {
        // 参数
        $params = input();
        //var_dump($params);exit;
        // 用户数据处理
        $user = null;


            if(!empty($params['token']))
            {
                $user = self::UserTokenData($params['token']);
            }


        return $user;
    }
    /**
     * 获取用户token用户数据
     * @author   Devil
     * @blog     http://gong.gg/
     * @version  1.0.0
     * @datetime 2019-08-18T19:01:59+0800
     * @desc     description
     * @param    [string]                   $token [用户token]
     */
    private static function UserTokenData($token)
    {
        $user = cache(config('cx.cache_user_info').$token);
        if($user !== null && isset($user['id']))
        {
            return $user;
        }

        // 数据库校验
        return self::AppUserInfoHandle(null, 'token', $token);
    }
    /**
     * app用户信息
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2018-11-06
     * @desc    description
     * @param   [int]             $user_id          [指定用户id]
     * @param   [string]          $where_field      [字段名称]
     * @param   [string]          $where_value      [字段值]
     * @param   [array]           $user             [用户信息]
     */
    public static function AppUserInfoHandle($user_id = null, $where_field = null, $where_value = null, $user = [])
    {
        // 获取用户信息
        $field = 'id,name,tel,headimg,level';
        if(!empty($user_id))
        {
            $user = self::UserInfo('id', $user_id, $field);
        } elseif(!empty($where_field) && !empty($where_value) && empty($user))
        {
            $user = self::UserInfo($where_field, $where_value, $field);
        }

        if(!empty($user))
        {
            // 用户信息处理
            $user = self::GetUserViewInfo(0, $user);

            // 基础处理
            if(isset($user['id']))
            {
                // token生成并存储缓存
                if(!empty($user['tel']))
                {
                    //$user['token'] =  md5(time() . rand(111111, 999999));
                    cache(config('cx.cache_user_info').$where_value, $user);

                    // 非token数据库校验，则重新生成token更新到数据库
                    if($where_field != 'token')
                    {
                        Db::name('User')->where(['id'=>$user['id']])->update(['token'=>$user['token']]);
                    }
                }

            }
        }

        return $user;
    }
    /**
     * 根据字段获取用户信息
     * @author   Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2019-01-25
     * @desc    description
     * @param   [string]          $where_field      [字段名称]
     * @param   [string]          $where_value      [字段值]
     * @param   [string]          $field            [指定字段]
     */
    public static function UserInfo($where_field, $where_value, $field = '*')
    {
        if(empty($where_field) || empty($where_value))
        {
            return '';
        }

        return Db::name('User')->where([$where_field=>$where_value])->field($field)->find();
    }
}
