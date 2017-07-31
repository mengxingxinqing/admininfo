<?php
namespace Partner\Model;
use Base\Service\WechatHelper;
use Home\Service\ActivityService;
use Home\Model\OrderModel AS HomeOrderModel;

/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-17
 * Time: 16:32
 */
class OrderModel extends BaseModel
{
    public static $STATUS_FINISH = 3;
    public static $STATUS_NORECEIVE = 0;
    public static $STATUS_CANCEL = 4;
    public static $DEL_NO = 0;
    public function getShopList(){
        $partner_id = session('partner_id');
        $res = S('shopList_'.$partner_id);
        $res = '';
        if(empty($res)){
            $res = M('shop')->where(array('partner_id'=>$partner_id,'is_del'=>0))->select();
            S('shopList_'.$partner_id,$res,TIME_ONE_HOUR);
        } 
        return $res;
    }

    public function getOrderList($p,$partner_id,$shop_id,$key,$reserve_time,$is_reserve=0,$pick_id){
        $where = '';
        if($shop_id != 0){
            $where = ' AND o.shop_id='.$shop_id;     
        }
        if($key != ''){
            $where .= sprintf(' AND (o.user_tel="%s" OR o.sqnum="%s")',$key,$key);
        }

        if($pick_id !=0){
            $where .= ' AND pick_id='.$pick_id;
        }

        $reserve_start = strtotime($reserve_time);
        $reserve_end = strtotime($reserve_time)+86400;
        if(strtotime('today') == strtotime($reserve_time)){
            if($is_reserve == 1){
                $where .= ' AND o.reserve_time<'.$reserve_end;
            }
            // $where .= ' AND o.reserve_time<'.$reserve_end;
        }else{
            $where .= ' AND o.reserve_time>='.$reserve_start.' AND o.reserve_time<'.$reserve_end;
        }

        $where .= ' AND o.is_base=0';
        
        $getField = "o.id,o.sqnum,o.shop_name,o.shop_id,o.shop_remark,o.type, o.send_type,o.sender_tel,o.send_status,o.send_price,o.create_time,o.reserve_time,o.total_money,o.pay_money,o.user_address,o.user_name,o.user_tel,o.sender_name,o.sender_tel,o.status,o.pay_type,o.bonus_money,o.user_remark,o.send_money,o.is_pick,o.pick_address,o.user_buytimes,o.is_base,o.base_id,o.lunchbox_money,o.pick_id,we.nickname";

    	$sql = "SELECT $getField FROM sm_order o LEFT JOIN sm_wechat_user we ON o.uid=we.id WHERE o.partner_id=".$partner_id.' AND o.is_pay=1 '.$where." ORDER BY o.id desc";
    	$data = $this->getPage($sql,$p);
    	foreach($data['data'] as $key=>$order){
    		$sql = "SELECT od.goods_name,od.num,od.price FROM sm_order_detail od WHERE od.order_id=".$order['id'];
    		$res = M()->query($sql);
    		$data['data'][$key]['detail'] = $res;
            if($order['pick_id'] > 0){
                $pick_info = M('pick_building')->where(array('id'=>$order['pick_id']))->find();
                $data['data'][$key]['wangdian_num'] = $pick_info['number'];
            }
    	}
    	return $data;
    }

