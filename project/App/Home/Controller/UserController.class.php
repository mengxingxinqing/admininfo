<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-08-03
 * Time: 22:58
 */

namespace Home\Controller;

use Base\Service\WechatHelper;
use Home\Service\UserService;
use Partner\Service\WechatService;
use Think\Controller;

class UserController extends Controller
{
    public function ask(){
        $openid = I('openid');
//        $test = I('test');
        $pid = I('pid');
        if(empty($pid)) return;

        $this->assign('pid',$pid);
        $ser = new UserService();
        $uid = $ser->getUid($openid);
        if(empty($openid)){
            return;
        }
        if(empty($uid))return;
        $uinfo = $ser->getUseInfoById($uid);
        $ainfo = $ser->getAddress($uid);
        $this->assign('tel',$ainfo['tel']);
        $this->assign('uinfo',$uinfo);
        $list = $ser->userAsk($uid);
        $this->assign('list',$list);
        $this->assign('answerid',$list[0]['id']);
//        if($test == 1){
            $this->display("asklist");
//        }else{

//            $this->display();
//        }

    }

    public function answer(){
        $id = I('id');
        $content = I('content');
        $pid = I('pid');
        $ser = new WechatService();
        $ser->answer($pid,$id,$content);
        echo SUCCESS;
    }

    public function loseUserOrder(){
        $openid = I('openid');
        if(empty($openid)){
            return;
        }

        $ser = new UserService();
        $uid = $ser->getUid($openid);
        if(empty($uid))return; 

        $data = $ser->getLoseUserInfo($uid);
        
        $this->assign('user_info',$data['user_info']);
        $this->assign('order_info',$data['order_info']);
        $this->display();
    }
    
    public function rechargeSuccess(){
        $this->display();
    }

    public function rechargeError(){
        $this->display();
    }
}