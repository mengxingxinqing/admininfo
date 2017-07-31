<?php
namespace Home\Service;
use Home\Model\ShopModel;
use Home\Model\PartnerModel;
use Home\Model\OrderModel;
use Home\Model\UserinfoModel;
class PartnerService
{
    public function goodsList($uid,$shop_id){
    	
        $shopmodel = new ShopModel();
        $partnermodel = new PartnerModel();
        $partner_id = $partnermodel->getPartnerId($uid);
        $list = $shopmodel->getGoodsList($partner_id,$shop_id);
        return $list;
    }

    public function goodsPageList($uid,$shop_id,$p,$cate){
        $shopmodel = new ShopModel();
        $partnermodel = new PartnerModel();
        $partner_id = $partnermodel->getPartnerId($uid);
        $list = $shopmodel->getGoodsPageList($partner_id,$shop_id,$p,$cate);
        return $list;
    }

    public function getPartnerStatistics($uid,$type){
        $partnermodel = new PartnerModel();
        $partner_id = $partnermodel->getPartnerId($uid);
        return $partnermodel->getPartner_Account_Money($partner_id,$type);
    }

    public function getPartnerShop($uid){
    	$model = new ShopModel();
    	return $model->getPartnerShop($uid);
    }

    public function shopCateList($uid,$shop_id){
        $model = new ShopModel();
        $partnermodel = new PartnerModel();
        $partner_id = $partnermodel->getPartnerId($uid);
        return $model->getshopCate($partner_id,$shop_id);
    }

    public function changeMaxTime($id,$max_time){
        $model = new ShopModel();
        return $model->setShopGoodsTime($id,$max_time);
    }

    public function setShopgoodsOff($id,$val){
        $model = new ShopModel();
        
        return $model->setShopGoodsAttr($id,$val);
    }


    public function getLostUser($uid,$shop_id){
        $partnermodel = new PartnerModel();
        $partner_id = $partnermodel->getPartnerId($uid);
        //10天前的时间
        $time =time() - 10*3600*24;
        $model = new OrderModel();
        $list = $model->getLostUser($partner_id,$shop_id,$time);
        $count = count($list)-10;
        $index = rand(0,$count);
        $list = array_slice($list,$index,5);
        return $list;
    }


    public function getRichUser($uid,$shop_id){
        $partnermodel = new PartnerModel();
        $partner_id = $partnermodel->getPartnerId($uid);
        $model = new OrderModel();
        $list = $model->getRichUser($partner_id,$shop_id);
        return $list;
    }

    public function getNewUser($uid,$shop_id){
        $partnermodel = new PartnerModel();
        $partner_id = $partnermodel->getPartnerId($uid);
        $model = new OrderModel();
        $list = $model->getNewUser($partner_id,$shop_id);
        
        /*剔除没有下过单新用户*/
        foreach ($list as $key => $value) {
            if (empty($value['tel'])) {
                unset($list[$key]);
            }
        }
        $count = count($list)-10;
        $index = rand(0,$count);
        $list = array_slice($list,$index,5);

        return $list;
    }



    public function orderList($uid,$shop_id,$p){
        $partnermodel = new PartnerModel();
        $partner_id = $partnermodel->getPartnerId($uid);
        $model = new OrderModel();
        $list = $model->getOrderList($partner_id,$shop_id,$p);
        return $list;
    }

    public function changeClose($id,$msg){
        $is_close = M('partner_shop')->where(array('id'=>$id))->getField('is_close');
        if ($is_close == 0) {
            $status['is_close'] = 1;
            $status['show_msg'] = $msg;

        }else{
           $status['is_close'] = 0;
           $status['show_msg'] = ''; 
        }
        
        $res_save = M('partner_shop')->where(array('id'=>$id))->save($status);
        if ($res_save) 
            return true;
        else
            return false;

    }

    public function getPartnerMsg($pid){
        $alert = M('partner_shop')->where(array('partner_id'=>$pid,'shop_id'=>session('shop')))->getField('alert_msg');
        $msg = M('partner')->where(array('id'=>$pid))->field("show_msg,shop_address")->find();
        if(!empty($alert)){
            $msg['show_msg'] = $alert;
        }
        return $msg;
    }