    public function getBakOrderList($p,$shop_id,$key,$start_time,$end_time,$status){

        $where = '';
        
        if($status == 3){
            $where .= ' AND o.status=3';
        }else if($status == 4){
            $where .= ' AND o.status=4';
        }

        if(!empty($start_time) && !empty($end_time)){
            $where .= ' AND o.reserve_time>='.strtotime($start_time).' AND o.reserve_time<'.strtotime($end_time);
        }

        if($shop_id != 0){
            $where .= ' AND o.shop_id='.$shop_id;     
        }

        if($key != ''){
            $where .= sprintf(' AND (user_tel="%s" OR sqnum="%s")',$key,$key);
        }

        $where .= ' AND o.is_base=0';

        $sql = "SELECT o.id,o.sqnum,o.shop_name,o.create_time,o.reserve_time,o.pay_money,o.total_money,o.user_address,o.user_name,o.user_tel,o.sender_name,o.sender_tel,o.status,o.pay_type,o.bonus_money,o.user_remark,o.send_money,o.is_pick,o.pick_address,o.user_buytimes,o.lunchbox_money,o.pick_id,we.nickname FROM sm_order_bak o LEFT JOIN sm_wechat_user we ON o.uid=we.id WHERE o.partner_id=".$_SESSION['partner_id'].' AND o.is_pay=1 '.$where." ORDER BY o.id desc";
        $data = $this->getPage($sql,$p);

        foreach($data['data'] as $key=>$order){
            $sql = "SELECT od.goods_name,od.num,od.price FROM sm_order_detail_bak od WHERE od.order_id=".$order['id'];
            $res = M()->query($sql);
            $data['data'][$key]['detail'] = $res;

            if($order['pick_id'] > 0){
                $pick_info = M('pick_building')->where(array('id'=>$order['pick_id']))->find();
                $data['data'][$key]['wangdian_num'] = $pick_info['number'];
            }
        }
        return $data;
    }

    public function getDayMoney($partner_id,$day){
        $sql = "select total_money from sm_partner_count where datepos='$day' and partner_id=".$partner_id;
        $res = self::getOneKey($sql,'total_money');
        $res = $res?$res:0;
        return $res;
    }

    public function getTodayMoney($partner_id){
        $time = getTodayTimespan()+86400;
        $sql = "select sum(pay_money) as total from sm_order where reserve_time<".$time.' and partner_id='.$partner_id.' and (status!='.self::$STATUS_NORECEIVE.' and status!='.self::$STATUS_CANCEL.') and is_del='.self::$DEL_NO.' and base_id=0';
        $res = self::getOneKey($sql,'total');
        $res = $res?$res:0;
        return $res;
    }
    
    public function getTodayOrderNum($partner_id){
        $time = getTodayTimespan()+86400;
        $sql = "select count(*) as total from sm_order where reserve_time<".$time.' and partner_id='.$partner_id.' and (status!='.self::$STATUS_NORECEIVE.' and status!='.self::$STATUS_CANCEL.') and is_del='.self::$DEL_NO.' and base_id=0';
        $res = self::getOneKey($sql,'total');
        $res = $res?$res:0;
        return $res;
    }


    //店铺昨日流水
    public function getYesOrderMoney($shop_id){
        $day = date("Y-m-d",strtotime("-1 day"));
        $sql = "select total_money from sm_shop_count where datepos='$day' and shop_id=".$shop_id;
        $res = self::getOneKey($sql,'total_money');
        $res = $res?$res:0;
        return $res;
    }

    //店铺今日流水
    public function getShopTodayMoney($sid){
        $time = getTodayTimespan()+86400;
        $sql = "select sum(pay_money) as total from sm_order where reserve_time<".$time.' and shop_id='.$sid.' and (status!='.self::$STATUS_NORECEIVE.' and status!='.self::$STATUS_CANCEL.') and is_del='.self::$DEL_NO.' and base_id=0';
        $res = self::getOneKey($sql,'total');
        $res = $res?$res:0;
        return $res;
    }

    //店铺今日订单数量
    public function getShopTodayNum($sid){
        $time = getTodayTimespan()+86400;
        $sql = "select count(*) as total from sm_order where reserve_time<".$time.' and shop_id='.$sid.' and (status!='.self::$STATUS_NORECEIVE.' and status!='.self::$STATUS_CANCEL.') and is_del='.self::$DEL_NO.' and base_id=0';
        $res = self::getOneKey($sql,'total');
        $res = $res?$res:0;
        return $res;
    }

