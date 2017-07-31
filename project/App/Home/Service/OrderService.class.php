<?php
namespace Home\Service;
use Api\Model\AndroidModel;
use Base\Service\WechatService;
use Home\Model\GoodsModel;
use Home\Model\OrderModel;
use Home\Model\UserinfoModel;
use Home\Service\ActivityService;
use Home\Model\ShopModel;
use Base\Service\WechatHelper;
use Partner\Model\OrderModel AS PartnerOrderModel;


class OrderService
{
    public function saveOrder($uid,$pid,$shop_id,$partner_id,$inputData){
        $userService = new UserService();
        $res = $userService->getShopCartInfo($uid,$shop_id,0,$inputData['type']);
        if($inputData['type'] != 3){
            $activityService = new ActivityService();
            $activity_info  = $activityService->getListBeforeOrder($partner_id,$shop_id,$uid);


            //特价商品优惠
            $shopService = new ShopService();
            $goods_discount_set = $shopService->getGoodsDiscountInfo($partner_id,$shop_id);
            if($goods_discount_set['status'] == 1){
                //订单里是否存在特价商品
                $is_exist = $shopService->isExistGoodsDiscount($res['goods_info']);
                    if($goods_discount_set['share_with_other'] == 0 && $is_exist){
                        foreach($activity_info as $key=>$activity){
                            if($activity['type'] == 'dec' && $activity['id'] >0){
                                unset($activity_info[$key]);
                            }
                        }
                    }
            }
        }
        

        $orderModel = new OrderModel();
        $order_id = $orderModel->addOrder($uid,$pid,$shop_id,$res,$inputData,$activity_info);

        $goodsModel = new GoodsModel();
        $goodsModel->clearall($uid,$shop_id);
        return $order_id;
    }

    public function checkStore($uid,$pid,$sid)
    {
        $mod = new GoodsModel();
        return $mod->checkStore($uid,$pid,$sid);
    }

    public function removeShopcartNoStore($res)
    {
        $mod = new GoodsModel();
        return $mod->removeShopcartNoStore($res);
    }

    public function getNoStoreStr($res)
    {
        $mod = new GoodsModel();
        return $mod->getNoStoreStr($res);
    }


//    /** 推送订单消息
//     * @param $base_id
//     */
//    public function sendOrderTpl($base_id){
//        $orderModel = new OrderModel();
//        $detail = $orderModel->getOrderDetail($base_id);
////        \Think\Log::write(json_encode($detail));
//        $wechatService = new WechatService();
//        //推送消息
//        foreach($detail as $item){
//            //发布订阅消息
//            publishOrder($item['order']['partner_id'],$item['order']['id']);
//            $res = self::getTpl($item,$wechatService);
//            foreach($item['sender'] as $sender){
//                $openid = $sender['openid'];
//                $wechatService->sendtpl(C('ORDER_TPL'),$openid,$res['url'],$res['data']);
//            }
//            if($item['order']['partner_id'] == "15"){
//                $this->process($item['order']['id'],"6404");
//            }else if($item['order']['partner_id'] == "70"){
//                $this->process($item['order']['id'],"7874");
//            }
//        }
//        //
//    }

