<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-27
 * Time: 18:08
 */

namespace Home\Service;


use Api\Model\UserModel;
use Base\Service\WechatHelper;
use Home\Model\ShopModel;
use Home\Model\UserinfoModel;

class NotifyService
{
    public function process($id,$type,$append=''){
        $service = new OrderService();
        $info = $service->getOrderById($id);
        if($info['is_pay'] == 0){
            //1.订单设置为已支付
            $service->setPay($id);


            //余额支付
            if($type == 'recharge'){
                $userService = new UserService();
                $userService->rechargeDec($id);
            }

            //设置自动接单
            $service->autoProcessOrder($info['shop_id'],$id);
            //如果是支付到系统 ，记录对应系统的openid
            if(!empty($append['openid']) ){
                $service->setOpenid($id,$append['openid']);
            }

            $uid = $info['uid'];
            $pid = $info['partner_id'];
            $sid = $info['shop_id'];
            //2.保存首次下单信息
            $buytime = $service->getUserBuyTimes($uid,$pid);
            if(empty($buytime) || $buytime == 0){
                $fdata['first_order_id'] = $id;
                $fdata['first_time'] = $info['create_time'];
                M('user_info')->where(array('uid'=>$info['uid'],'partner_id'=>$info['partner_id']))->save($fdata);
                //处理邀请送红包
                $bser = new BonusService();
                $bser->sendInvBonus($uid,MARKET_GET_ORDER);
            }
            //3.统计  最后一次购时间
            M('user_info')->where(array('uid'=>$uid,'partner_id'=>$pid))->save(array('last_time'=>$info['create_time']));
            //购买次数累加
            M('user_info')->where(array('uid'=>$uid,'partner_id'=>$pid))->setInc('buy_times');
            //购买金额累加
            M('user_info')->where(array('uid'=>$uid,'partner_id'=>$pid))->setInc('buy_moneys',$info['pay_money']);

            //4.红包设置使用
            if($info['bonus_id']){
                $bonus_data = array(
                    'used_time' => time(),
                    'order_id' => $id,
                    'status' => 1,

                );
                M('bonus')->where(array('id'=>$info['bonus_id']))->save($bonus_data);
            }
            //5.统计购买单数
            $num = M('user_info')->where(array('uid'=>$uid,'partner_id'=>$pid))->getField('buy_times');
            M('order')->where(array('id'=>$id))->save(array('user_buytimes'=>$num));
            //6.发布新消息

            // publishOrder($sid,$id);

            $is_base = M('order')->where(array('id'=>$id))->getField('is_base');
            if($is_base){
                //有子订单
                $chilid_order_list = M('order')->where(array('base_id'=>$id))->select();
                foreach($chilid_order_list as $chilid_order){
                    publishOrder($sid,$chilid_order['id']);
                    M('order')->where(array('id'=>$chilid_order['id']))->save(array('user_buytimes'=>$num)); 
                }
            }else{
                publishOrder($sid,$id);
            }

            //将订单发送到待处理
            self::addOrderToQueue($pid,$sid,$id);


            //7.扣费
            $moneySer = new MoneyService();
            $orderMoney = (float)($info['pay_money']-$info['send_money']);
            $dec = $moneySer->koufei($pid,$sid,$info['id'],$orderMoney);
            //判断 系统充值
            if($info['pay_to'] == PAYTO_SYSTEM && $type=='wx'){
                $moneySer->chongzhi($id,$pid,$info['pay_money'],PAY_TYPE_ORDER,$dec);
            }


            //8.商户自定义回调函数，目前放心早餐用
            $custom = M('custom_service')->where(array('partner_id'=>$info['partner_id']))->find();
            if(!empty($custom)){
                $custom_service = new CustomService();
                $custom_service->$custom['function_name']($id);
            }

            //9.给店长发送消息模板
            self::sendNewOrderTpl($pid,$sid,$info['sqnum']);

        }
    }


    private function addOrderToQueue($pid,$sid,$oid){
        //缓存增加待处理订单
        $is_pcclient = M('partner_conf')->where(array('partner_id'=>$pid))->getField("is_pcclient");
        if($is_pcclient){
            $unread = S("UnReadList_".$sid);
            if(empty($unread)) $unread = array();
            $unread[] = $oid;
            S("UnReadList_".$sid,$unread);
        }
        $unread = S("TelUnReadList_".$sid);
        if(empty($unread)) $unread = array();
        $unread[] = $oid;
        S("TelUnReadList_".$sid,$unread);
    }

    public function wxafter($id){

    }

    public function processPayback($id)
    {
        $info = M('partner_payorder')->find($id);
        if($info['status'] == 0){
            M('partner_payorder')->where(array('id'=>$id))->save(array('status'=>1));
            $moneySer = new MoneyService();
            $moneySer->chongzhi($id,$info['partner_id'],$info['money'],PAY_TYPE_PAY);
        }
    }