    public function getPartnerCount($shopid){
//        $num = M('partner_shop')->where(array('shop_id'=>$shopid))->count();
        $sql = "select count(*) as num from tm_partner_shop ps left join tm_partner p on p.id=ps.partner_id where ps.shop_id=$shopid and p.status=0";
        $res = M()->query($sql);
        return $res[0]['num'];
    }

    public function changeSend($id){
        $no_send = M('partner_shop')->where(array('id'=>$id))->getField('no_send');
        if ($no_send == 0) {
            $status['no_send'] = 1;

        }else{
           $status['no_send'] = 0; 
        }
        
        $res_save = M('partner_shop')->where(array('id'=>$id))->save($status);
        if ($res_save) 
            return true;
        else
            return false;
    }

    public function getType($partner_id,$shop_id){
        $model = new PartnerModel();
        return $model->getType($partner_id,$shop_id);
    }

    public function getPartnerStatus($shop){
        $model = new PartnerModel();
        return $model->getPartnerStatus($shop);
    }
    
    /** 根据id获取合作商户信息
     * @param $pid
     * @return mixed
     */
    public function getPartnerById($pid)
    {
        $info = M('partner')->find($pid);
        return $info;
    }

    /** 获取公众号名称
     * @param $pid
     * @return mixed
     */
    public function getWechatName($pid)
    {
        $key = "getWechatName_".$pid;
        $name = S($key);
        if(empty($name)) {
            $name = M("wechat")->where(array('partner_id' => $pid))->getField('name');
            S($key,$name,TIME_ONE_DAY);
        }
        return $name;
    }

    /** 获取合作商户的城市
     * @param $pid
     * @return mixed
     */
    public function getPartnerCity($pid)
    {
        $key = "getPartnerCity_".$pid;
        $res = S($key);
        if(empty($res)){
            $model = new PartnerModel();
            $res = $model->getPartnerCity($pid);
            S($key,$res,TIME_ONE_DAY);
        }
        return $res;
    }

    /** 获取合作商户名字
     * @param $id
     * @return mixed
     */
    public function getPartnerName($id)
    {
        $name = M('partner')->where(array('id'=>$id))->getField('name');
        return $name;
    }

    /** 获取合作商户的模板id号
     * @param $pid 合作商户
     * @param $type 模板类型
     * @return mixed
     */
    public function getTplId($pid,$type){
        $tpl = M('wechat_tpl')->where(array('partner_id'=>$pid,'type'=>$type))->getField('tmpid');
        return $tpl;
    }

    public function getSendMoney($partner_id,$shop_id,$uid){
        $partner_model = new PartnerModel();
        $type = $partner_model->getSendChargeType($partner_id,$shop_id);
        if($type == 1){
            //固定收费
            $user_model = new UserinfoModel();
            $user_address = $user_model->getUserAddress($uid);

            $shop_model = new ShopModel();
            $shop_info = $shop_model->getShopInfo($shop_id);
            $distance = $user_model->getShortDistance($shop_info['lng'],$shop_info['lat'],$user_address['lng'],$user_address['lat']);
            
            if(empty($user_address['lat']) || empty($user_address['lng'])){
                //用户地址定位为空
                $send_info['charge_type'] = 1;
                $send_info['msg'] = '请填写您的收货地址';
                $send_info['send_money'] = 0;
            }elseif($distance > $shop_info['send_range']*1000){
                $send_info['charge_type'] = 1;
                $send_info['msg'] = '抱歉，您的地址距本店过远，不能送达';
                $send_info['send_money'] = 0;
            }else{
                $res = $partner_model->getSendCharge($partner_id,$shop_id,1);
                $send_info['charge_type'] = 1;
                $send_info['msg'] = '成功'; 
                $send_info['send_money'] = $res[0]['send_price'];
                $send_info['send_interval'] = $res[0]['send_interval'];
            }        
        }else{
            //按距离收费
            $user_model = new UserinfoModel();
            $user_address = $user_model->getUserAddress($uid);
            // $user_address['lat'] = '';
            if(empty($user_address['lat']) || empty($user_address['lng'])){
                //用户地址定位为空
                $send_info['charge_type'] = 2;
                $send_info['msg'] = '请填写您的收货地址';
                $send_info['send_money'] = 0;
            }else{
                $shop_model = new ShopModel();
                $shop_info = $shop_model->getShopInfo($shop_id);
                $distance = $user_model->getShortDistance($shop_info['lng'],$shop_info['lat'],$user_address['lng'],$user_address['lat']);
                // var_dump($distance);die;

                $charge_list = $partner_model->getSendCharge($partner_id,$shop_id,2);
                foreach($charge_list as $charge){
                    if($distance >= $charge['send_range_start'] && $distance < $charge['send_range_end']){
                        $send_info['charge_type'] = 2;
                        $send_info['msg'] = '成功'; 
                        $send_info['send_money'] = $charge['send_price'];
                        $send_info['send_interval'] = $charge['send_interval'];
                    }
                }
                if(empty($send_info)){
                    $send_info['charge_type'] = 2;
                    $send_info['msg'] = '抱歉，您的地址距本店过远，不能送达';
                    $send_info['send_money'] = 0;
                }  
            }
        }
        return $send_info;
    }

