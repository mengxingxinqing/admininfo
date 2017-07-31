<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-09-08
 * Time: 14:53
 */

namespace Home\Controller;


use Base\Service\WechatHelper;
use Home\Service\PartnerService;
use Think\Controller;
use Home\Service;

class ShoppayController extends Controller
{

    /** openid
     * @param $pid
     * @return bool|mixed
     */
    private function getOpenid($pid)
    {
        $helper = WechatHelper::get($pid);
        $json = $helper->weObj->getOauthAccessToken();
        if($json){
            $openid = $json['openid'];
            \Think\Log::write("tong-test 1= openid=".$openid);
        }
        \Think\Log::write("get open id ".$helper->weObj->errMsg);
        return $openid;
    }

    /**
     * 到店付
     */
    public function index()
    {
        $key = I('paykey');
        if(empty($key)) return null;
        $str = base64_decode($key);
        $str = explode('-',$str);
        $pid = $str[0];
        $sid = $str[1];
        \Think\Log::write("tong-strs= pid=".$pid.'  sid='.$sid);
        $openid = session('pay_openid_'.$pid);
        if(empty($openid)){
            $openid = self::getOpenid($pid);
            session('pay_openid_'.$pid,$openid);
        }
        $this->assign('openid',$openid);
        $this->assign('pid',$pid);
        $this->assign('sid',$sid);
        $ser = new PartnerService();
        $info = $ser->getPartnerById($pid);
        $this->assign('info',$info);
        $this->display();
    }

    /**
     * 生成到店付的订单
     */
    public function createShopOrder()
    {
        $money = I('m');
        $data['partner_id'] = I('pid');
        $data['shop_id'] = I('sid');
        $uid = '';
        $openid = I('openid');
        $data['uid'] = $uid;
        $data['openid'] = $openid;
        $data['order_num'] = order_num($uid);
        $data['total_money'] = $money;
        $data['pay_money'] = $money;
        $data['pay_type'] = PAY_WX;
        $data['create_time'] = time();
        $data['is_pay'] = 0;
        \Think\Log::write("tong-data = ".json_encode($data));
        $id = M('shop_pay')->add($data);

        $helper = WechatHelper::get($data['partner_id']);
        try{
            $ext['notify_url'] = C('BASE_URL').U("Home/Notify/shoppay",array('id'=>$id));
            $apiparam = $helper->getJsPayParam($id,$openid,$data['order_num'],$money,PAYTO_PARTNER,$ext);
        }catch (\WxPayException $e){
            \Think\Log::write('wxpay-error1 '.$e->errorMessage());
        }
        echo $apiparam;exit;
    }
}