    /**生成消息模板
     * @param $item
     */
    public function getTpl($item,$wechatService){
        $uid = $item['order']['uid'];
        
        //判断用户是否下过单
        $is_first = M('order_first')->where(array('uid'=>$uid,'order_id'=>$item['order']['id']))->getField('id');

        if ($is_first) {
            $data['keyword2'] = $item['user']['name']."(首单)";
        }else{
            if($item['order']['partner_id'] == 15){
                $num = M('order_first')->where(array('uid'=>$uid,'partner_id'=>$item['order']['partner_id']))->getField('num');
                if(empty($num)){
                    $num = 1;
                }
                $data['keyword2'] = $item['user']['name']."(总计下单：".$num."次)";
            }else{
                $data['keyword2'] = $item['user']['name'];
            }
        }

        if(!empty($item['order']['activity_msg'])){
            $data['keyword2'] .= '【'.$item['order']['activity_msg']."】";
        }
        $data['first'] = '【'.$item['shop']['name'].'】新订单提示 用户下单';
        $data['keyword1'] = $item['order']['id'];
        
        $data['keyword3'] = $item['user']['address'];
        $data['keyword4'] = $item['user']['tel'];
        if (!empty($item['order']['bonus_money'])) {
            $data['keyword5'] = $item['order']['real_price'].'(红包已抵扣'.intval($item['order']['bonus_money']).'元)';
        }else{
            $data['keyword5'] = $item['order']['real_price'];
        }
        
        $data['remark'] = '订单创建时间为'.date('Y-m-d H:i:s',time()).' 请尽快配送，点击查看详情';
        $res['data'] = $data;

        $res['url'] = $wechatService->createBaseUrl(U('Partner/dispatchingOrder',array('id'=>$item['order']['id'])));
//        $url =$wechatModel->createBaseUrl(U('Partner/dispatchingOrder',array('shop'=>$shop_id)));
//        $res = $this->sendtpl(C('ORDER_TPL'),$v['openid'],$url,$data);
        return $res;
    }
    /** 获取订单信息
     * @param $order_id
     */
    public function getOrderInfo($order_id){
        $model = new OrderModel();
        return $model->getOrderInfo($order_id);
    }

    public function changeOrderPayTo($id)
    {
        M('order')->where(array('id'=>$id))->save(array('pay_to'=>PAYTO_SYSTEM));
        \Think\Log::write("tong-pay2  sql=".M()->getLastSql());
    }

    public function getOrderStatus($base_id){
        $model = new OrderModel();
        return $model->getOrderStatus($base_id);
    }

    public function getOrderIsPay($id){
        $model = new OrderModel();
        return $model->getOrderIsPay($id);
    }

    public function setPay($id){
        $model = new OrderModel();
        $model->setPay($id);
    }

    public function setOpenid($id,$openid){
        $model = new OrderModel();
        $model->setOpenid($id,$openid);
    }

    public function getPartnerOrder($partner_id,$status,$process_id=''){
        $model = new OrderModel();
        return $model->getPartnerOrder($partner_id,$status,$process_id);
    }

    public function DispatcherProcess($partner_id,$status,$process_id=''){
        $model = new OrderModel();
        return $model->DispatcherProcess($partner_id,$status,$process_id);
    }
    
    public function finish($id,$uid){
        $model = new OrderModel();
        return $model->updateOrder($id,$uid,2,array('finish_time'=>time()));
    }

    public function giveUp($id,$uid,$msg,$processing){
        \Think\Log::write("giveUp1");
        $model = new OrderModel();
        if (empty($processing)) {
           $order_status = $model->thisOrderStatus($id);
            \Think\Log::write("giveUp2");
            //判断是否被截单
            if ($order_status == 1) {
                return false;
            }
        }

        \Think\Log::write("giveUp3  id=".$id.'  uid='.$uid);
        $res = $model->updateOrder($id,$uid,3,array('finish_time'=>time()),$msg);
        //取消订单完毕判断是否适用红包，是否是微信支付，并且已支付
        if ($res) {
            \Think\Log::write("giveUp4");
            $findOrder = $model->findOrder($id);
            if ($findOrder['bonus_id']) {
                 $bonus =  $model->refundBonus($findOrder['bonus_id']);
            }
            \Think\Log::write("giveUp5 findorder=".json_encode($findOrder));
            if ($findOrder['pay_type']=='微信支付' && $findOrder['is_pay'] == 1) {
                \Think\Log::write("giveUp6");
                $wechatService = new WechatService();
                $res = $wechatService->refund($findOrder);
                $model->refundInfo($findOrder,$res);
            }
        }


        $str = $model->getOrderTplDetail($id);
        //发送模板消息
        $info = $model->getOrderTplInfo($id);
        $wechatService = new WechatService();
        $remark =  '十分抱歉，您订单中的'.$str.'已被'.$info['partner']['name'].'取消。取消原因:'.$info['order']['show_msg'].'如该商品已微信支付，我们会在30分钟内退款到您的微信账户。如有任何问题，请拨打：'.$info['partner']['tel'];
        $title = '【'.$info['partner']['name'].'】';
        $data['first'] = $title;
        $data['keyword1'] = $info['order']['order_num'];
        $data['keyword2'] = date('Y-m-d h:s',$info['order']['create_time']);
        $data['remark'] = $remark;
        $url = $wechatService->createBaseUrl(U('Index/myorder'));
        $userModel = new UserinfoModel();
        $openid = $userModel->getOpenid($info['order']['uid']);
        $wechatService->sendtpl(C('GIVEUP_TPL'),$openid,$url,$data);
        return 1;
    }