    public function getSendInfo($pid,$sid,$uid)
    {
        $user_model = new UserinfoModel();
        $user_address = $user_model->getUserAddress($uid);
        if(empty($user_address['lat']) || empty($user_address['lng'])){
            //用户地址定位为空
            $send_info['status'] = 2;
            return $send_info;
        }else{
            $partner_model = new PartnerModel();
            $type = $partner_model->getSendChargeType($pid,$sid);
            $shop_model = new ShopModel();
            $shop_info = $shop_model->getShopInfo($sid);
            $distance = $user_model->getShortDistance($shop_info['lng'],$shop_info['lat'],$user_address['lng'],$user_address['lat']);
            $send_info['upoint']['lng'] = $user_address['lng'];
            $send_info['upoint']['lat'] = $user_address['lat'];
            if($shop_info['send_range'] != 0 && $distance > $shop_info['send_range']*1000) {
                $send_info['status'] = 1;
                return $send_info;
            }
            $send_info['send_type'] = $type;
            $send_info['status'] = 0;
            $send_info['polygon'] = $partner_model->getPolygonSend($sid);
            $send_info['send_money'] = -1;
            if($type == 1 || $type == 2){
                $send_info['status'] = 0;
                $res = $partner_model->checkCharge($pid,$sid,$type,$distance);
                if($res == 0){
                    $send_info['send_money'] = 0;
                }else if($res == null){
                    $send_info['status'] = 3;
                }else{
                    $send_info['send_money'] = $res;
                }
            }
            return $send_info;
        }
    }

    public function getConf($pid){
        $info = M('partner_conf')->where(array('partner_id'=>$pid))->find();
        return $info;
    }

    public function getPayTo($sid){
        return PAYTO_PARTNER;
        $info = M('shop_conf')->where(array('shop_id'=>$sid))->getField("curr_status");
        return $info;
    }

    public function getLeaveMoney($pid)
    {
        $money = M('partner')->where(array('id'=>$pid))->getField('money');
        if($money<MONEY_LINE){
            return false;
        }
        return true;
    }

    public function getShopNum($pid)
    {
        $num = M('shop')->where(array('partner_id'=>$pid,'is_del'=>0))->count();
        return $num;
    }

    public function reserveInfo($pid,$sid)
    {
        $info = M('partner_conf')->where(array('partner_id'=>$pid))->field('time_need_status')->find();
        $shop_conf = M('shop_conf')->where(array('shop_id'=>$sid))->field('start_time,end_time,send_interval,reserve_status,reserve_day,fixed_time_status,sendtime_start,sendtime_end,close_firstday_reserve')->find();
        $data['reserve_status'] = $shop_conf['reserve_status'];
        $data['reserve_day'] = $shop_conf['reserve_day'];
        $data['fixed_time_status'] = $shop_conf['fixed_time_status'];
        $data['time_need_status'] = $info['time_need_status'];
        $data['start_time'] = $shop_conf['start_time'];
        $data['end_time'] = $shop_conf['end_time'];
        $data['sendtime_start'] = $shop_conf['sendtime_start'];
        $data['sendtime_end'] = $shop_conf['sendtime_end'];
        $data['close_firstday_reserve'] = $shop_conf['close_firstday_reserve'];
        // $data['send_interval'] = $shop_conf['send_interval'];
        $reserve = M('reserve_conf')->where(array('partner_id'=>$pid,'shop_id'=>$sid))->order('pre_time asc')->select();
        $data['conf'] = $reserve;
        $data['now_time'] = date('H:i',time());
        $uid = session('uid');
        $send_info = self::getSendMoney($pid,$sid,$uid);
        $data['send_interval'] = empty($send_info['send_interval']) ? 20 : $send_info['send_interval'];
        // var_dump($send_info);die;
        return $data;
    }