    //订单状态改为接单
    public function confirmOrder($order_id,$table='order'){
        $time = time();
        $res = M($table)->where(array('id'=>$order_id))->save(array('status'=>1,'process_time'=>$time));

        //父订单推子订单
        $is_base = M($table)->where(array('id'=>$order_id))->getField('is_base');
        if($is_base){
            //有子订单
            M($table)->where(array('base_id'=>$order_id))->save(array('status'=>1,'process_time'=>$time));
        }

        //由子订单推父订单
        $base_id = M($table)->where(array('id'=>$order_id))->getField('base_id');
        if($base_id > 0){
            M($table)->where(array('id'=>$base_id))->save(array('status'=>1,'process_time'=>$time));
            M($table)->where(array('base_id'=>$base_id))->save(array('status'=>1,'process_time'=>$time));
            $order_id = $base_id;
        }
        $ser = new ActivityService();
        $ser->getListAfterOrder($order_id);
        self::processOrderTpl($order_id);
        return $res;
    }

    //订单状态改为配送
    public function sendOrder($order_id,$table){
        $res = M($table)->where(array('id'=>$order_id))->save(array('status'=>2));
        return $res;
    }

    //订单状态改为完成
    public function completeOrder($order_id,$table){
        $res = M($table)->where(array('id'=>$order_id))->save(array('status'=>3));


        return $res;
    }

    //订单状态改为取消
    public function cancelOrder($order_id,$table='order',$reason=''){
        //是否有父订单
        $base_id = M($table)->where(array('id'=>$order_id))->getField('base_id');
        if($base_id > 0){
            //有父订单
            M($table)->where(array('id'=>$base_id))->save(array('status'=>4,'giveup_reason'=>$reason));
            $res = M($table)->where(array('base_id'=>$base_id))->save(array('status'=>4,'giveup_reason'=>$reason));

            if($table == 'order'){
                $other_table = 'order_bak';
            }else{
                $other_table = 'order';
            }
            M($other_table)->where(array('id'=>$base_id))->save(array('status'=>4,'giveup_reason'=>$reason));
            M($other_table)->where(array('base_id'=>$base_id))->save(array('status'=>4,'giveup_reason'=>$reason));
        }else{
            //普通单
            $res = M($table)->where(array('id'=>$order_id))->save(array('status'=>4,'giveup_reason'=>$reason));
            self::backStore($order_id);
        }
        self::cancelOrderTpl($order_id);

        return $res;
    }

    public function backStore($order_id)
    {
        $mode = new \Shop\Model\GoodsModel();
        $info = M('order')->where(array('id'=>$order_id))->field('partner_id,shop_id')->find();
        $has = $mode->partnerHasStore($info['partner_id'],$info['shop_id']);
        if($has){
            $list = M('order_detail')->where(array('order_id'=>$order_id))->field('goods_id,num')->select();
            $gmod = new \Home\Model\GoodsModel();
            foreach ($list as $v){
                $gmod->incGoods($v['goods_id'],$v['num']);
            }
        }
    }

    public function getOrderById($order_id,$table){
        $res = M($table)->where(array('id'=>$order_id))->find();
        return $res;
    }

    //添加退款列表
    public function addRefundList($order_info,$refund){
        $data['order_num'] = $order_info['order_num'];
        $data['order_id'] = $order_info['id'];
        $data['money'] = $refund['refund_fee']/100;
        $data['info'] = json_encode($refund);
        $data['return_code'] = $refund['result_code'];
        $data['return_msg'] = $refund['return_msg'];
        $data['create_time'] = time();
        M('refund')->add($data);
    }


