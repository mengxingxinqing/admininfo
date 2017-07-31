<?php
namespace Partner\Controller;
use Partner\Service\OrderService;
use Partner\Service\PartnerService;
use Partner\Service\ShopService;
use Partner\Service\UserService;
use Think\Controller;
class IndexController extends BaseController {
    public function index(){
        $userser = new UserService();
        $newuser = $userser->getNewUserNum($this->partner_id);
        $this->assign('newuser',$newuser);
        $totalUser = $userser->totalUser($this->partner_id);
        $this->assign('totaluser',$totalUser);
        $orderser = new OrderService();
        $yesMoney = $orderser->getDayMoney($this->partner_id,date("Y-m-d",strtotime("-1 day")));
        $this->assign('yesmoney',$yesMoney);
        $todayMoney = $orderser->getTodayMoney($this->partner_id);
        $this->assign('todaymoney',$todayMoney);
        $ordernum = $orderser->getTodayOrderNum($this->partner_id);
        $this->assign('order_num',$ordernum);

        $partnerser = new PartnerService();
        $account_money = $partnerser->getPartnerAccountMoney($this->partner_id);
        $this->assign('account_money',$account_money);

        $today_shoppay = $orderser->getDaysShopPay($this->partner_id,strtotime('today'));
        $yes_shoppay = $orderser->getDaysShopPay($this->partner_id,strtotime('yesterday'));
        $this->assign('today_shoppay',$today_shoppay);
        $this->assign('yes_shoppay',$yes_shoppay);

        $rate = $partnerser->getPartnerRate($this->partner_id);
        $this->assign('rate',$rate);

        $shopser = new ShopService();
        $list = $shopser->getShopInfoList();
        $this->assign('list',$list);
        $this->display();
    }

    //开关店
    public function closeShop(){
        $shop_id = I('shop_id');
        $now_status = I('now_status');
        $service = new ShopService();
        $service->closeShop($shop_id,$now_status);
        echo json_encode(array('status'=>'ok'));
    }

    //开启暂不外送
    public function noSend(){
        $shop_id = I('shop_id');
        $now_status = I('now_status');
        $service = new ShopService();
        $service->noSend($shop_id,$now_status);
        echo json_encode(array('status'=>'ok'));
    }

    public function welcome(){
        $this->display();
    }



}