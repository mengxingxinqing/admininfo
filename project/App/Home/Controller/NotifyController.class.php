<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-24
 * Time: 16:34
 */

namespace Home\Controller;


use Home\Service\BonusService;
use Home\Service\NotifyService;
use Home\Service\OrderService;
use Think\Controller;

class NotifyController extends Controller
{
    public function wx()
    {
        $id = I('get.id');
        if(empty($id)){
            $url = $_SERVER['PHP_SELF'];
            $pos = strripos($url,'/');
            $id = substr($url,$pos+1);
        }
        $ser = new NotifyService();
        $ser->process($id,'wx');
        echo 'SUCCESS';exit;
    }

    public function qrcode(){
//        $id = I('get.productid');
//        $openid = I('get.openid');
//        $ser = new NotifyService();
//        $data['openid'] = $openid;
//        $ser->process($id,'wx',$data);
//        \Think\Log::write("pay info openid=".$openid.' pid='.$id);
//        echo 'SUCCESS';exit;
    }

    public function shoppay()
    {
        $id = I('get.id');
        if(empty($id)){
            $url = $_SERVER['PHP_SELF'];
            $pos = strripos($url,'/');
            $id = substr($url,$pos+1);
        }
        $ser = new NotifyService();
        $ser->shoppay($id);
        echo 'SUCCESS';exit;
    }

    public function backpay()
    {
        $id = I('get.id');
        if(empty($id)){
            $url = $_SERVER['PHP_SELF'];
            $pos = strripos($url,'/');
            $id = substr($url,$pos+1);
        }
        $ser = new NotifyService();
        $ser->processPayback($id);
        echo 'SUCCESS';exit;
    }

    public function test(){
        echo '666';
    }


    //充值回调
    public function userRechargCallBack(){
        $id = I('get.id');
        $ser = new NotifyService();
        $ser->processUserRecharge($id);
        echo 'SUCCESS';exit;
    }
}