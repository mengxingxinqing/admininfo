<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-08-12
 * Time: 10:35
 */

namespace Partner\Controller;


use Base\Service\WechatHelper;
use Partner\Service\ShopService;

class MoneyController extends BaseController
{
    public function index()
    {
        $pid = $this->partner_id;
//        $conf = $this->service->getConf($pid);
//        $conf['rate'] = 100*$conf['rate'];
//        $this->assign('conf',$conf);
        $page = I('p');
        $loglist = $this->service->getLeastLog($pid,$page);
        $this->assign('log',$loglist);
//        $monthInfo = $this->service->getMonthMoney($pid);
//        if(empty($monthInfo)){
//            $m = 0;
//        }else{
//            $m = $monthInfo['money'];
//        }
//        $this->assign('m',$m);
        $list = $this->service->getShopMoneyList($pid);
        $this->assign('list',$list);
        $this->display();
    }

    public function showPay()
    {
        $sid = I('id');
        $this->assign("sid",$sid);
        $this->display();
    }

    public function pay()
    {
        $money = 200;
        $sid = I('sid');
        $pid = $this->partner_id;
//        $conf = $this->service->getConf($pid);
        $ser = new ShopService();
        $conf = $ser->getConf($sid);
        $data['money'] = $money;
        $data['order_num'] = order_num($pid);
        $data['curr_money'] = $conf['money'];
        $data['partner_id'] = $pid;
        $data['create_time'] = time();
        $data['shop_id'] = $sid;
        $order_id = $this->service->addOrder($data);
        $helper = WechatHelper::get($pid);
        $payconf = C('DEFAULT_PAY');
        $payconf['notify_url'] = C('BASE_URL').U("Home/Notify/backpay",array('id'=>$order_id));
        $apiparam = $helper->getJsPayParam($order_id,"",$data['order_num'],$money,PAYTO_SYSTEM,$payconf);
        echo $apiparam;exit;
    }

    public function declist()
    {
        $list = $this->service->getDeclist($this->partner_id);
        $this->assign('list',$list);
        $this->display();
    }
}