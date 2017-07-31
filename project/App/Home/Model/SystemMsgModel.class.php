<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-09-13
 * Time: 16:59
 */

namespace Home\Model;


class SystemMsgModel
{
    public static function addMsg($from_name,$msg,$pid=0,$sid=0,$type=0)
    {
        $data['partner_id'] = $pid;
        $data['shop_id'] = $sid;
        $data['msg'] = $msg;
        $data['type'] = $type;
        $data['from_name'] = $from_name;
        $data['create_time'] = time();
        M('system_msg')->add($data);
    }
}