<?php
namespace Partner\Controller;
use Partner\Service\UserService;
use Think\Controller;

class UserController extends BaseController {
    public function index(){ 
        $p = I('p');
        $shop_id = I('shop_id',0);
        $data = I();
    	$service = new UserService();
        $list = $service->getUserList($p,$shop_id,$data);
        //关注数据
        $follow_data = $service->followData($shop_id);
        $shop_list = $service->getShopList();
        $this->assign('list',$list);
        $this->assign('follow_data',$follow_data);
        $this->assign('shop_list',$shop_list);
        $this->assign('shop_id',$shop_id);
        $this->assign('search_key',$search_key);
        $this->assign('start_time',$data['start_time']);
        $this->assign('end_time',$data['end_time']);
        $this->assign('buy_moneys_start',$data['buy_moneys_start']);
        $this->assign('buy_moneys_end',$data['buy_moneys_end']);
        $this->assign('buy_times_start',$data['buy_times_start']);
        $this->assign('buy_times_end',$data['buy_times_end']);
        $this->assign('invite_num_start',$data['invite_num_start']);
        $this->assign('invite_num_end',$data['invite_num_end']);
        $this->display();
    }

    public function setRemark(){
        $uid = I('uid');
        $remark = I('remark');
        $service = new UserService();
        $service->setRemark($uid,$remark);
    }

    public function setAddress(){
        $uid = I('uid');
        $address = I('address');
        $service = new UserService();
        $service->setAddress($uid,$address);
    }

    public function BonusList(){
        $p = I('p');
        $shop_id = I('shop_id',0);
        $start_time = I('start_time');
        $end_time = I('end_time');
        $service = new UserService();
        $shop_list = $service->getShopList();

        $list = $service->getBonusList($p,$shop_id,$start_time,$end_time);
        $this->assign('list',$list);
        $this->assign('shop_list',$shop_list);
        $this->assign('shop_id',$shop_id);
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->display();
    }

    public function sendBonus(){
        $data = I();
        $user_id = $data['user_id'];
        $money = $data['money'];
        $param = empty($data['param']) ? 0 : $data['param'];
        $day = $data['day'];
        $bonus_name = $data['bonus_name'];
        $service = new UserService();
        $bonus_id = $service->sendBonus($user_id,$money,$param,$day,$bonus_name);
        if($bonus_id){
            echojson('红包发送成功',U('User/index'));
        }
    }

    /**
     * 用户热力图
     */
    public function userHot()
    {
        $ser = new UserService();
        $list = $ser->userHot($this->partner_id);
        $city = $ser->getPartnerCity($this->partner_id);
        $this->assign('city',$city);
        $this->assign("list",$list);
        $this->display();
    }

    public function orderTime(){
//        $pid,$start,$end
        
    }


    //流失用户召回
    public function userBack(){
        $partner_id = session('partner_id');
        $p = I('p');
        $shop_id = I('shop_id',0);

        $service = new UserService();
        $follow_data = $service->followData($shop_id);
        $shop_list = $service->getShopList();

        $list = $service->getUserBackList($p,$partner_id,$shop_id);
        $this->assign('shop_list',$shop_list);
        $this->assign('list',$list);
        $this->display();
    }

    public function sendBonusAlert(){
        $id = I('user_id');
        $service = new UserService();
        $user_info = $service->getUserInfoById($id);
        $this->assign('user_info',$user_info);
        $this->display();
    }

    public function userInfoAlert(){
        $this->display();
    }
}