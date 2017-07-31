<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/12 0012
 * Time: 下午 6:02
 */

namespace Home\Controller;


use Base\Service\WechatHelper;
use Home\Service\ActivityService;
use Home\Service\GoodsService;
use Home\Service\IndexService;
use Home\Service\MarketService;
use Home\Service\OrderService;
use Home\Service\PointService;
use Home\Service\ShopService;
use Home\Service\TimeGoodsService;
use Home\Service\UserService;
use Home\Service\BonusService;
use Home\Service\PartnerService;

use Home\Service\NotifyService;

class IndexController extends BaseController
{

    public function index(){
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        if($partner_id == 25){
            //忆友家强行跳转到周餐
            // redirect(U('Index/weekIndex'));
        }
        $baseCat = I('basecat');
        $goodsSer = new GoodsService();
        if(!empty($baseCat)){
            $cat = $goodsSer->getCatByBaseCat($baseCat,$shop_id);
        }else{
            $cat = I('cat','');
        }
        if(!empty($cat)){
            $this->assign('cat_id',$cat);
        }
        $shopService = new ShopService();
        $info = $shopService->getShopInfo($shop_id);
        $partnerSer = new PartnerService();
        $shop_num = $partnerSer->getShopNum($partner_id);
//        echo M()->getLastSql().$shop_num;die;
        $this->assign('shop_num',$shop_num);
        $in_sale_time = self::checkTime($info['start_time'],$info['end_time']);
        $isset_certs = $shopService->isSetCerts($shop_id);
        $notice = $shopService->loadnotice($shop_id);
        $this->assign('notice',$notice);
        $this->assign('isset_certs',$isset_certs);
        $this->assign('info',$info);
        $this->assign('in_sale_time',$in_sale_time);


        $shop_service = new ShopService();
        $goods_discount_info = $shop_service->getGoodsDiscountInfo($partner_id,$shop_id);
        $this->assign('goods_discount_status',$goods_discount_info['status']);
        $this->assign('goods_discount_num',$goods_discount_info['goods_discount_num']); 
        $this->display('index'.$info['tpl_type']);
    }

    public function preIndex()
    {
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $shopService = new ShopService();
        $info = $shopService->getShopInfo($shop_id);

        $setting = $shopService->getPreIndexInfo($partner_id,$shop_id);
        $this->assign('setting',$setting);
        $this->assign('info',$info);
        $this->display();
    }

    public function partner(){
        $goodService = new GoodsService();
        $shop_id = session('shop');
        $list = $goodService->getPartnerList($shop_id);
        $cat = I('cat',$list[0]['id']);
        $this->assign('cat',$cat);
        $this->assign('partners',$list);
        $this->display();
    }

    public function groupindex(){
        $shop_id = session('shop');
        $shopService = new ShopService();
        $info = $shopService->getShopInfo($shop_id);
        $this->assign('info',$info);
        $this->display();
    }

    public function loaddata(){
        $cat = I('cat');
        $goodService = new GoodsService();
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $data['list'] = $goodService->getGoodsList($cat,$shop_id);
        $data['detail'] = $goodService->getGoodsStatus($shop_id,$partner_id,$cat);
        echo json_encode($data);exit;
    }

    public function loadpredata()
    {
        $cat = I('cat',0);
        $goodService = new GoodsService();
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $data['list'] = $goodService->getPreGoodsList($cat,$shop_id);
        $data['detail'] = $goodService->getPreGoodsStatus($shop_id,$partner_id,$cat);
        echo json_encode($data);exit;
    }

    public function loadgroupdata(){
        $goodService = new GoodsService();
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $data['list'] = $goodService->getGroupGoodsList($shop_id);
        echo json_encode($data);exit;
    }

    public function loaddetail(){
        $cat = I('cat');
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $goodService = new GoodsService();
        $list = $goodService->getGoodsStatus($shop_id,$partner_id,$cat);
        echo json_encode($list);exit;
    }

    public function loadshopcart(){
        $shop_id = session('shop');
        $uid = session('uid');
        $type = I('type',0);
        $goodService = new GoodsService();
        $list = $goodService->getShopcart($uid,$shop_id,$type);
        echo json_encode($list);exit;
    }

    /**
     * 购物车商品数量
     */
    public function changeNum(){
        $goods_id = I('id');
        $num = I('num');
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new UserService();
        $service->changeNum($uid,$shop_id,$goods_id,$num);
    }

    public function getGoodsFormat()
    {
        $id = I('id');
        $ser = new GoodsService();
        $res = $ser->getGoodsFormat($id);
        echo json_encode($res);exit;
    }

    /**
     * 检查购物车
     */
    public function checkShopCart(){
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new UserService();
        $type = I('type',0);
        $res = $service->getShopCartInfo($uid,$shop_id,1,$type);
        $total_money = $res['total_money'];
        $total_num = $res['total_num'];
        $this->assign('total_money',$total_money);
        $this->assign('total_num',$total_num);
        $this->assign('info',$res['res']);
        $this->assign('type',$type);
        if($res['can_next'] == 1 && $total_num>0){
            redirect(U('Index/goOrder',array('type'=>$type)));
        }else{
            $this->display('shopcart');
        }
    }


    public function shopcart(){
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new UserService();
        //清除掉购物车不满起送价的商品

        $res = $service->getShopCartInfo($uid,$shop_id,1);
        // var_dump($res['res'][9]);die;
        $total_money = $res['total_money'];
        $total_num = $res['total_num'];
//        echo json_encode($res);exit;
        $this->assign('total_money',$total_money);
        $this->assign('total_num',$total_num);
        $this->display();
    }


    public function loadShopcartData(){
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new UserService();
        //清除掉购物车不满起送价的商品

        $res = $service->getShopCartInfo($uid,$shop_id,1);
        
        echo json_encode($res);
        exit();
    }

