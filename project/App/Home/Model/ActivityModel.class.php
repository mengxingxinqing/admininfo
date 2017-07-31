<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016/4/1
 * Time: 14:14
 */

namespace Home\Model;


use Home\Service\BonusService;
use Partner\Service\UserService;
use Think\Log;

class ActivityModel
{
    static $BEFORE_TYPE = 1;
    static $BEFORE_ORDER = 1;
    static $AFTER_ORDER = 2;
    static $SELECT_ORDER = 3;
    public function getListBeforeOrder($pid,$sid,$uid,$ext)
    {
        $sql = "select a.*,t.use_method,t.use_type from sm_activity_shop s left join sm_activity_type t on t.id=s.type left join sm_activity a on s.act_id=a.id where t.use_type<=".self::$AFTER_ORDER." and s.status=0 and s.shop_id=".$sid." order by t.sort desc";
        // echo $sql;die;
        $list = M()->query($sql);
        $res = array();
        foreach ($list as $item)
        {
            $item['uid'] = $uid;
            $item['pid'] = $pid;
            $item['sid'] = $sid;
            $item['time'] = self::$BEFORE_ORDER;
            $item['ext'] = $ext;

            if($tmp = self::$item['use_method']($item)){
                $res[] = $tmp;
            }
        }
        return $res;
    }

    private function showinfo($item){
        $tmp['id'] = $item['id'];
        $tmp['type'] = 'show';
        $tmp['name'] = $item['name'];
        return $tmp;
    }

    public function getListAfterOrder($oid)
    {
//        \Think\Log::write("执行getListAfterOrder  oid=".$oid);
        $info = M("order")->where(array('id'=>$oid))->field('uid,partner_id,shop_id,total_money,pay_money,send_money')->find();
        $uid = $info['uid'];
        $pid = $info['partner_id'];
        $sid = $info['shop_id'];
        $sql = "select a.*,t.use_method from sm_activity_shop s left join sm_activity_type t on t.id=s.type left join sm_activity a on s.act_id=a.id where t.use_type=".self::$AFTER_ORDER." and s.status=0 and s.shop_id=".$sid." order by t.sort desc";
        $list = M()->query($sql);
        $res = array();
//        \Think\Log::write("执行getListAfterOrder  sql=".$sql.'  list='.json_encode($list));

        foreach ($list as $item)
        {
            $item['uid'] = $uid;
            $item['pid'] = $pid;
            $item['sid'] = $sid;
            $item['oid'] = $oid;
            $item['total_money'] = $info['total_money'];
            $item['pay_money'] = $info['pay_money'];
            $item['send_money'] = $info['send_money'];
            $item['time'] = self::$AFTER_ORDER;
            if($tmp = self::$item['use_method']($item)){
                $res[] = $tmp;
            }
        }
        return $res;
    }


    /**
     * 首单送红包
     **/
    public function process1($item)
    {
        $umodel = new UserinfoModel();
        $times = $umodel->getUserBuyTimes($item['pid'],$item['uid']);
        if($item['time'] == self::$BEFORE_ORDER && $times == 0){
            return self::showinfo($item);
        }
        $tmp['type'] = 'dec';
        $tmp['id'] = $item['id'];
        $tmp['name'] = $item['name'];
        $ser = new BonusService();
        if($times == 0){
            \Think\Log::write("首单送红包  ".json_encode($item). '  uid='.$item["uid"].'  pid='.$item["pid"]);
            $ser->sendBonus($item['uid'],$item['pid'],"首次下单送上红包一枚","红包有期限，尽快使用吧",$item['param2'],$item['param1']);
        }
        return false;
    }


    /** 首单送礼品
     * @param $item
     * @return bool
     */
    public function process2($item)
    {
        $tmp['id'] = $item['id'];
        $tmp['type'] = 'show';
        $tmp['name'] = $item['name'];
        $om = new OrderModel();
        $num = $om->getUserOrderNum($item['uid']);
        if($num == 0){
            return $tmp;
        }
        return false;
    }

    /** 满减
     * @param $item
     * @return bool
     */
    public function process3($item){
        //处理多个满减覆盖的问题

        $tmp['type'] = 'dec';
        $tmp['id'] = $item['id'];
        $tmp['name'] = $item['name'];
        $om = new OrderModel();
        $money = $om->getShopcartMoney($item['uid'],$item['sid']);
        $m = $item['param1'];
        $j = $item['param2'];

        $sql = "select max(CAST(a.`param1` as SIGNED)) as money  from sm_activity_shop s LEFT join sm_activity a on a.id=s.act_id WHERE s.shop_id=".$item['sid']." and s.type=".$item['type']." and s.status=0 and CAST(a.`param1` as SIGNED)<=".$money;
        $info = M()->query($sql);


        if($info[0]['money'] != $m){
            return false;
        }

        if($money>=$m){
            $tmp['money'] = sprintf("%.2f", $j);
            return $tmp;
        }
        return false;
    }

    /** 满送红包
     * @param $item
     * @return bool
     */
    public function process4($item)
    {
        \Think\Log::write("执行满送红包 item = ".json_encode($item));
        $tmp['id'] = $item['id'];
        $tmp['name'] = $item['name'];
        $m = $item['param1'];
        $n = $item['param2'];
        $d = $item['param3'];
        $om = new OrderModel();
        if($item['time'] == self::$BEFORE_ORDER){
            $money = $om->getShopcartMoney($item['uid'],$item['sid']);
            if($money<$m) {
                return false;
            }
            $tmp['type'] = 'show';
            return $tmp;
        }else{
            if($item['total_money'] < $m){
                return false;
            }
            \Think\Log::write("满送红包  ".json_encode($item). '  uid='.$item["uid"].'  pid='.$item["pid"]);
            //处理邀请送红包
            $userSer = new UserService();
            $userSer->sendBonus($item['uid'],$n,'',$d,$tmp['name']);
        }
        return false;
    }

    public function process6($item)
    {
        $tmp['id'] = $item['id'];
        $tmp['name'] = $item['name'];
        $tmp['type'] = 'show';
        $m = $item['param1'];
        $om = new OrderModel();
        $num = $om->getShopcartMoney($item['uid'],$item['sid']);
        if($num>=$m){
            return $tmp;
        }
    }
    //满减配送费
    public function process7($item)
    {

        $tmp['id'] = $item['id'];
        $tmp['name'] = $item['name'];
        $m = $item['param1'];
        if($item['time'] == self::$BEFORE_ORDER){
            if(empty($item['ext']['send_money'])){
                return null;
            }
            $tmp['money'] = $item['ext']['send_money'];
            $tmp['type'] = 'dec';
            $om = new OrderModel();
            $num = $om->getShopcartMoney($item['uid'],$item['sid']);
            if($num>=$m){
                return $tmp;
            }
        }else{
            if(empty($item['send_money'])){
                return null;
            }
            if($item['total_money']>=$m){
                $tmp['money'] = sprintf("%.2f", $item['send_money']);
                return $tmp;
            }
        }
    }

    //获得现在的活动列表
    public function getNowActivity($partner_id,$shop_id){
        $sql = "SELECT act.name AS name FROM sm_activity_shop act_s LEFT JOIN sm_activity act ON act_s.act_id=act.id WHERE act_s.partner_id=".$partner_id.' AND act_s.shop_id='.$shop_id.' AND act_s.status=0';
        $res = M()->query($sql);
        return $res;
    }

}