    public function getoutOrder($id,$uid){
        $model = new OrderModel();
        $str = $model->getOrderTplDetail($id);
        //发送模板消息
        $info = $model->getOrderTplInfo($id);
        $wechatService = new WechatService();
        $userModel = new UserinfoModel();
        $openid = $userModel->getOpenid($info['order']['uid']);
        $nickname = $userModel->getNickname($info['order']['uid']);
        $remark =  "";
        $title = '送到门口啦～出来取单吧^ω^';
        $data['first'] = $title;
        $data['keyword1'] = $info['order']['order_num'];
        $data['keyword2'] = $nickname;
        $data['keyword3'] = " 配送员已在门口默默等候……";
        $data['remark'] = $remark;
        $url = $wechatService->createBaseUrl(U('Index/myorder'));

        $wechatService->sendtpl(C('GETOUT_TPL'),$openid,$url,$data);
        return 1;

    }

    

    public function giveupAll(){
        $model = new OrderModel();
        $model->giveupAll();
    }

    //用户订单列表
    public function getUserOrder($uid){
        $model = new OrderModel();
        $new = $model->getUserOrder($uid);
        $old = $model->getUserOrderBak($uid);
        $res = array_merge($new,$old);
        return $res;
    }

    public function giveUpWxpay($base_id){
        $model = new OrderModel();
        $model->giveUpWxpay($base_id);
    }

    public function changeWxpay($order_id){
        $model = new OrderModel();
        $res = $model->changeWxpay($order_id);
        return $res;
    }

    public function checkWxpayStatus($order_id){
        $model = new OrderModel();
        $res = $model->checkWxpayStatus($order_id);
        return $res;
    }

    public function getOrderById($id)
    {
        $info = M('order')->find($id);
        return $info;
    }


    /** 获取用户在一个店铺的购买次数
     * @param $uid
     * @param $pid
     * @return mixed
     */
    public function getUserBuyTimes($uid, $pid)
    {
        $model = new OrderModel();
        return $model->getUserBuyTimes($uid,$pid);
    }

    //处理自动接单
    public function autoProcessOrder($shop_id,$order_id){
        $shop_model = new ShopModel();
        $is_order_auto = $shop_model->isOrderAuto($shop_id);
        if($is_order_auto){
            // self::accetpOrder($order_id,0);
            $order_model = new PartnerOrderModel();
            $order_model->confirmOrder($order_id);
        }
        // self::sendOrderTpl($order_id);
    }

    public function sendOrderTpl($order_id){
        $order_info = self::getOrderById($order_id);
        $pser = new PartnerService();
        $pname = $pser->getPartnerName($order_info['partner_id']);
        $user = new UserService();
        $openid = $user->getOpenid($order_info['uid']);
        
        $remark =  '您的订单已被受理，稍等片刻，速速就来～如有问题点击这里与商家联系';
        $title = '【'.$pname.'】 订单号：'.$order_info['sqnum'];
        $data['first'] = $title;
        $data['keyword1'] = $order_info['order_num'];
        $data['keyword2'] = $order_info['pay_money'];
        $data['keyword3'] = date('Y-m-d H:i',$order_info['create_time']);
        $data['remark'] = $remark;

        $tpl = $pser->getTplId($order_info['partner_id'],TPL_BONUS);
        $helper = WechatHelper::get($order_info['partner_id']);
        $url = $helper->createBaseUrl(U('Home/Index/myorder'));
        $res = $helper->sendbonustpl($tpl,$openid,$url,$data);
    }


    //用户订单列表
    public function getWeekUserOrder($uid){
        $model = new OrderModel();
        $new = $model->getWeekUserOrder($uid);
        $old = $model->getWeekUserOrderBak($uid);
        $res = array_merge($new,$old);
        return $res;
    }

}