    //添加余额退款列表
    public function addRechargeRefundList($order_info){
        $time = time();
        $data['order_num'] = $order_info['order_num'];
        $data['order_id'] = $order_info['id'];
        $data['money'] = $order_info['pay_money'];
        $data['return_code'] = 'SUCCESS';
        $data['create_time'] = $time;
        M('refund')->add($data);

        $curr_money = M('user_info')->where(array('uid'=>$order_info['uid']))->getField('account_money');
        $data1['uid'] = $order_info['uid'];
        $data1['order_id'] = $order_info['id'];
        $data1['partner_id'] = $order_info['partner_id'];
        $data1['shop_id'] = $order_info['shop_id'];
        $data1['order_money'] = $order_info['pay_money'];
        $data1['money'] = $order_info['pay_money'];
        $data1['curr_money'] = $curr_money;
        $data1['create_time'] = $time;
        $data1['type'] = 2;
        //退款记录
        M('user_recharge_declog')->add($data1);
        //退款
        M('user_info')->where(array('uid'=>$order_info['uid']))->setInc('account_money',$order_info['pay_money']);
    }

    public function getRefundList($p,$partner_id,$shop_id=0){
        if($shop_id == 0){
            $sql = "SELECT * FROM (SELECT r.*,o.shop_name,o.uid,o.user_name,o.sqnum,o.pay_type FROM sm_refund r LEFT JOIN sm_order o ON r.order_id=o.id WHERE o.partner_id=".$partner_id." UNION ALL "."SELECT r.*,o.shop_name,o.uid,o.user_name,o.sqnum,o.pay_type FROM sm_refund r LEFT JOIN sm_order_bak o ON r.order_id=o.id WHERE o.partner_id=".$partner_id.") t ORDER BY t.id DESC";
        }else{
            $sql = "SELECT * FROM (SELECT r.*,o.shop_name,o.uid,o.user_name,o.sqnum,o.pay_type FROM sm_refund r LEFT JOIN sm_order o ON r.order_id=o.id WHERE o.partner_id=".$partner_id." AND o.shop_id=".$shop_id." UNION ALL "."SELECT r.*,o.shop_name,o.uid,o.user_name,o.sqnum,o.pay_type FROM sm_refund r LEFT JOIN sm_order_bak o ON r.order_id=o.id WHERE o.partner_id=".$partner_id." AND o.shop_id=".$shop_id.") t ORDER BY t.id DESC";
        }
        $data = $this->getPage($sql,$p);
        return $data;
    }

    public function setOrderRefundStatus($order_id,$table){
        M($table)->where(array('id'=>$order_id))->save(array('is_refund'=>1));
    }

    public function getActivity($order_id){
        $res = M('order_activity')->where(array('oid'=>$order_id))->select();
        foreach($res as $key=>$activity){
            if(!empty($activity['money'])){
                $res[$key]['money'] = -$activity['money'];
            }
        }
        return $res;
    }

    public function getOrderListByUid($p,$uid){
        $sql = "SELECT * FROM sm_order_bak WHERE uid=$uid ORDER BY id DESC";

        $data = $this->getPage($sql,$p);

        foreach($data['data'] as $key=>$order){
            $sql = "SELECT od.goods_name,od.num,od.price FROM sm_order_detail_bak od WHERE od.order_id=".$order['id'];
            $res = M()->query($sql);
            $data['data'][$key]['detail'] = $res;
        }
        return $data;
    }

    //今日的历史订单完成后进入历史订单列表
    public function toOldOrder($order_id){
        $today = strtotime('today');
        $reserve_time = M('order')->where(array('id'=>$order_id))->getField('reserve_time');
        if($reserve_time < $today){
            //order_detail
            $order_detail_sql = "insert into sm_order_detail_bak (SELECT * FROM  sm_order_detail where order_id=$order_id)";
            $order_detail_rs=M('')->execute($order_detail_sql);
            M('order_detail')->where(array('order_id'=>$order_id))->delete();

            //order
            $order_sql = "insert into sm_order_bak (SELECT  * FROM  sm_order where id=$order_id)";
            $order_rs=M('')->execute($order_sql);
            M('order')->where(array('id'=>$order_id))->delete();
        }
    }