    public function shoppay($id)
    {
        $info = M("shop_pay")->where(array('id'=>$id))->field('partner_id,shop_id,is_pay,pay_money,order_num')->find();
        if($info['is_pay'] == 0){
            $data['is_pay'] = 1;
            $data['pay_time'] = time();
            M("shop_pay")->where(array('id'=>$id))->save($data);
            self::sendShoppayTpl($info['partner_id'],$info['shop_id'],$info['order_num'],$info['pay_money']);
        }
    }

    private function sendShoppayTpl($pid, $sid,$order_num,$money)
    {
        $data['first'] = '到店付新订单';
        $data['keyword1'] = "订单号：".$order_num;
        $data['keyword2'] = "支付金额：".$money;
        $data['keyword3'] = date("Y-m-d H:i",time());
        $data['remark'] = '';
        $m = new UserModel();
        $tplid = $m->getTplId($pid,TPL_USERASK);
        $helper = WechatHelper::get($pid);
        $shopModel = new ShopModel();
        $uid = $shopModel->shopAdminUid($sid);
        if(!empty($uid)){
            $userModel = new UserinfoModel();
            $openid = $userModel->getOpenid($uid);
            $res = $helper->sendtpl($tplid,$openid,'',$data);
        }
    }

    public function sendNewOrderTpl($pid, $sid, $order_num)
    {
        $data['first'] = '您有一个新订单【'.$order_num.'】，请尽快去app或后台接单～';
        $data['keyword1'] = "订单号：".$order_num;
        $data['keyword2'] = date("Y-m-d H:i",time());
        $data['remark'] = '';
        $m = new UserModel();
        $tplid = $m->getTplId($pid,TPL_PROCESSORDER);
        $helper = WechatHelper::get($pid);
        $shopModel = new ShopModel();
        $uid = $shopModel->shopAdminUid($sid);
        if(!empty($uid)){
            $userModel = new UserinfoModel();
            $openid = $userModel->getOpenid($uid);
            $res = $helper->sendtpl($tplid,$openid,'',$data);
        }
    }


    public function processUserRecharge($id){      
        $res = M('user_recharge_log')->where(array('id'=>$id))->find();
        if($res['status'] == 0){
            if($res['bonus_money'] > 0 && $res['bonus_num'] > 0){
                $time = time();
                $end_time = $time+86400*$res['bonus_param2'];
                for($i=0;$i<$res['bonus_num'];$i++){
                    $data['partner_id'] = $res['partner_id'];
                    $data['shop_id'] = $res['shop_id'];
                    $data['type'] = 5;
                    $data['uid'] = $res['uid'];
                    $data['money'] = $res['bonus_money'];
                    $data['param'] = $res['bonus_param1'];
                    $data['end_time'] = $end_time;
                    $data['create_time'] = $time;
                    M('bonus')->add($data);
                }
                self::sendRechargeBonusMsg($res['uid'],$res['partner_id'],$res['bonus_param2'],$res['bonus_money'],$res['bonus_num']);
            }

            M('user_recharge_log')->where(array('id'=>$id))->save(array('status'=>1));
            $recharge_money = $res['money'] + $res['giveaway_money'];
            M('user_info')->where(array('uid'=>$res['uid']))->setInc('account_money',$recharge_money);
            if($res['qrcode_id'] > 0){
                M('user_recharge_qrcode')->where(array('id'=>$res['qrcode_id']))->setInc('user_num');
                M('user_recharge_qrcode')->where(array('id'=>$res['qrcode_id']))->setInc('recharge_money',$res['money']);
            }
        }
    }

    public function sendRechargeBonusMsg($uid,$pid,$lastdate,$money,$bonus_num){
        $pser = new PartnerService();
        $pname = $pser->getPartnerName($pid);
        $user = new UserService();
        $openid = $user->getOpenid($uid);

        $data['first'] =  '"'.$pname.'"送您'.$bonus_num.'个红包';
        $data['keyword1'] = '"'.$pname.'"专属红包（'.$bonus_num.'个）';
       
        if($lastdate == 1){
            $extend = '(有效期24小时)';
        }else{
            $extend = '(有效期'.$lastdate.'天)';
        }

        if($res['bonus_num'] > 1){
            $extend = '('.$bonus_num.'个)'.$extend;
        }

        $data['keyword2'] = $money.'元'.$extend;
        $data['keyword3'] = date('Y-m-d',time());
        $tpl = $pser->getTplId($pid,TPL_BONUS);
        if(empty($tpl)) return;
        $helper = WechatHelper::get($pid);
        $res = $helper->sendTpl($tpl,$openid,"",$data);
    }
}