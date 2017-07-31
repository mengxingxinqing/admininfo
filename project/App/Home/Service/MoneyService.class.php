<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-08-10
 * Time: 15:21
 */

namespace Home\Service;


use Home\Model\OrderModel;
use Home\Model\ShopModel;
use Home\Model\SystemMsgModel;

class MoneyService
{
    /** 充值操作  包括流水充值 后台手动充值
     * @param $order_id 订单id
     * @param $pid
     * @param $order_money
     * @param $type
     * @param string $sid
     * @param int $dec
     */
    public function chongzhi($order_id,$pid,$order_money,$type,$dec=0){
        return false;
//        $pid = $order['partner_id'];
        $orderMod = new OrderModel();
        if($type == PAY_TYPE_ORDER){
            $sid = $orderMod->getOrderShopId($order_id);
        }elseif($type == PAY_TYPE_PAY){
            $sid = M('partner_payorder')->where(array('id'=>$order_id))->getField('shop_id');
        }
//        $conf = M('partner_conf')->where(array('partner_id'=>$pid))->find();
        $conf = M('shop_conf')->where(array('shop_id'=>$sid))->find();
        $money = $conf['money'];
//        M('partner_conf')->where(array('partner_id'=>$pid))->setInc('money',$order_money);
        M('shop_conf')->where(array('shop_id'=>$sid))->setInc('money',$order_money);
        //如果充值满了 转为减钱模式
        if($order_money+$money > $conf['max_money']){
            M('shop_conf')->where(array('shop_id'=>$sid))->save(array('curr_status'=>PAYTO_PARTNER));
        }

        //充值记录
        $data['partner_id'] = $pid;
        $data['order_id'] =$order_id;
        $data['shop_id'] = $sid;
        $data['order_money'] = $order_money;
        $data['money'] = $order_money-$dec;
        $data['curr_money'] = $money + $order_money;
        $data['create_time']= time();
        $data['type'] = $type;
        M("partner_paylog")->add($data);
        if($type == PAY_TYPE_ORDER){
            self::monthLog($pid,$sid,$order_money-$dec,'inc_money');
        }else if($type == PAY_TYPE_PAY){
            self::monthLog($pid,$sid,$order_money,'back_inc_money');
        }

    }

    /** 执行订单扣费
     * @param $pid  合作商户id
     * @param $sid  门店id
     * @param $oid  订单id
     * @param $orderMoney 订单金额 处理过后的
     * @param int $type 0 订单扣费  1 电话扣费
     * @return int
     */
    public function koufei($pid,$sid,$oid,$orderMoney,$type = 0)
    {
        return false;
        $m = date('m',time());

        if(!self::checkMonthLog($pid,$sid,$m)){
            //本月扣费到封顶了
            SystemMsgModel::addMsg("商户扣费系统","本商户本月扣费到顶了",$pid,$sid);
            return 0;
        }
        $conf = M('shop_conf')->where(array('shop_id'=>$sid))->find();
        $money = $conf['money'];
        $dec = 0;
        if($type == 0){ //订单扣费
            $dec = $orderMoney*$conf['rate'];
        }else if($type == 1){ //电话扣费
            $dec = $orderMoney;
        }

        M('shop_conf')->where(array('shop_id'=>$sid))->setDec('money',$dec);

        if($money-$dec < $conf['min_money']+C('SMS_LINE')){
            self::sendSms($sid);
        }

        if($money-$dec<$conf['min_money']){
            M('shop_conf')->where(array('shop_id'=>$sid))->save(array('curr_status'=>PAY_STATUS_INC));
        }
        //扣费记录
        $data['partner_id'] = $pid;
        $data['shop_id'] = $sid;
        $data['order_id'] = $oid;
        $data['money'] = $dec;
        $data['order_money'] = $orderMoney;
        $data['curr_money'] = $money-$dec;
        $data['create_time']= time();
        $data['type'] = $type;
        M("partner_declog")->add($data);
        //按月统计扣费信息
        if($type == 0){
            self::monthLog($pid,$sid,$dec,"dec_money");
        }else{
            self::monthLog($pid,$sid,$dec,"dec_phone_money");
        }
        return $dec;
    }

