<?php
namespace app\index\model;
use app\wxapp\model\MessageReadLog;
use think\Model;
class Message extends Model {
    protected $table = 'message';
    public function getList($map = []) {
        $total = $this::where($map)->count(1);
        $list = $this::all(function($query) use($map) {
            $query->where($map)->order('addtime desc')->limit(page());
        });
        foreach ($list as $k=>$val) {
            if($val['send_time'] == 0) {
                $list[$k]['send_time'] = '无';
            }else{
                $list[$k]['send_time'] = date('Y-m-d H:i:s',$val['send_time']);
            }
        }
        return page_data($total, $list);
    }

    /*
     * 获取未读系统消息
     * @param array $where
     */
    public function getSysMsg($uid = 0) {
        $msgReadLog = new MessageReadLog();
        $readCount = $msgReadLog->where(['uid'=>$uid,'type'=>1])->count();
        $total = $this::where(['type'=>1,'is_send'=>1,'uid'=>0])->count();
        $totalNum = ($total - $readCount);;
        if($totalNum == 0) {
            $newMsgTime = '';
        }else{
            $newMsg = $this::field('send_time')->where(['type'=>1,'is_send'=>1,'uid'=>0])->order('send_time desc')->find();
            if($newMsg) {
                $newMsgTime = date('m月d日',$newMsg['send_time']);
            }else{
                $newMsgTime = '';
            }
        }
        return ['totalNum'=>$totalNum,'newMsgTime'=>$newMsgTime];
    }

    /*
     * 获取未读邀请信息
     * @param array $where
     */
    public function getInvMsg($uid = 0) {
        $msgReadLog = new MessageReadLog();
        $readCount = $msgReadLog->where(['uid'=>$uid,'type'=>2])->count();
        $total = $this::where(['type'=>2,'uid'=>$uid])->count();
        $totalNum = ($total - $readCount);
        if($totalNum == 0) {
            $newMsgTime = '';
        }else{
            $newMsg = $this::field('addtime')->where(['type'=>2,'uid'=>$uid])->order('addtime desc')->find();
            if($newMsg) {
                $newMsgTime = date('m月d日',$newMsg['addtime']);
            }else{
                $newMsgTime = '';
            }
        }
        return ['totalNum'=>$totalNum,'newMsgTime'=>$newMsgTime];

    }

    /*
     * 系统消息
     */
    public function getSystemMsg($uid = 0) {
        $msgReadLog = new MessageReadLog();
        $readCount = $msgReadLog->where(['uid'=>$uid,'type'=>3])->count();
        $total = $this::where(['type'=>3,'uid'=>$uid])->count();
        $totalNum = ($total - $readCount);
        if($totalNum == 0) {
            $newMsgTime = '';
        }else{
            $newMsg = $this::field('send_time')->where(['type'=>3,'uid'=>$uid])->order('send_time desc')->find();
            if($newMsg) {
                $newMsgTime = date('m月d日',$newMsg['send_time']);
            }else{
                $newMsgTime = '';
            }
        }
        return ['totalNum'=>$totalNum,'newMsgTime'=>$newMsgTime];
    }
}