    //自提时间
    public function pickTimeInfo($pid,$sid,$uid)
    {
        $info = M('partner_conf')->where(array('partner_id'=>$pid))->field('time_need_status')->find();
        $shop_conf = M('shop_conf')->where(array('shop_id'=>$sid))->field('start_time,end_time,send_interval,is_pick,reserve_status,reserve_day,fixed_time_status,close_firstday_reserve')->find();
        $data['reserve_status'] = $shop_conf['reserve_status'];
        $data['reserve_day'] = $shop_conf['reserve_day'];
        $data['fixed_time_status'] = $shop_conf['fixed_time_status'];
        $data['time_need_status'] = $info['time_need_status'];
        $data['start_time'] = $shop_conf['start_time'];
        $data['end_time'] = $shop_conf['end_time'];
        $data['send_interval'] = $shop_conf['send_interval'];
        $data['is_pick'] = $shop_conf['is_pick'];
        $data['close_firstday_reserve'] = $shop_conf['close_firstday_reserve'];

        $pick_id = M('user_address')->where(array('uid'=>$uid,'partner_id'=>$pid))->getField('pick_id');
        if(empty($pick_id)){
            $reserve = M('pick_time')->where(array('shop_id'=>$sid,'pick_point_id'=>0))->order('pick_start_time asc')->select();
        }else{
            $reserve = M('pick_time')->where(array('pick_point_id'=>$pick_id))->order('pick_start_time asc')->select();
            if(empty($reserve)){
                $reserve = M('pick_time')->where(array('shop_id'=>$sid,'pick_point_id'=>0))->order('pick_start_time asc')->select();
            }
        }
        
        $data['conf'] = $reserve;
        $data['now_time'] = date('H:i',time());
        return $data;
    }

    public function isSetWeek($partner_id){
        $res = M('partner_conf')->where(array('partner_id'=>$partner_id))->getField('is_week');
        return $res;
    }

//    private function createInvTime($start,$end,$send_int,$len){
//        $h = date('H');
//        $m = date('i');
//        $m = ceil($m/10)*10;
//        $r = self::addTime($h,$m,$send_int);
//        $s = $r['h'].':'.$r['m'];
//        $index = 0;
//        $res[$index][] = $s;
//        while($s < $end){
//            $r = self::addTime($r['h'],$r['m'],20);
//            $s = $r['h'].':'.$r['m'];
//            $res[$index][] = $s;
//        }
//
//        for($i=0;$i<$len-1;$i++){
//
//        }
//        return $res;
//    }

//    private function createSpanTime($start,$end,$span){
//        while($start < $end){
//            $r = self::addTime($r['h'],$r['m'],20);
//            $s = $r['h'].':'.$r['m'];
//            $res[$index][] = $s;
//        }
//    }

//    private function createInvDay($len)
//    {
//        $arr = array('今天','明天');
//        $weekarray=array("日","一","二","三","四","五","六");
//        for($i=2;$i<7;$i++){
//            $day = strtotime('+'.$i.' day');
//            $arr[$i] = "星期".$weekarray[date('w',$day)];
//        }
//        return array_slice($arr, 0, $len);
//    }

//    private function createTimes($reserve,$len){
//        $t = date('H:i');
//        $index = 0;
//        for($j=0;$j<count($reserve);$j++){
//            if($reserve[$j]['buy_time']>=$t){
//                $arr[$index][] = $reserve[$j]['pre_time'];
//            }
//        }
//        for($i=0;$i<$len-1;$i++){
//            $index++;
//            for($j=0;$j<count($reserve);$j++){
//                $arr[$index][] = $reserve[$j]['pre_time'];
//            }
//        }
//        return $arr;
//    }
//
//    private function addTime($h,$m,$a){
//        $m = intval($m)+intval($a);
//        if($m >= 60){
//            $m = $m%60;
//            $h = $h+1;
//        }
//        if($h<10){
//            $h = '0'.$h;
//        }
//        if($m<10){
//            $m = '0'.$m;
//        }
//        return array('h'=>$h,'m'=>$m);
//    }

}