    public function partnerReserveStatus($partner_id,$shop_id){
        $res = M('shop_conf')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->getField('reserve_status');
        if($shop_id == 0){
            $sql = "SELECT count(*) AS num FROM sm_shop_conf sc LEFT JOIN sm_shop s ON sc.shop_id=s.id WHERE sc.partner_id=".$partner_id." AND s.is_del=0 AND sc.reserve_status=1";
            $res = M()->query($sql);
            $res = $res[0]['num'];
            if($res != 0 ){
                $res = 1;
            }
        }
        return $res;
    }

    public function getReserveDays($partner_id,$shop_id){
        
        if($shop_id == 0){
             $sql = "SELECT MAX(reserve_day) AS reserve_day FROM sm_shop_conf sc LEFT JOIN sm_shop s ON sc.shop_id=s.id WHERE sc.partner_id=".$partner_id." AND s.is_del=0 AND sc.reserve_status=1";
             $res = M()->query($sql);
             $res = $res[0]['reserve_day'];
             if(empty($res)){
                $res = 1;
             }
        }else{
            $res = M('shop_conf')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->getField('reserve_day');
        }
        return $res;
    }

    public function shoppayOrder($pid, $p,$sid,$start_time,$end_time)
    {
//        $sql = "select count(*) as num from sm_shop_pay p where p.is_pay=1 and p.partner_id=".$pid;
//        $res = M()->query($sql);
        $pageSize = C("PAGE_SIZE");
//        $page = new \Org\Util\Page($res[0]['num'],$pageSize);
//        $sql = "select p.*,s.name from sm_shop_pay p left join sm_shop s on s.id=p.shop_id where p.partner_id=".$pid." and p.is_pay=1 order by p.id desc ";
//        $sql .= " limit ".($p-1)*$pageSize.','.$pageSize;
//        $data['page'] = $page->show();
//        $data['data'] = M()->query($sql);
        $where = "where p.partner_id=$pid and p.is_pay=1 ";
        if(!empty($sid)){
            $where .=" and p.shop_id=".$sid;
        }
        if(!empty($start_time)){
            $where .= " and from_unixtime(p.create_time,'%Y-%m-%d')>='$start_time'";
        }
        if(!empty($end_time)){
            $where .= " and from_unixtime(p.create_time,'%Y-%m-%d')<='$end_time'";
        }
        $limit = " limit ".($p-1)*$pageSize.','.$pageSize;
        $order = " order by p.id desc";
        $sql = "select p.*,s.name from sm_shop_pay p left join sm_shop s on s.id=p.shop_id  ".$where.$order.$limit;
        $data['data'] = M()->query($sql);
        $sql = "select count(*) as num from sm_shop_pay ".$where;
        $num = self::getOneKey($sql,'num');
        $page = new \Org\Util\Page($num,$pageSize);
        $data['page'] = $page->show();
        return $data;
    }

    public function getOrderCount($partner_id,$shop_id,$start_time,$end_time){
        $where['partner_id'] = $partner_id;
        if($shop_id != 0){
            $where['shop_id'] = $shop_id;
        }
        $where['is_pay'] = 1;
        $where['status'] = array(array('neq',0),array('neq',4));
        $where['reserve_time'] = array(array('egt',$start_time),array('lt',$end_time));
        $where['is_base'] = 0;
        $count = M('order')->where($where)->count();
        return $count;
    }

    public function goodsCountList($partner_id,$start_time,$end_time){
        $where = "o.partner_id=".$partner_id;
        $where .= " AND o.status=1 ";
        $where .= " AND o.reserve_time>=".$start_time;
        $where .= " AND o.reserve_time<".$end_time;
        $sql = "SELECT SUM(od.num) AS num,od.goods_name,od.basegoods_id FROM sm_order_detail od LEFT JOIN sm_order o ON od.order_id=o.id  WHERE ".$where." GROUP BY basegoods_id";
        $res = M()->query($sql);
        return $res;
    }