    public function goOrder(){
        $uid = session('uid');
        $shop_id = session('shop');
        $type = I('type');//1 今日 2 明日 3 团餐
        $service = new UserService();
        $res = $service->getShopCartInfo($uid,$shop_id,1);
//        var_dump($res);die;
        if($res['can_next'] != 1){
            redirect(U('Index/shopcart'));
        }

        $send_type = I('send_type',0);
        $this->assign('send_type',$send_type);
        
        $partner_id = session('partner_id');

        $shopService = new ShopService();
        //获取店铺信息
        $shop_info = $shopService->getShopById($shop_id);

        $can_pick = $shopService->checkPick($partner_id,$shop_id);
        if($can_pick == 0){
            $shop_info['is_pick'] = 0;
        }
        
        $this->assign('shop_info',$shop_info);

         

        //自提地点
        $pick_building = $shopService->getPickBuilding($shop_id);
        $this->assign('pick_building',$pick_building);

        $userService = new UserService();
        //获取用户信息
        $user_info = $userService->getAddress($uid,$shop_id);
        $this->assign('user_info',$user_info);

        //获取订单信息
        $remove = 1;
        $res = $userService->getShopCartInfo($uid,$shop_id,$remove,$type);
        if($res['total_money'] == 0){
            redirect(U('Index/index'));
        }
        
        $this->assign('total_num',$res['total_num']);
        $this->assign('goods_info',$res['goods_info']);


        //检测用户红包
        $bonus_service = new BonusService();
        $bonus_list = $bonus_service->checkBonus($uid,$partner_id,$shop_id,$res['total_money']);
        $selected_bonus = $bonus_list[0];
        // var_dump($selected_bonus);die;
        $this->assign('selected_bonus',$selected_bonus);
        $this->assign('bonus_list',$bonus_list);

        //
        if($type != 3){
            //获取活动
            $extParam['send_money'] = $send_info['send_money'];
            $activityService = new ActivityService();
            $activity_info = $activityService->getListBeforeOrder($partner_id,$shop_id,$uid,$extParam);


            //特价商品优惠
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
        

        $this->assign('activity_info',$activity_info);
        
        //餐盒费 
        $this->assign('lunchbox_money',$res['lunchbox_money']); 


        //送餐预计时间
        $send_time = $shopService->getSendTime($shop_id,$send_info['send_interval']);
        $this->assign('send_time',$send_time);

        //获取实付金额
        $pay_money = $this->getPaymoney($res['total_money'],$selected_bonus,$activity_info,$send_info['send_money'],$res['lunchbox_money']);
        $total_money = bcadd($res['total_money'], $send_info['send_money'],2);
        $total_money = bcadd($total_money,$res['lunchbox_money'],2);

        //获取未扣除红包的金额
        $no_bonus_money = $this->getNoBonusMoney($res['total_money'],$activity_info,$send_info['send_money'],$res['lunchbox_money']);

        //获取用户余额
        if($shop_info['is_user_recharge'] == 1){
            $account_money = $userService->getUserAccountMoney($uid);
            if($account_money >= $pay_money){
                $can_recharge_pay = 1;
            }else{
                $can_recharge_pay = 0;
            }
            $this->assign('account_money',$account_money);
            $this->assign('can_recharge_pay',$can_recharge_pay);
        }

        $this->assign('total_money',$total_money);
        $this->assign('pay_money',$pay_money);
        $this->assign('no_bonus_money',$no_bonus_money);
        $this->assign('type',$type);
        $this->display('neworder');
    }

    public function getSendInfo()
    {
        $uid = session('uid');
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        //获取配送费
        $partnerService = new PartnerService();
        $res = $partnerService->getSendInfo($partner_id,$shop_id,$uid);
        echo json_encode($res);exit;
    }

    public function userOrderCount($uid){
        $where['uid'] = $uid;
        $where['bonus_id'] =  array('neq','');
        return M('order')->where($where)->count();
    }
    
    public function getPlaceholder($shop_id){
        return M('shop')->where(array('id'=>$shop_id))->getField('guide_info');
    }


    /**
     * 保存订单
     */
    public function saveOrder(){
        $uid = session('uid');
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $data = I();
        //检查库存
        $orderService = new OrderService();
        \Think\Log::write("tong-test1");
        $res = $orderService->checkStore($uid,$partner_id,$shop_id);
        \Think\Log::write("tong-test2  json=".json_encode($res));
        if(!empty($res)){
            $orderService->removeShopcartNoStore($res);
            $back['status'] = "nostore";
            $back['msg'] = $orderService->getNoStoreStr($res);
            echo json_encode($back);exit;
        }
        //库存检查结束

        if($data['is_pick'] == 1){
            //自提
            $b = strpos($data['pick_time'], '-');
            if($b !== false){
                //日期格式 12:22-3;
                $tmpArr = explode('-', $data['pick_time']);
                $pick_time = strtotime('today') + $tmpArr[1] * 86400;
                $date = date('Y-m-d',$pick_time);
                $date = $date.' '.$tmpArr[0];
                $data['reserve_time'] = strtotime($date);
            }
            $data['send_money'] = 0;
        }else{
            $a = strpos($data['reserve_time'], '-');
            if($a !== false){
                //日期格式 12:22-3;
                $tmpArr = explode('-', $data['reserve_time']);
                $reserve_time = strtotime('today') + $tmpArr[1] * 86400;
                $date = date('Y-m-d',$reserve_time);
                $date = $date.' '.$tmpArr[0];
                $data['reserve_time'] = strtotime($date);
            }
            $data['is_pick'] = 0;
            $data['pick_address'] = '';
            $data['pick_id'] = 0;
        }

        $data['order_num'] = order_num($uid);
        //变更用户的信息

        $userService = new UserService();
        //保存订单信息
        $user_info = $userService->getAddress($uid,$shop_id);
        if($data['is_pick'] == 1){
            if(empty($user_info['name']) || empty($user_info['pick_address']) || empty($user_info['tel'])){
                $back['status'] = "changePickAdd";
                echo json_encode($back);exit;
            }
        }else{
            if(empty($user_info['name']) || empty($user_info['address']) || empty($user_info['tel'])){
                $back['status'] = "changeAdd";
                echo json_encode($back);exit;
            }
        }
        


        if($order_id = $orderService->saveOrder($uid,$partner_id,$shop_id,$partner_id,$data)){
            if($data['pay_type'] == PAY_WX){
                $back['status'] = "wx";
                $back['param'] = $order_id;
            }else if($data['pay_type'] == PAY_XIANXI){
                // publishOrder($shop_id,$order_id);
                $back['status'] = "success"; 
                $back['param'] =  $data['order_num'];
                $back['order_id'] = $order_id; 
                // $orderService->autoProcessOrder($shop_id,$order_id);
                $ser = new NotifyService();
                $ser->process($order_id,'xx');
            }else{
                //余额支付
                $account_money = $userService->getUserAccountMoney($uid);
                $order_info = $orderService->getOrderInfo($order_id);
                $order_money = $order_info['base_info']['pay_money'];
                if($account_money >= $order_money){
                    $back['status'] = "success"; 
                    $back['param'] =  $data['order_num'];
                    $back['order_id'] = $order_id; 
                    $ser = new NotifyService();
                    $ser->process($order_id,'recharge');
                }else{
                    $back['status'] = "recharge_no_enough"; 
                    $back['param'] =  $data['order_num'];
                }
            }
            echo json_encode($back);exit;
        }else{
            $back['status'] = 'error';
            echo json_encode($back);exit;
        }
    }

    public function sendOrderTpl(){
//        ignore_user_abort(true);
//        $base_id = I('base_id');
//        $orderService = new OrderService();
//        $orderService->sendOrderTpl($base_id);
    }

    public function getPartnerStatus(){
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new PartnerService();
        $list = $service->getPartnerStatus($shop_id);
        echo json_encode($list);exit;
    }


    /**
     * 清空购物车
     */
    public function clearall(){
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new UserService();
        $service->clearall($uid,$shop_id);
    }

    //更名为
    public function myjf(){
        $week = I('isweek',0);
        $uid = session('uid');
        $service = new BonusService();
        $list = $service->getUserBonus($uid);
        // var_dump($list);die;
//        $this->num = $service->getActivityBonus($uid);
        $this->assign('list',$list);
        $this->assign('isweek',$week);
        $this->display("mybonus");
    }

    public function getActivity(){
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new ActivityService();
        $data = $service->getActivity($uid,$shop_id);
        echo json_encode($data);exit;
    }

    public function loadnotice()
    {
        $shop_id = session('shop');
        $ser = new ShopService();

    }


    /**
     * 我的订单
     */
    public function myorder(){
        $uid = session('uid');
        $partner_id = session('partner_id');
        $orderService = new OrderService();
        
        $partner_service = new PartnerService();
        $isset_week = $partner_service->isSetWeek($partner_id);
        if($isset_week){
            $list = $orderService->getWeekUserOrder($uid);
            $this->assign('list',$list);
            $this->display('week_myorder');
        }else{
            $list = $orderService->getUserOrder($uid);
            $this->assign('list',$list);
            $this->display();
        }
        
    }
    /**
     * 获取店铺是否关门，如果关门了，提示关门信息
     */
    public function getShopMsg(){
        $select = I('select_type');
        $cat = I('cat');
        $service = new ShopService();
        $res = $service->getShopMsg($select,$cat);
        echo json_encode($res);exit;
    }

    /**
     * 获取用户购物车
     */
    public function countShopcart()
    {
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new UserService();
        $res = $service->countShopcart($uid,$shop_id);
        echo json_encode($res);exit;
    }


    /**
     * 判断用户购物车是否为空
     */
    public function shopcartEmpty(){
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new UserService();
        echo $service->shopcartEmpty($uid,$shop_id);exit;
    }



    public function orderSuccess(){
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $order_id = I('order_id');
        $order_service = new OrderService(); 
        $order_info = $order_service->getOrderInfo($order_id);
        $shop_service = new ShopService();
        $shop_info = $shop_service->getShopInfo($shop_id);
        $money = $shop_service->getShareBonus($partner_id);
        $this->assign('order_info',$order_info['base_info']);
        $this->assign('money',$money);
        $this->assign('shop_info',$shop_info);
        // $this->display('orderSuccess_old');
        $this->display();
    }

    public function wxpay(){
        $order_id = I('order_id');
        $shop_id = session('shop');
        $pid = session('partner_id');
        $openid = session('openid');
        $service = new OrderService();
        $info = $service->getOrderInfo($order_id);
        $helper = WechatHelper::get($pid);
        try{
            $pser = new PartnerService();
            $payto = $pser->getPayTo($shop_id);
            //切换支付信息，系统接收钱
            if($payto == PAYTO_SYSTEM){
                $data = C("DEFAULT_PAY");
                $service->changeOrderPayTo($order_id);
            }else{
                $data=null;
            }
            $apiparam = $helper->getJsPayParam($order_id,$openid,$info['base_info']['order_num'],$info['base_info']['pay_money'],$payto,$data);
        }catch (\WxPayException $e){
            \Think\Log::write('wxpay-error1 '.$e->errorMessage());
        }
        echo $apiparam;exit;
    }

    public function test()
    {
        $this->display();
    }

    public function orderStatus(){
        $base_id = I('id');
        $service = new OrderService();
        $status = $service->getOrderStatus($base_id);
        if($status == 0){
            echo 'ready';exit;
        }else{
            echo 'overtime';exit;
        }
    }

    public function getOrderIsPay()
    {
        $id = I('id');
        $service = new OrderService();
        $ispay = $service->getOrderIsPay($id);
        echo $ispay;exit;
    }

    /**
     * 邀请好友的红包
     */
    public function myqrcode()
    {
        $uid = session('uid');
        $pid = session('partner_id');
        $ser = new MarketService();
        $set = $ser->getSetting($pid);
        $this->assign('set',$set);
        if(empty($set) || !$set['is_open']){
            $partner = new PartnerService();
            $info = $partner->getPartnerById($pid);
            $this->assign('info',$info);
            $this->display("notshare");
        }else{
            //获取用户的渠道二维码
            $userser = new UserService();
            $qrcode = $userser->getQrcode($pid,$uid);
            $this->assign('img',$qrcode);
            $pser = new PartnerService();
            $wname = $pser->getWechatName($pid);
            $city = $pser->getPartnerCity($pid);
            $uinfo = $userser->getUseInfoById($uid);
            $this->assign('uinfo',$uinfo);
            $this->assign('city',$city);
            $this->assign('wname',$wname);
            if($pid == 25){
                //忆友的伙伴们 特殊处理
                $img = $userser->getUserHead($pid,$uid);
                $this->assign('img',$img);
                $this->display("myqrcode_new");
            }else{
                $this->display();
            }
        }
    }

    public function getStatics(){
        $uid = session('uid');
        $service = new IndexService();
        $sharetype = I('sharetype','');
       
        
        switch ($sharetype) {
            case 'bonusshare':
                $service->setShareType(4);
            case 'shopping':
                $service->setShareType(3);
                break;
            case 'message':
                $service->setShareType(2);
                break;
            default:
                $service->setShareType(1);
                break;
        }
        
        $res = 1;
        $this->ajaxReturn($res);
    }

    /**
     * 用户手动选择取消微信支付
     */
    public function giveUpWxpay(){
        $base_id = I('base_id');
        if(!empty($base_id)){
            $service = new OrderService();
            $service->giveUpWxpay($base_id);
        }
    }

     /**
     * 将微信支付转为线下支付
     */
    public function changeWxpay(){
        $order_id = I('order_id');
        if(!empty($order_id)){
            $ser = new OrderService();
            $ser->changeWxpay($order_id);
            $ser = new NotifyService();
            $ser->process($order_id,'xx');
            $res = 1;
        }else{
            $res = 0;
        }
        echo json_encode(array('status'=>$res));
    }
    
    public function loadCategoryList(){
        $goodService = new GoodsService();
        $shop_id = session('shop');
        $category = $goodService->getCategory($shop_id);
        echo json_encode($category);
    }

    public function checkWxpayStatus(){
        $shop_id = session('shop');
        $order_id = I('order_id');
        $service = new OrderService();
        $res = $service->checkWxpayStatus($order_id);
        $shop_service = new ShopService();
        $cash_pay = $shop_service->isSetCashPay($shop_id);
        echo json_encode(array('status'=>$res,'cash_pay'=>$cash_pay));
    }

    /**
     * 用户修改地址
     */
    public function changeAddress(){
        $uid = session('uid');
        $partner_id = session('partner_id');
        $shop_id = session('shop');
        $userService = new UserService();
        //获取用户地址
        $user_address = $userService->getAddress($uid,$partner_id);
        // var_dump($user_address);die;
        $this->assign('user_address',$user_address);
        //获取楼宇信息
        $shopService = new ShopService();
        $shopInfo = $shopService->getShopById($shop_id);
        $building_list = $shopService->getBuildingList($shop_id);
        if(empty($building_list)){
            $empty_building = 1;
        }else{
            $empty_building = 0;
        }
        $this->assign('shop_address',$shopInfo['name']);
        $this->assign('shop_info',$shopInfo);
        $this->assign('building_list',$building_list);
        $this->assign('empty_building',$empty_building);
        $this->display();
    }

    public function changeAddressAction(){
        $userService = new UserService();
        $uid = session('uid');
        $partner_id = session('partner_id');
        $data = I();
        $userService->setAddress($uid,$partner_id,$data);
        echo json_encode(array('status'=>'ok'));
    }

    /**
     * 用户修改自提信息
     */
    public function changePickInfo(){
        $uid = session('uid');
        $partner_id = session('partner_id');
        $shop_id = session('shop');
        $userService = new UserService();
        //获取用户地址
        $user_address = $userService->getAddress($uid,$partner_id);
        $this->assign('user_address',$user_address);

        $shop_service = new ShopService();
        $pick_list = $shop_service->getDefaultPickList($partner_id,$shop_id);
        $this->assign('pick_list',$pick_list);
        $this->assign('partner_id',$partner_id);
        $this->assign('shop_id',$shop_id);
        $this->display();
    }

    public function changePickInfoAction(){
        $userService = new UserService();
        $uid = session('uid');
        $partner_id = session('partner_id');
        $data = I();
        $userService->setPickInfo($uid,$partner_id,$data);
        echo json_encode(array('status'=>'ok'));
    }

    //搜索自提地点
    public function searchPick(){
        $pick = I('pick');
        $partner_id = session('partner_id');
        $shop_id = session('shop');
        $shop_service = new ShopService();
        $res = $shop_service->searchPick($partner_id,$shop_id,$pick);
        echo json_encode($res);
    }

    //根据定位获取自提点
    public function getPicklistByLocation(){
        $data = I();
        $lat = I('lat',0);
        $lng = I('lng',0);
        $partner_id = session('partner_id');
        $shop_id = session('shop');
        $service = new ShopService();
        $res = $service->getPicklistByLocation($partner_id,$shop_id,$data['lat'],$data['lng']);
        echo json_encode($res);
    }

    /**
     * 获取实付金额 
    */
    public function getPaymoney($total_money,$bonus_info,$activity_info,$send_money,$lunchbox_money=0){
        $bonus = empty($bonus_info['money']) ? 0 : $bonus_info['money'];
        $pay_money = bcsub($total_money,$bonus,2);
        foreach($activity_info as $activity){
            if($activity['type'] == 'dec'){
                $pay_money = bcsub($pay_money,$activity['money'],2);
            }
        }
        $pay_money = bcadd($pay_money, $send_money,2);
        $pay_money = bcadd($pay_money, $lunchbox_money,2);
        if($pay_money < 0) $pay_money = 0;
        return $pay_money;
    }

    /**
     * 获取未扣除红包的金额 
    */
    public function getNoBonusMoney($total_money,$activity_info,$send_money,$lunchbox_money=0){
        /*$bonus = empty($bonus_info['money']) ? 0 : $bonus_info['money'];
        $pay_money = bcsub($total_money,$bonus,2);*/
        $pay_money = $total_money;
        foreach($activity_info as $activity){
            if($activity['type'] == 'dec'){
                $pay_money = bcsub($pay_money,$activity['money'],2);
            }
        }
        $pay_money = bcadd($pay_money, $send_money,2);
        $pay_money = bcadd($pay_money, $lunchbox_money,2);
        if($pay_money < 0) $pay_money = 0;
        return $pay_money;
    }

    //首页加载活动
    public function loadActivity(){
        $partner_id = session('partner_id');
        $shop_id = session('shop');
        $activityService = new ActivityService();
        $activity_info = $activityService->getNowActivity($partner_id,$shop_id);
        $str = '';
        foreach($activity_info as $key=>$value){
            $str .= $value['name'].' ';
        }
        echo json_encode(array('msg'=>$str));
    }

    //添加发出邀请的人数
    public function addMarketUser(){
        $uid = session('uid');
        $partner_id = session('partner_id');
        $service = new MarketService();
        $service->addMarketUser($partner_id,$uid);
    }

    public function setVisit(){
        $uid = session('uid');
        \Think\Log::write("setvisit uid=".$uid);
        $service = new UserService();
        $service->setVisit($uid);
    }

    public function certs(){
        $partner_id = session('partner_id');
        $shop_id = session('shop');
        $service = new ShopService();
        $list = $service->getCertsList($partner_id,$shop_id);
        $this->assign('list',$list);
        $this->display();
    }

    public function reserveInfo()
    {
        $partner_id = session('partner_id');
        $shop_id = session('shop');
        $ser = new PartnerService();
        $info = $ser->reserveInfo($partner_id,$shop_id);
        echo json_encode($info);
    }
    
    public function pickTimeInfo()
    {
        $partner_id = session('partner_id');
        $shop_id = session('shop');
        $uid = session('uid');
        $ser = new PartnerService();
        $info = $ser->pickTimeInfo($partner_id,$shop_id,$uid);
        echo json_encode($info);
    }

    public function getBuilding(){
        $building_id = I('building_id');
        $service = new ShopService();
        $res = $service->getBuildingById($building_id);
        echo json_encode($res);
    }


    //检测是否在营业时段内
    private function checkTime($start_time,$end_time){
        if(empty($start_time)){
            return 1;
        }
        $time = date('H:i');

        if($end_time>$start_time){//自然日
            if($time>=$start_time && $time<=$end_time){
                return 1;
            }
        }else{ //非自然日
            if(($time<="23:59" && $time>=$start_time) || $time<=$end_time){
                return 1;
            }
        }
        return 0;
    }


    //用户充值
    public function userRechargePay(){
        $partner_id = session('partner_id');
        $uid = session('uid');
        $shop_id = session('shop');
        $shopService = new ShopService();
        $info = $shopService->getShopInfo($shop_id);
        $service = new UserService();
        $account_money = $service->getUserAccountMoney($uid);
        $list = $service->getUserRechargeSet($partner_id);
        $this->assign('account_money',$account_money);
        $this->assign('list',$list);
        $this->assign('info',$info);
        $this->display('userRecharge');
    }


    public function userRecharge(){
        $recommand_id = I('recommand_id',null);
        session('recommand_id',null);
        if(!empty($recommand_id)){
            session('recommand_id',$recommand_id);
        }
        redirect(U('Index/userRechargePay'));
    }

    //充值微信调用
    public function getWxParam(){
        $partner_id = session('partner_id');
        $recharge_id = I('recharge_id');
        $service = new UserService();
        $shop_id = session('shop');
        $money = I('money',null);
        if(isset($money)){
            $recharge_info = $service->getUserRechargeSetByMoney($partner_id,$money);
        }else{
            $recharge_info = $service->getUserRechargeSetById($recharge_id);
        }
        if(!empty($recharge_info)){
            $uid = session('uid');
            $partner_id = session('partner_id');
            $order_num = order_num($uid);
            $recommand_id = session('recommand_id');
            $recharge_info['qrcode_id'] = empty($recommand_id) ? 0 : $recommand_id;
            $order_id = $service->addUserRecharge($partner_id,$shop_id,$uid,$order_num,$recharge_info);
            $openid = session('openid');
            $helper = WechatHelper::get($partner_id);
            $payto = PAYTO_PARTNER;
            $data['notify_url'] = C('BASE_URL').U("Home/Notify/userRechargCallBack",array('id'=>$order_id));
            $apiparam = $helper->getJsPayParam('',$openid,$order_num,$recharge_info['money'],$payto,$data);
            // echo $apiparam;
            $api = json_decode($apiparam,true);
            echo json_encode(array('order_id'=>$order_id,'apiparam'=>$api));
            die;
        }
        
    }

    public function rechargeDetail(){
        $type = I('type',1);
        $this->assign('type',$type);
        $this->display();
    }

    public function loadrechargeDetail(){
        $uid = session('uid');
        //type 1 充值明细 2 支出明细
        $type = I('type',1);
        $page = I('page',0);
        $service = new UserService();
        $list = $service->getUserRechargeDetail($uid,$type,$page);
        $page = $page + 5;
        echo json_encode(array('list'=>$list,'page'=>$page));
    }

    public function rechargeSuccess(){
        $this->display();
    }

    public function rechargeError(){
        $this->display();
    }

    public function otherRecharge(){
        $partner_id = session('partner_id');
        $service = new UserService();
        $list = $service->getUserRechargeSet($partner_id);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 到点余额支付  函数开始
     */

    public function pointPay()
    {
        $point = I('spid');
        if(empty($point)) {
            echo "系统异常~~";exit;
        }
        $uid = session('uid');
        $pid = session('partner_id');
        $sid = session('shop');
        $service = new UserService();
        if(IS_POST){
            $money = I('money');
            echo $service->pointRechargeDec($pid,$sid,$uid,$point,$money);exit;
        }else{

            $account_money = $service->getUserAccountMoney($uid);
            $ser = new PointService();
            $info = $ser->getById($point);
            $this->assign('info',$info);
            $this->assign('account_money',$account_money);
            $this->display();
        }
    }

    public function pointPaySuccess()
    {
        $uid = session('uid');
        $service = new UserService();
        $list = $service->pointPayList($uid,0,1);
        $res = $list[0];
        $this->assign('info',$res);
        $this->display();
    }

    public function pointPayList()
    {
        $uid = session('uid');
        $point = I('point',0);
        $service = new UserService();
        $list = $service->pointPayList($uid,$point,5);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 到点余额支付  函数结束
     */



    /**
     * 周餐 预定模式页面  函数开始
     */
    public function weekIndex()
    {
        $partner_id = session('partner_id');
        $uid = session('uid');
        $shop_id = session('shop');
        $c = I('day');

        $shop_service = new ShopService();
        $goods_discount_info = $shop_service->getGoodsDiscountInfo($partner_id,$shop_id);

        $ser = new TimeGoodsService();
        $list = $ser->getTimeList($partner_id,$shop_id);

        if(empty($list)){
            echo "本商户暂未开通本模块";exit;
        }
        if(empty($c)){
            $c = $list[0]['id'];
        }

        $this->assign('now',time());
        $this->assign('today_date',date('Y-m-d',time()));
        $shopService = new ShopService();
        $info = $shopService->getShopInfo($shop_id);
        $this->assign('info',$info);
        $this->assign('c',$c);
        $this->assign('list',$list);
        $this->assign('goods_discount_status',$goods_discount_info['status']);
        $this->assign('goods_discount_num',$goods_discount_info['goods_discount_num']);
        //老的模板
        //$this->display();
        $this->display("weekIndex2");
    }


    /**
     * 根据编号获取商品列表
     */
    public function getDayGoods()
    {
        $day = I('day');
        $partner_id = session('partner_id');
        $uid = session('uid');
        $shop_id = session('shop');
        $ser = new TimeGoodsService();
        $list = $ser->getDayGoodsList($partner_id,$shop_id,$day);
        echo json_encode($list);exit;
    }

    public function changeWeekNum()
    {
        $day_id = I('day_id');
        $time = I('time');
        $id = I('id');
        $num = I('num');
        $partner_id = session('partner_id');
        $uid = session('uid');
        $shop_id = session('shop');
        $ser = new TimeGoodsService();
        $ser->changeWeekNum($day_id, $time, $id, $num,$partner_id,$shop_id,$uid);
    }

    /**
     * 获取商品详情
     */
    public function loadweekdetail()
    {
        $day = I('day_id');
        $partner_id = session('partner_id');
        $uid = session('uid');
        $shop_id = session('shop');
        $ser = new TimeGoodsService();
        $list = $ser->loadweekdetail($partner_id,$shop_id,$day);
        echo json_encode($list);exit;
    }


    public function loadweekshopcart()
    {

    }

    public function loadShopMsg(){
        $partner_id = session('partner_id');
        $shop_id = session('shop');

        $ser = new TimeGoodsService();
        $res = $ser->loadShopMsg($partner_id,$shop_id);
        echo $res;exit;
    }

    public function loadtime()
    {
        $partner_id = session('partner_id');
        $uid = session('uid');
        $shop_id = session('shop');
        $ser = new TimeGoodsService();
        $res = $ser->loadTime($partner_id,$shop_id);
        echo $res;exit;
    }


    public function goOrderWeek(){
        $uid = session('uid');
        $shop_id = session('shop');
        $partner_id = session('partner_id');

        $shopService = new ShopService();
        //获取店铺信息
        $shop_info = $shopService->getShopById($shop_id);

        $can_pick = $shopService->checkPick($partner_id,$shop_id);
        if($can_pick == 0){
            $shop_info['is_pick'] = 0;
        }

        $this->assign('shop_info',$shop_info);



        //自提地点
        $pick_building = $shopService->getPickBuilding($shop_id);
        $this->assign('pick_building',$pick_building);

        $userService = new UserService();
        //获取用户信息
        $user_info = $userService->getAddress($uid,$shop_id);
        $this->assign('user_info',$user_info);

        //获取订单信息
        $time_service = new TimeGoodsService();
        $remove = 1;
        $res = $time_service->getShopCartInfoWeek($uid,$shop_id,$remove);
        // var_dump($res['res']);die;
        if($res['total_money'] == 0){
            redirect(U('Index/weekIndex'));
        }
        
        $this->assign('total_num',$res['total_num']);
        $this->assign('goods_info',$res['res']);

        //检测用户红包
        $bonus_service = new BonusService();
        $bonus_list = $bonus_service->checkBonus($uid,$partner_id,$shop_id,$res['total_money']);
        $selected_bonus = $bonus_list[0];
        // var_dump($selected_bonus);die;
        $this->assign('selected_bonus',$selected_bonus);
        $this->assign('bonus_list',$bonus_list);

        
        //获取配送费
        $partnerService = new PartnerService();
        $send_info = $partnerService->getSendMoney($partner_id,$shop_id,$uid);
        // var_dump($send_info);die;
        $this->assign('send_info',$send_info);

        //获取活动
        $extParam['send_money'] = $send_info['send_money'];
        $activityService = new ActivityService();
        $activity_info = $activityService->getListBeforeOrder($partner_id,$shop_id,$uid,$extParam);

        //提前预定优惠
        $day_discount = $shopService->getDayDiscount($partner_id,$shop_id,$res['res']);
        if($day_discount > 0){
            $day_discount_activity['type'] = 'dec';
            $day_discount_activity['id'] = 0;
            $day_discount_activity['name'] = '提前预定优惠'.floatval($day_discount).'元';
            $day_discount_activity['money'] = $day_discount;
            $activity_info[] = $day_discount_activity;
        }


        //特价商品优惠
        $goods_discount_set = $shopService->getGoodsDiscountInfo($partner_id,$shop_id);
        if($goods_discount_set['status'] == 1){
            $goods_discount = $shopService->getGoodsDiscount($partner_id,$shop_id,$uid,$res['res'],$goods_discount_set['goods_discount_num']);
            if(!empty($goods_discount)){
                
                if($goods_discount_set['share_with_other'] == 0){
                    foreach($activity_info as $key=>$activity){
                        if($activity['type'] == 'dec' && $activity['id'] >0){
                            unset($activity_info[$key]);
                        }
                    }
                }
                

                foreach($goods_discount as $key=>$discount){
                    $goods_discount_activity['type'] = 'dec';
                    $goods_discount_activity['id'] = 0;
                    $goods_discount_activity['name'] = $discount['discount_msg'];
                    $goods_discount_activity['money'] = $discount['discount'];
                    $activity_info[] = $goods_discount_activity;
                }
            }
        }

        $this->assign('activity_info',$activity_info);

        //送餐预计时间
        $send_time = $shopService->getSendTime($shop_id,$send_info['send_interval']);
        $this->assign('send_time',$send_time);

        //获取实付金额
        $pay_money = $this->getPaymoney($res['total_money'],$selected_bonus,$activity_info,$send_info['send_money']);
        $total_money = bcadd($res['total_money'], $send_info['send_money'],2);

        //获取未扣除红包的金额
        $no_bonus_money = $this->getNoBonusMoney($res['total_money'],$activity_info,$send_info['send_money']);

        //获取用户余额
        if($shop_info['is_user_recharge'] == 1){
            $account_money = $userService->getUserAccountMoney($uid);
            if($account_money >= $pay_money){
                $can_recharge_pay = 1;
            }else{
                $can_recharge_pay = 0;
            }
            $this->assign('account_money',$account_money);
            $this->assign('can_recharge_pay',$can_recharge_pay);
        }

        $this->assign('total_money',$total_money);
        $this->assign('pay_money',$pay_money);
        $this->assign('no_bonus_money',$no_bonus_money);

        $this->display('orderWeek');
    }

    public function getSendTime()
    {
        $uid = session('uid');
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $ser = new TimeGoodsService();
        $res = $ser->getSendTime($partner_id,$shop_id,$uid);

        echo json_encode($res);exit;
    }

    public function shopcartWeek(){
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new TimeGoodsService();
        //清除掉购物车不满起送价的商品

        $res = $service->getShopCartInfoWeek($uid,$shop_id,1);
        // var_dump($res['res'][9]);die;
        $total_money = $res['total_money'];
        $total_num = $res['total_num'];
//        echo json_encode($res);exit;
        $this->assign('total_money',$total_money);
        $this->assign('total_num',$total_num);
        $this->display();
    }

    public function loadShopcartWeek(){
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new TimeGoodsService();
        //清除掉购物车不满起送价的商品

        $res = $service->getShopCartInfoWeek($uid,$shop_id,1);
        
        echo json_encode($res);
        exit();
    }

    //检查购物车
    public function checkShopCartWeek(){
        $uid = session('uid');
        $shop_id = session('shop');
        $service = new TimeGoodsService();
        $res = $service->getShopCartInfoWeek($uid,$shop_id,1);
        // var_dump($res);die;
        $total_money = $res['total_money'];
        $total_num = $res['total_num'];
        $this->assign('total_money',$total_money);
        $this->assign('total_num',$total_num);
        $this->assign('info',$res['res']);
        if($res['can_next'] == 1 && $total_num>0){
            redirect(U('Index/goOrderWeek'));
        }else{
            $this->display('shopcartWeek');
        }
    }


    public function changeAddressWeek(){
        $uid = session('uid');
        $partner_id = session('partner_id');
        $shop_id = session('shop');
        $userService = new UserService();
        //获取用户地址
        $user_address = $userService->getAddress($uid,$partner_id);
        // var_dump($user_address);die;
        $this->assign('user_address',$user_address);
        //获取楼宇信息
        $shopService = new ShopService();
        $shopInfo = $shopService->getShopById($shop_id);
        $building_list = $shopService->getBuildingList($shop_id);
        if(empty($building_list)){
            $empty_building = 1;
        }else{
            $empty_building = 0;
        }
        $this->assign('shop_address',$shopInfo['name']);
        $this->assign('shop_info',$shopInfo);
        $this->assign('building_list',$building_list);
        $this->assign('empty_building',$empty_building);
        $this->display();
    }

    public function changeAddressActionWeek(){
        $userService = new UserService();
        $uid = session('uid');
        $partner_id = session('partner_id');
        $data = I();
        $userService->setAddress($uid,$partner_id,$data);
        echo json_encode(array('status'=>'ok'));
    }


    /**
     * 保存订单
     */
    public function saveOrderWeek(){
        $uid = session('uid');
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $data = I();
        $orderService = new OrderService();
        /*//检查库存
        $orderService = new OrderService();
        \Think\Log::write("tong-test1");
        $res = $orderService->checkStore($uid,$partner_id,$shop_id);
        \Think\Log::write("tong-test2  json=".json_encode($res));
        if(!empty($res)){
            $orderService->removeShopcartNoStore($res);
            $back['status'] = "nostore";
            $back['msg'] = $orderService->getNoStoreStr($res);
            echo json_encode($back);exit;
        }
        //库存检查结束*/

        if($data['is_pick'] == 1){
            //自提
            $b = strpos($data['pick_time'], '-');
            if($b !== false){
                //日期格式 12:22-3;
                $tmpArr = explode('-', $data['pick_time']);
                $pick_time = strtotime('today') + $tmpArr[1] * 86400;
                $date = date('Y-m-d',$pick_time);
                $date = $date.' '.$tmpArr[0];
                $data['reserve_time'] = strtotime($date);
            }
            $data['send_money'] = 0;
        }else{

            $time_service = new TimeGoodsService();
            $send_time = $time_service->getSendTime($partner_id,$shop_id,$uid);
            // var_dump($send_time);die;
            if(empty($send_time)){
                $back['status'] = 'notime';
                $back['msg'] = '该时段今日餐品不可预定';
                echo json_encode($back);exit;
            }else{
                $flag = 0;
                foreach($send_time as $key=>$value){
                    if($data['reserve_time'] == $value['pre_time']){
                        $flag = 1;
                        break;
                    }
                }
                if($flag == 0){
                    $back['status'] = 'notime';
                    $back['msg'] = '该时段今日餐品不可预定';
                    echo json_encode($back);exit;
                }
            }

            $a = strpos($data['reserve_time'], '-');
            if($a !== false){
                //日期格式 12:22-3;
                $tmpArr = explode('-', $data['reserve_time']);
                $reserve_time = strtotime('today') + $tmpArr[1] * 86400;
                $date = date('Y-m-d',$reserve_time);
                $date = $date.' '.$tmpArr[0];
                $data['reserve_time'] = strtotime($date);
            }
            $data['is_pick'] = 0;
            $data['pick_address'] = '';
        }

        $data['order_num'] = order_num($uid);
        //变更用户的信息

        $userService = new UserService();
        //保存订单信息
        $user_info = $userService->getAddress($uid,$shop_id);
        if(empty($user_info['name']) || empty($user_info['address']) || empty($user_info['tel'])){
            $back['status'] = "changeAdd";
            echo json_encode($back);exit;
        }

        $time_service = new TimeGoodsService();
        if($order_id = $time_service->saveOrderWeek($uid,$partner_id,$shop_id,$partner_id,$data)){
            if($data['pay_type'] == PAY_WX){
                $back['status'] = "wx";
                $back['param'] = $order_id;
            }else if($data['pay_type'] == PAY_XIANXI){
                // publishOrder($shop_id,$order_id);
                $back['status'] = "success"; 
                $back['param'] =  $data['order_num'];
                $back['order_id'] = $order_id; 
                // $orderService->autoProcessOrder($shop_id,$order_id);
                $ser = new NotifyService();
                $ser->process($order_id,'xx');
            }else{
                //余额支付
                $account_money = $userService->getUserAccountMoney($uid);
                $order_info = $orderService->getOrderById($order_id);
                $order_money = $order_info['pay_money'];
                if($account_money >= $order_money){
                    $back['status'] = "success"; 
                    $back['param'] =  $data['order_num'];
                    $back['order_id'] = $order_id; 
                    $ser = new NotifyService();
                    $ser->process($order_id,'recharge');
                }else{
                    $back['status'] = "recharge_no_enough"; 
                    $back['param'] =  $data['order_num'];
                }
            }
            echo json_encode($back);exit;
        }else{
            $back['status'] = 'error';
            echo json_encode($back);exit;
        }
    }

    /**
     * 周餐 预定模式页面  函数结束
     */

    //教练充值二维码页面
    public function rechargeQrcode(){
        $uid = session('uid');
        $service = new UserService();
        $info = $service->getRechargeQrcode($uid);
        if(empty($info)){
            $this->display('rechargeQrcodeError');
            die;
        }
        $this->assign('info',$info);
        $this->display();
    }

    public function loadGroupSet()
    {
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $ser = new GoodsService();
        $res = $ser->loadGroupSet($partner_id,$shop_id);
        $res['remark'] = htmlspecialchars_decode($res['remark']);
        echo json_encode($res);exit;
    }


    public function chooseShopPos()
    {
        $lat = I('lat');
        $lng = I('lng');
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $ser = new ShopService();
        $near = $ser->getNearShop($partner_id,$lat,$lng);
        if($near['id'] != $shop_id && $near['shop_num']>1){
            $data['status'] = 1;
            $data['shop_id'] = $near['id'];
            $data['shop_name'] = $near['name'];
            echo json_encode($data);exit;
        }
    }

    public function saveShopcart()
    {
        $info = file_get_contents('php://input');
        $info = json_decode($info,true);
        $shop_id = session('shop');
        $partner_id = session('partner_id');
        $uid = session('uid');
        M('shopcart')->where(array('uid'=>$uid))->delete();
        foreach ($info as $it){
            $it['shop_id'] = $shop_id;
            $it['partner_id'] = $partner_id;
            $it['create_time'] = time();
            $it['uid'] = $uid;
            $it['status'] = 0;
            unset($it['total_price']);
            M('shopcart')->add($it);
        }
        echo "success";exit;
    }

    public function personInfo()
    {
        $uid = session('uid');
        $partner_id = session('partner_id');
        $userser = new UserService();
        $uinfo = $userser->getUseInfoById($uid);
        $qrcode = $userser->getQrcode($partner_id,$uid);
        $data = $userser->getPersonInfo($uid);
        $this->assign('user',$uinfo);
        $this->assign('qrcode',$qrcode);
        $this->assign('data',$data);
        $this->display();

    }

    public function bonusActivity()
    {
        $id = I('id');

        $uid = session('uid');
        $info = self::getBonusActivityInfo($id);
        $status = 0;
        $num = M('bonus_activity_user')->where(['uid'=>$uid,'activity_id'=>$id])->find();
        if(!empty($num)){
            $status = 1;
        }

        $getnum = M('bonus_activity')->where(['id'=>$id])->getField('num');
        if(!empty($info['max_num']) && $getnum >= $info['max_num']){
            $status = 2;
        }

        $this->assign('status',$status);

        $this->assign('info',$info);
        $this->display();
    }

    private function getBonusActivityInfo($id){
        $key = "BonusActivity_".$id;
        $info = S($key);
        if(empty($info)){
            $info = M('bonus_activity')->find($id);
            $info['detail'] = M('bonus_activity_detail')->where(['activity_id'=>$id])->select();
            S($key,$info,3000);
        }


        M('bonus_activity')->where(['id'=>$id])->setInc('read_time');
        return $info;
    }

    public function getBonusActivity()
    {
        $id = I('id');
        $uid = session('uid');
        $info = self::getBonusActivityInfo($id);
        if($info['status'] == 1){
            echo 2;die;
        }
        $num = M('bonus_activity_user')->where(['uid'=>$uid,'activity_id'=>$id])->find();
        if(!empty($num)){
            echo 1;die;
        }
        $getnum = M('bonus_activity')->where(['id'=>$id])->getField('num');
        if($getnum >= $info['max_num']){
            echo 2;die;
        }
        $partner_id = session('partner_id');
        $ser = new BonusService();
        foreach ($info['detail'] as $k=>$v){
            $data['partner_id'] = $partner_id;
            $data['uid'] = $uid;
            $data['money'] = $v['bonus_money'];
            $data['day'] = $v['bonus_day'];
            $data['param'] = $v['bonus_param'];
            $data['type'] = BONUS_ACTIVE;
            for ($i=0;$i<$v['num'];$i++) {
                $ser->addBonus($data);
            }
        }
        M('bonus_activity_user')->add(['uid'=>$uid,'activity_id'=>$id,'create_time'=>time()]);
        M('bonus_activity')->where(['id'=>$id])->setInc('num');
        echo 0;die;
    }

}