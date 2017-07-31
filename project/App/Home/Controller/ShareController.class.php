<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-20
 * Time: 17:52
 */

namespace Home\Controller;


use Base\Service\WechatHelper;
use Home\Service\PartnerService;
use Home\Service\ShareService;
use Home\Service\UserService;
use Think\Controller;

class ShareController extends Controller
{
    public function index()
    {
        $uid = I('f');
        $pid = I('p');
        if(empty($pid)){
            echo 'error';exit;
        }
        $pser = new PartnerService();
        $pinfo = $pser->getPartnerById($pid);
        $this->assign('pinfo',$pinfo);
        if(!empty($uid)){
            $uid = base64_decode($uid);
            $ser = new ShareService();
            $setting = $ser->getSetting($pid);
            $ser = new ShareService();
            $share = $ser->getShare($pid,$uid);
            $this->assign('share',$share);
            if($setting['is_open'] != 1){
                $this->display('sub');
            }else{
                $userser = new UserService();
                $uinfo = $userser->getUseInfoById($uid);
                $qrcode = $userser->getQrcode($pid,$uid);
                $this->assign('qrcode',$qrcode);
                $this->assign('user',$uinfo);
                $this->assign('setting',$setting);
                $this->assign('id',$uid);
                $this->display();
            }
        }else{
            $this->display('sub');
        }
    }

    public function selectpay()
    {
        $pid = I('pid');
        $sid = I('sid');
        $helper = WechatHelper::get($pid);
        $str = $pid.'-'.$sid;
        $key = base64_encode($str);
        $url = $helper->getUrl(C('BASE_URL').U("Home/Shoppay/index",array('paykey'=>$key)));
        $url = str_replace("//index","/index",$url);
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            redirect($url);
        }
        $this->assign('url',$url);
        $this->display();
    }
}