    public function  goodsCountByShop($partner_id,$basegoods_id,$start_time,$end_time){
        $where = "o.partner_id=".$partner_id;
        $where .= " AND od.basegoods_id=".$basegoods_id;
        $where .= " AND o.status=1 ";
        $where .= " AND o.reserve_time>=".$start_time;
        $where .= " AND o.reserve_time<".$end_time;
        $sql = "SELECT SUM(od.num) AS num,o.shop_name FROM sm_order_detail od LEFT JOIN sm_order o ON od.order_id=o.id WHERE ".$where." GROUP BY od.shop_id";
        $res = M()->query($sql);
        return $res;
    }



    /** 接单后给用户发送消息模板
     * @param $oid
     */
    public function processOrderTpl($oid)
    {
        $model = new HomeOrderModel();
        $info = $model->getOrderBaseInfo($oid);
        $uid = $info['uid'];
        $partner = new PartnerModel();
        $tpl = $partner->getTplId($info['partner_id'],TPL_PROCESSORDER);
        if(empty($tpl)){
            return;
        }
        $user = new UserModel();
        $user_info = $user->getUserInfoById($uid);
        $openid = $user_info['openid'];
        $data['first'] =  "您的订单已被受理，稍等片刻，速速就来～如有问题可与商家联系";
        $data['keyword1'] = $info['sqnum'];
        $data['keyword2'] = date('Y-m-d',time());
        $data['remark'] = "";
        $helper = WechatHelper::get($info['partner_id']);
        $res = $helper->sendTpl($tpl,$openid,"",$data);
    }


    /** 取单后给用户发送消息模板
     * @param $oid
     */
    public function cancelOrderTpl($oid)
    {
        $model = new HomeOrderModel();
        $info = $model->getOrderBaseInfo($oid);
        $uid = $info['uid'];
        $partner = new PartnerModel();
        $tpl = $partner->getTplId($info['partner_id'],TPL_CANCELORDER);
        if(empty($tpl)){
            return;
        }
        $user = new UserModel();
        $user_info = $user->getUserInfoById($uid);
        $openid = $user_info['openid'];
        $data['first'] =  "抱歉，您的订单已被取消。如果订单为微信支付，钱款将依照微信平台交易规则，1~3个工作日返还。";
        $data['keyword1'] = $info['sqnum'];
        $data['keyword2'] = date('Y-m-d',time());
        $data['remark'] = "";
        $helper = WechatHelper::get($info['partner_id']);
        $res = $helper->sendTpl($tpl,$openid,"",$data);
    }


    public function getBaseWeekOrderList($p,$partner_id,$shop_id,$key,$reserve_time){
        $where = '';
        if($shop_id != 0){
            $where = ' AND o.shop_id='.$shop_id;     
        }
        if($key != ''){
            $where .= sprintf(' AND (o.user_tel="%s" OR o.id="%s")',$key,$key);
        }

        $reserve_start = strtotime($reserve_time);
        $reserve_end = strtotime($reserve_time)+86400;
        if(strtotime('today') == strtotime($reserve_time)){
            $where .= ' AND o.create_time<'.$reserve_end;
        }else{
            $where .= ' AND o.create_time>='.$reserve_start.' AND o.create_time<'.$reserve_end;
        }

        $where .= ' AND o.is_base=1';
        


        $sql = "SELECT o.id,o.sqnum,o.shop_name,o.create_time,o.reserve_time,o.total_money,o.pay_money,o.user_address,o.user_name,o.user_tel,o.sender_name,o.sender_tel,o.status,o.pay_type,o.bonus_money,o.user_remark,o.send_money,o.is_pick,o.pick_address,o.user_buytimes,o.is_base,o.base_id,o.user_attrs,we.nickname FROM sm_order o LEFT JOIN sm_wechat_user we ON o.uid=we.id WHERE o.partner_id=".$partner_id.' AND o.is_pay=1 '.$where." ORDER BY o.id desc";
        $data = $this->getPage($sql,$p);

        return $data;
    }