    /** 资金池金额过少，发送短信提醒
     * @param $sid
     */
    public function sendSms($sid)
    {
        return false;
        $status = S('sendsms_status_'.$sid);
        if(empty($status)){
            $shopModel = new ShopModel();
            $shop_name = $shopModel->getShopName($sid);
            $tel = $shopModel->getShopTel($sid);
            sendSms($tel,array($shop_name,C("SERVICE_PHONE")));
            S('sendsms_status_'.$sid,'1',DAY_SECONDS/2);
        }
    }

    /** 按月统计数据 流水扣费充值 和 流水直接充值
     * @param $pid
     * @param $sid
     * @param $money
     * @param string $field 所属字段
     * @param int $type 0 确定 1 取消
     */
    public function monthLog($pid,$sid,$money,$field ,$type=0)
    {
        return false;
        \Think\Log::write("month log    pid=".$pid.' sid='.$sid.'  money='.$money."  field=".$field.' type='.$type);
        $m = date('m',time());
        $m = (int)$m;
        $where['partner_id'] = $pid;
        $where['shop_id'] = $sid;
        $where['months'] = $m;
        $count = M('month_paylog')->where($where)->count();
        if($count == 0){
            $cinfo = M('shop_conf')->where(array('shop_id'=>$sid))->field('top_money,rate')->find();
            M('month_paylog')->add(array('partner_id'=>$pid,'shop_id'=>$sid,'months'=>$m,'money'=>0,'top_money'=>$cinfo['top_money'],'rate'=>$cinfo['rate'],'modify_time'=>time()));
        }

        \Think\Log::write("month_paylog  type=$type field=$field money=$money");
        if($type == 0){
            //如果是向系统中充钱，包括 前台订单充值，或后台手动充值，按照月份记录下来
            M('month_paylog')->where($where)->setInc($field,$money);
        }else{
            M('month_paylog')->where($where)->setDec($field,$money);
        }
    }




    public function checkMonthLog($pid,$sid,$m)
    {
        return false;
        $info = M('month_paylog')->where(array('partner_id'=>$pid,'shop_id'=>$sid,'months'=>$m))->field('dec_money,top_money')->find();
        if(empty($info)){
            return true;
        }
        if($info['dec_money']>=$info['top_money']){
            return false;
        }
        return true;
    }

    //订单扣点 返还回来
    public function giveUpOrder($oid,$pay_to){
        return false;

        $orderMod = new OrderModel();
        $sid = $orderMod->getOrderShopId($oid);
        $info = M('partner_declog')->where(array('order_id'=>$oid))->find();
        \Think\Log::write("give up order oid=".$oid.'  payto='.$pay_to.' info ='.empty($info));
        if(!empty($info)){
            if($pay_to == PAYTO_PARTNER){
                M('shop_conf')->where(array('shop_id'=>$sid))->setInc('money',$info['money']);
                M('partner_declog')->where(array('order_id'=>$oid))->save(array('status'=>1));
                self::monthLog($info['partner_id'],$sid,$info['money'],'dec_money',1);
            }else if($pay_to ==PAYTO_SYSTEM){
                $money = M('partner_paylog')->where(array('order_id'=>$oid))->getField("money");
                self::monthLog($info['partner_id'],$sid,$money,'inc_money',1);
            }
        }
    }
    //如果是从系统里退款 则商户充值要往下减
    public function refund($order_id,$pid,$money)
    {
        return false;
        \Think\Log::write("refund oid=".$order_id."  pid=".$pid.'  money='.$money);
        $orderMod = new OrderModel();
        $sid = $orderMod->getOrderShopId($order_id);
        $info = M('partner_paylog')->where(array('order_id'=>$order_id))->find();
        if(empty($info)){
            return;
        }
        M('shop_conf')->where(array('shop_id'=>$sid))->setDec('money',$info['money']);
        $conf = M('shop_conf')->where(array('shop_id'=>$sid))->find();
        if($conf['money']<$conf['min_money']){
            M('shop_conf')->where(array('shop_id'=>$sid))->save(array('curr_status'=>PAY_STATUS_INC));
        }
        M('partner_paylog')->where(array('order_id'=>$order_id))->save(array('status'=>1));
    }
}