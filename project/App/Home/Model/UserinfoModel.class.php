<?php
namespace Home\Model;
use Base\Model\BaseModel;
use Base\Service\WechatHelper;
use Base\Service\WechatService;

class UserinfoModel extends BaseModel
{
    public $table = "wechat_user";
    public function getCurrUserInfo($uid){

    }

    public function getOrder($uid){

    }

    public function getOpenid($uid){
        return M('wechat_user')->where(array('id'=>$uid))->getField('openid');
    }
    public function getNickname($uid){
        return M('wechat_user')->where(array('id'=>$uid))->getField('nickname');
    }

    public function getShortDistance ( $lngx, $latx, $lngy, $laty )
    {
        $DEF_PI = 3.14159265359; // pi();
        $lon1 = ($DEF_PI / 180) * $lngx;
        $lon2 = ($DEF_PI / 180) * $lngy;
        $lat1 = ($DEF_PI / 180) * $latx;
        $lat2 = ($DEF_PI / 180) * $laty;

        // 地球半径
        $R = 6371;
        // 两点间距离 km，如果想要米的话，结果*1000就可以了
        $d = acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($lon2 - $lon1)) * $R;
        return (int)($d * 1000);
    }


    //获取微信操作对象
    public function getWeObj(){
        $info = S('wechat');
        if(empty($info)){
            $info = M('wechat')->where(array('status'=>'1'))->find();
            S('wechat',$info);
        }
        session('appid',$info['appid']);
        $options = array(
            'token'=>$info['token'],//填写你设定的key
            'appid'=>$info['appid'], //填写高级调用功能的app id
            'appsecret'=>$info['appsecret'] //填写高级调用功能的密钥
        );

        $weObj = new \Com\TPWechat($options);
        return $weObj;
    }

     public function sendbonustpl($tmp_id,$openid,$url,$data){
        $info = M('wechat')->where(array('status'=>'1'))->find();
        session('appid',$info['appid']);
        $options = array(
            'token'=>$info['token'],//填写你设定的key
            'appid'=>$info['appid'], //填写高级调用功能的app id
            'appsecret'=>$info['appsecret'] //填写高级调用功能的密钥
        );

        $weObj = new \Com\TPWechat($options);
        $data['touser'] = $openid;
        $data['template_id'] = $tmp_id;
        $data['url'] = $url;
        $data['topcolor'] = '#FF0000';
        foreach($data as $k =>$v){
            $data['data'][$k]['value'] = $v;
            $data['data'][$k]['color'] = '#173177';
        }
        return $weObj->sendTemplateMessage($data);
    }

    public function sendBonusMsg($openid,$money,$min_money,$lastdate='0',$partner_id){
        $partner_name  = M('partner')->where(array('id'=>$partner_id))->getField('shop_name');
        $data['first'] =  '"'.$partner_name.'"送您一个红包';
        $data['keyword1'] = '"'.$partner_name.'"专属红包（1个）';
        if(empty($lastdate)){
            $extend = '(有效期7天)';
        }else{
            if($lastdate == 1){
                $extend = '(有效期24小时)';
            }else{
                $extend = '(有效期'.$lastdate.'天)';
            }
        }
        $data['keyword2'] = $money.'元'.$extend;
        $data['keyword3'] = date('Y-m-d',time());

        $data['remark'] = '您已得到一个'.$money.'元便利店红包！现在下单就减'.$money.'元，快去选购吧～';
        
        $wechatModel = new WechatService();
//        $url =$wechatModel->createBaseUrl(U('Home/Index/myjf'));
        $res = $this->sendbonustpl(C('SEND_BONUS'),$openid,"",$data);
        \Think\Log::write('sendOne:2  '.json_encode($res));
    }


     public function sendBonusMsgInviter($openid,$money,$min_money,$lastdate='0',$partner_id){
        $partner_name  = M('partner')->where(array('id'=>$partner_id))->getField('shop_name');
        $data['first'] =  '"'.$partner_name.'"送您一个红包';
        $data['keyword1'] = '"'.$partner_name.'"专属红包（1个）';
        if(empty($lastdate)){
            $extend = '(有效期7天)';
        }else{
            if($lastdate == 1){
                $extend = '(有效期24小时)';
            }else{
                $extend = '(有效期'.$lastdate.'天)';
            }
        }
        $data['keyword2'] = $money.'元'.$extend;
         $data['keyword3'] = date('Y-m-d',time());
        $data['remark'] = '您已成功邀请一位同事关注十分便利，恭喜获得一个'.$money.'元邀请红包～';
        
        $wechatModel = new WechatService();
//        $url =$wechatModel->createBaseUrl(U('Home/Index/myjf'));
        $res = $this->sendbonustpl(C('SEND_BONUS'),$openid,"",$data);
        \Think\Log::write('sendOne:2  '.json_encode($res));
    }

    public function getUserAddress($uid,$shop_id=''){
        $where['uid']=$uid;
        $address = M('user_address')->where($where)->find();
        \Think\Log::write("get address ".json_encode($address));
        return $address;
    }

    public function getUserPartnerId($uid){
        $partner_id = M('sender')->where(array('uid'=>$uid))->getField('partner_id');
        return $partner_id;
    }

    public function getAllPartnerId($uid){
        $partner_id = M('sender')->where(array('uid'=>$uid))->getField('partner_id',true);
        return $partner_id;
    }

    public function getShop($uid){
        $shop_id = M('wechat_user')->where(array('id'=>$uid))->getField('shop_id');
        return $shop_id;
    }

    public function addUser($weObj,$inviter,$user_info){
        $user_mod = M('wechat_user');
        $is_exist = $user_mod->where(array('openid'=>$user_info['openid']))->find();
        if(!$is_exist){
            $user_info['status'] = 0;
            $user_info['create_time'] = time();
            $user_info['inviter_id'] = $inviter;
            return $user_mod->add($user_info);
        }
        return null;
    }

    /** 根据openid  获取用户的id
     * @param $openid
     * @return mixed
     */
    public function getUid($openid)
    {
        $key = "getUid_".$openid;
        $uid = S($key);
        if(empty($uid)){
            $user_mod = M('wechat_user');
            $uid = $user_mod->where(array('openid'=>$openid))->getField('id');
            S($key,$uid,TIME_FIVE_MINUTE);
        }
        return $uid;
    }

    /** 根据uid 获取用户的shopid
     * @param $uid
     * @return mixed
     */
    public function getShopId($uid)
    {
        $user_mod = M('wechat_user');
        $shopid = $user_mod->where(array('id'=>$uid))->getField('shop_id');
        return $shopid;
    }

    /** 获取用户临时道二维码
     * @param $pid
     * @param $id
     * @return mixed
     */
    public function getQrcode($pid,$id)
    {
        $info = M('user_qrcode')->where(array('uid'=>$id))->find();
        if(empty($info) || $info['overtime']<=time()){
            $helper = WechatHelper::get($pid);
            $data['qrcode'] = $helper->getMyQrcode($id);
            \Think\Log::write("qrcode = ".$data['qrcode']." errmsg=".$helper->weObj->errMsg);
            $data['overtime'] = time()+1500;
            $data['uid'] = $id;
            if($info){
                M('user_qrcode')->where(array('uid'=>$id))->save($data);
            }else{
                M('user_qrcode')->add($data);
            }
            return $data['qrcode'];
        }else{
            return $info['qrcode'];
        }
    }

    /** 用户选择店铺
     * @param $pid
     * @param $uid
     * @param $sid
     */
    public function selectShop($pid,$uid, $sid)
    {
        M('wechat_user')->where(array('id'=>$uid))->save(array('shop_id'=>$sid));
        $openid = M('wechat_user')->where(array('id'=>$uid))->getField('openid');
        $groupid = M('wechat_group')->where(array('partner_id'=>$pid,'shop_id'=>$sid))->getField('group_id');
        $helper = WechatHelper::get($pid);
        $helper->joinGroup($groupid,$openid);
    }

    public function getUserBuyTimes($pid, $uid)
    {
        return M('user_info')->where(array('partner_id'=>$pid,'uid'=>$uid))->getField('buy_times');
    }

    public function getLoseUserInfo($uid){
        $sql = "SELECT u.nickname,u.create_time,u.leave_time,ui.buy_times,ui.buy_moneys,ui.last_time,ua.name,ua.tel,ua.address,ua.detail_address,s.name AS shop_name FROM sm_wechat_user u LEFT JOIN sm_user_info ui ON u.id=ui.uid LEFT JOIN sm_user_address ua ON u.id=ua.uid LEFT JOIN sm_shop s ON u.shop_id=s.id WHERE u.id=".$uid;
        $res = M()->query($sql);

        $data['user_info'] = $res[0];

        $order = M('order')->where(array('uid'=>$uid))->order('create_time desc')->find();
        if(!empty($order)){
            $detail_list = M('order_detail')->where(array('order_id'=>$order['id']))->select();
            $order['detail'] = $detail_list;
        }else{
            $order = M('order_bak')->where(array('uid'=>$uid))->order('create_time desc')->find();
            $detail_list = M('order_detail_bak')->where(array('order_id'=>$order['id']))->select();
            $order['detail'] = $detail_list;
        }

        $activity = M('order_activity')->where(array('oid'=>$order['id']))->select();
        $order['activity'] = $activity;

        $data['order_info'] = $order;
        return $data;
    }

    public function getUserRechargeSet($partner_id){
        $list = M('user_recharge_set')->where(array('partner_id'=>$partner_id))->select();
        return $list;
    }

    public function getUserRechargeSetById($id){
        $res = M('user_recharge_set')->where(array('id'=>$id))->find();
        return $res;
    }

    public function getUserRechargeSetByMoney($partner_id,$money){
        $res = M('user_recharge_set')->where("partner_id=".$partner_id." AND money<=".$money)->order('money desc')->find();
        $res['money'] = $money;
        if(empty($res['giveaway_money'])){
            $res['giveaway_money'] = 0;
        }
        return $res;
    }

    public function addUserRecharge($partner_id,$shop_id,$user_id,$order_num,$recharge_info){
        $data['partner_id'] = $partner_id;
        $data['shop_id'] = $shop_id;
        $data['uid'] = $user_id;
        $data['money'] = $recharge_info['money'];
        $data['giveaway_money'] = $recharge_info['giveaway_money'];
        $data['qrcode_id'] = $recharge_info['qrcode_id'];
        $data['order_num'] = $order_num;
        $data['create_time'] = time();
        $account_money = M('user_info')->where(array('uid'=>$user_id))->getField('account_money');
        $data['curr_money'] = $account_money;
        $data['bonus_money'] = empty($recharge_info['bonus_money']) ? 0 : $recharge_info['bonus_money'];
        $data['bonus_num'] = empty($recharge_info['bonus_num']) ? 0 : $recharge_info['bonus_num'];
        $data['bonus_param1'] = empty($recharge_info['bonus_param1']) ? 0 : $recharge_info['bonus_param1'];
        $data['bonus_param2'] = empty($recharge_info['bonus_param2']) ? 0 : $recharge_info['bonus_param2'];
        $data['is_custom'] = empty($recharge_info['is_custom']) ? 0 : $recharge_info['is_custom'];
        $id = M('user_recharge_log')->add($data);
        return $id;
    }

    public function getUserAccountMoney($uid){
        $res = M('user_info')->where(array('uid'=>$uid))->getField('account_money');
        return $res;
    }

    public function getUserRechargeDetail($uid,$type,$page){
        if($type == 1){
            $res = M('user_recharge_log')->where(array('uid'=>$uid,'status'=>1))->order('create_time desc')->limit($page,5)->select();
        }else{
            $res = M('user_recharge_declog')->where(array('uid'=>$uid))->order('create_time desc')->limit($page,5)->select();
        } 
        return $res;
    }

    public function rechargeDec($order_id){
        $order_info = M('order')->where(array('id'=>$order_id))->find();
        $curr_money = M('user_info')->where(array('uid'=>$order_info['uid']))->getField('account_money');
        $data['uid'] = $order_info['uid'];
        $data['order_id'] = $order_id;
        $data['partner_id'] = $order_info['partner_id'];
        $data['shop_id'] = $order_info['shop_id'];
        $data['order_money'] = $order_info['pay_money'];
        $data['money'] = $order_info['pay_money'];
        $data['curr_money'] = $curr_money;
        $data['create_time'] = time();
        $data['type'] = 1;
        M('user_recharge_declog')->add($data);
        M('user_info')->where(array('uid'=>$order_info['uid']))->setDec('account_money',$order_info['pay_money']);
    }

    public function pointRechargeDec($pid,$sid,$uid,$point,$money){
        $curr_money = M('user_info')->where(array('uid'=>$uid))->getField('account_money');
        if($money>$curr_money){
            return 1;
        }
        $data['uid'] = $uid;
        $data['partner_id'] = $pid;
        $data['shop_id'] = $sid;
        $data['point_id'] = $point;
        $data['money'] = $money;
        $data['curr_money'] = $curr_money;
        $data['create_time'] = time();
        $data['type'] = 3;
        M('user_recharge_declog')->add($data);
        M('user_info')->where(array('uid'=>$uid))->setDec('account_money',$money);
        return 0;
    }

    public function pointPayList($uid,$pid,$limit=5)
    {
        if($pid != 0){
            $s = "and d.point_id=".$pid;
        }
        $sql = "select d.*,p.name from sm_user_recharge_declog d LEFT JOIN sm_pick_building p on p.id=d.point_id where d.type=3  ".$s." and d.uid=".$uid." order by d.id desc limit ".$limit;
        $list = M()->query($sql);
        return $list;
    }

    
    public function getRechargeQrcode($uid){
        $res = M('user_recharge_qrcode')->where(array('uid'=>$uid))->find();
        return $res;
    }

    public function getUserHead($pid,$uid){
        $res = M('wechat_user')->where(array('id'=>$uid))->getField('headimgurl');
        return $res;
    }
}