    public function getBakBaseWeekOrderList($p,$shop_id,$key,$start_time,$end_time,$status){

        $where = '';
        
        if($status == 3){
            $where .= ' AND o.status=3';
        }else if($status == 4){
            $where .= ' AND o.status=4';
        }

        if(!empty($start_time) && !empty($end_time)){
            $where .= ' AND o.reserve_time>='.strtotime($start_time).' AND o.reserve_time<'.strtotime($end_time);
        }

        if($shop_id != 0){
            $where .= ' AND o.shop_id='.$shop_id;     
        }

        if($key != ''){
            $where .= sprintf(' AND (user_tel="%s" OR sqnum="%s")',$key,$key);
        }

        $where .= ' AND o.is_base=1';

        $sql = "SELECT o.id,o.sqnum,o.shop_name,o.create_time,o.reserve_time,o.pay_money,o.total_money,o.user_address,o.user_name,o.user_tel,o.sender_name,o.sender_tel,o.status,o.pay_type,o.bonus_money,o.user_remark,o.send_money,o.is_pick,o.pick_address,o.user_buytimes,o.user_attrs,we.nickname FROM sm_order_bak o LEFT JOIN sm_wechat_user we ON o.uid=we.id WHERE o.partner_id=".$_SESSION['partner_id'].' AND o.is_pay=1 '.$where." ORDER BY o.id desc";
        
        $data = $this->getPage($sql,$p);

        foreach($data['data'] as $key=>$order){
            $sql = "SELECT od.goods_name,od.num,od.price FROM sm_order_detail_bak od WHERE od.order_id=".$order['id'];
            $res = M()->query($sql);
            $data['data'][$key]['detail'] = $res;
        }
        return $data;
    }


    public function getAllOrderList($partner_id,$shop_id,$key,$reserve_time){
        $where = '';
        if($shop_id != 0){
            $where = ' AND o.shop_id='.$shop_id;     
        }
        if($key != ''){
            $where .= sprintf(' AND (o.user_tel="%s" OR o.sqnum="%s")',$key,$key);
        }

        $reserve_start = strtotime($reserve_time);
        $reserve_end = strtotime($reserve_time)+86400;
        if(strtotime('today') == strtotime($reserve_time)){
            $where .= ' AND o.reserve_time<'.$reserve_end;
        }else{
            $where .= ' AND o.reserve_time>='.$reserve_start.' AND o.reserve_time<'.$reserve_end;
        }

        $where .= ' AND o.is_base=0';
        


        $sql = "SELECT o.id,o.sqnum,o.shop_name,o.create_time,o.reserve_time,o.total_money,o.pay_money,o.user_address,o.user_name,o.user_tel,o.sender_name,o.sender_tel,o.status,o.pay_type,o.bonus_money,o.user_remark,o.send_money,o.is_pick,o.pick_address,o.user_buytimes,o.is_base,o.base_id,we.nickname FROM sm_order o LEFT JOIN sm_wechat_user we ON o.uid=we.id WHERE o.partner_id=".$partner_id.' AND o.is_pay=1 '.$where." ORDER BY o.id desc";
        $data['data'] = M()->query($sql);

        foreach($data['data'] as $key=>$order){
            $sql = "SELECT od.goods_name,od.num,od.price FROM sm_order_detail od WHERE od.order_id=".$order['id'];
            $res = M()->query($sql);
            $data['data'][$key]['detail'] = $res;
        }
        return $data;
    }

    public function getDaysShopPay($partner_id,$start_time){
        $end_time = $start_time + 86400;
        $sql = "SELECT SUM(pay_money) as pay_money FROM sm_shop_pay WHERE partner_id=$partner_id AND is_pay=1 AND create_time>=$start_time AND create_time<$end_time";
        $res = M()->query($sql);
        $pay_money = empty($res[0]['pay_money']) ? '0.00' : $res[0]['pay_money'];
        return $pay_money;
    }

}