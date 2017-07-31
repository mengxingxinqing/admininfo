<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/7/30 0030
 * Time: 下午 5:43
 */

namespace Home\Model;


use Base\Model\BaseModel;

class OrderModel extends BaseModel
{
    public $table = "order";
    public function getUserOrderCount($uid){
        $sql = "select count(*) as num from tm_order where uid=$uid";
        $count = $this->queryField($sql,'num');
        $sql = "select count(*) as num from tm_order_bak where uid=$uid";
        $count += (int)$this->queryField($sql,'num');
        return $count;
    }

    public function getLostUser($partner_id,$shop_id,$time){
        $sql = "select sum(od.total_price) allmoney,od.uid,ud.name,ud.tel,ud.address
                from tm_order_detail_bak od left join tm_user_address as ud on od.uid=ud.uid
                where od.partner_id=".$partner_id." and od.create_time<".$time." and ud.shop_id=".$shop_id." and 
                (select count(*) from tm_order_detail_bak p1 where p1.uid=od.uid and p1.create_time>{$time})<1 and od.shop_id=".$shop_id." group by od.uid";

        $list = M()->query($sql);

        return $this->getUserBounsCount($partner_id,$list);
    }

    // 获取用户有效红包的数量
    public function getUserBounsCount($partner_id,$list){
        
        $where['status'] = 0;
        $where['partner_id'] = $partner_id;
        $bouns = M('bonus');
        foreach ($list as $key => $value) {
            $where['uid'] = $value['uid'];
            $bounscount = $bouns->where($where)->count();
            if (empty($bounscount) || $bounscount == 0) {
                $list[$key]['bouns'] = 0;
            }else{
                $list[$key]['bouns'] = $bounscount;
            }
        }
        return $list;
    }

    public function getRichUser($partner_id,$shop_id){
         $sql = " select sum(price) as allmoney,uid,name,tel,address 
            from
            (select od.real_price as price,od.uid as uid ,ud.name as name,ud.tel as tel ,ud.address as address 
            from tm_order_bak od
            left join tm_user_address as ud on od.uid=ud.uid 
            where od.partner_id=".$partner_id." and ud.shop_id=".$shop_id." and  od.status=2 and od.is_del=0 and od.shop_id=".$shop_id."
            ) as new_table
                group by uid
                order by allmoney desc 
                limit 10 ";
        $list = M()->query($sql);
        return $list;
    }


    public function getNewUser($partner_id,$shop_id){
        $sql = "select u.id,ud.name,ud.address,ud.tel from tm_wechat_user u left join tm_user_address ud on u.id=ud.uid where u.shop_id=$shop_id and ud.shop_id=".$shop_id." and (select count(*) from tm_order_detail_bak po where po.partner_id=".$partner_id." and po.uid=u.id)=0";
        $list = M()->query($sql);
        
        return $this->getUserBounsCount($partner_id,$list);
    }
    public function getOrderList($partner_id,$shop_id,$p){
        $limit = ((int)$p-1)*C('PAGE_SIZE').','.C('PAGE_SIZE');
        $sql = "select *,FROM_UNIXTIME( create_time,'%Y-%m-%d %H:%i:%s') as new_time from tm_order_bak where shop_id=$shop_id and partner_id=$partner_id and status=2 and is_del=0 order by id desc limit $limit";
        $data=M()->query($sql);
        //是否是首单用户
        foreach ($data as $key => $value) {
            $is_first = M('order_first')->where(array('uid'=>$value['uid'],'order_id'=>$value['id']))->find();
            if (!empty($is_first)) {
                $data[$key]['is_first'] = 'is_first';
            }
        }
        
        return $data;
    }

    public function checkbonus($bonus_ids){
        //判断红包是否是这个商家的红包
        $find_partners = M('bonus')->where(array('id'=>$bonus_ids))->field("id,partner_id,shop_id,money")->find();
        return $find_partners;
    }

    public function addOrder($uid,$pid,$shop_id,$res,$input,$activity_info){
        \Think\Log::write("tong-test3 add order");
        $time = time();
        if(empty($input['reserve_time'])){
            $reserve_time = $time;
        }else{
            $reserve_time = $input['reserve_time'];
        }
        $real_price = $total_money = $res['total_money']+$input['send_money']+$res['lunchbox_money'];
        $data = array(
            'order_num'=>$input['order_num'],
            'uid'=>$uid,
            'user_name'=>$input['name'],
            'user_tel'=>$input['tel'],
            'user_address'=>$input['address'].' '.$input['detail_address'],
            'shop_id'=>$shop_id,
            'shop_name'=>$input['shop_name'],
            'shop_tel'=>$input['shop_tel'],
            'partner_id'=>$pid,
            'total_money'=>$total_money,
            'pay_money'=>$real_price,
            'create_time'=>$time,
            'reserve_time'=>$reserve_time,
            'pay_type'=>$input['pay_type'],
            'remark'=>$input['remark'],
            'user_remark'=>$input['userremark'],
            'send_money'=>$input['send_money'],
            'is_pick' =>$input['is_pick'],
            'pick_address'=>$input['pick_address'],
            'user_part'=>$input['user_part'],
            'order_type'=> empty($input['order_type']) ? 0 : 1,
            'pick_id'=>$input['pick_id'],
            'lunchbox_money'=>$res['lunchbox_money'],
            'type'=>$input['type'],
        );

        //扣除红包金额
        if($input['bonus_id'] != ""){
            $bonus_info = $this->checkbonus($input['bonus_id']);
            $bonus_money = $bonus_info['money'];
            if(!empty($bonus_info) && $bonus_info['partner_id'] == $pid){
                $data['pay_money'] -= $bonus_money;
                $data['bonus_id'] = $bonus_info['id'];
                $data['bonus_money'] = $bonus_money;
            }
        }

        //扣除活动金额
        foreach ($activity_info as $key => $activity) {
            if($activity['type'] == 'dec'){
                $data['pay_money'] -= $activity['money'];
            }
        }

        if($data['pay_money'] < 0){
            $data['pay_money'] = 0;
        }

        /*if($input['pay_type'] == "线下支付"){
            $data['is_pay'] = 1;
        }*/
        // var_dump($data);die;
        $order_id = M('order')->add($data);
       
        //生成订单序号并存储
        $this->addSqnum($order_id,$shop_id,$reserve_time);

        //存活动
        $this->addActivity($activity_info,$order_id,$uid,$shop_id,$pid);

        $goodsMod = new GoodsModel();
        $goods_list = $res['goods_info'];
        foreach($goods_list as $goods){
            $category_info = $this->getCategoryInfo($goods['category_id']);
            $data = array(
                'order_id'=>$order_id,
                'goods_id'=>$goods['goods_id'],
                'basegoods_id'=>$goods['basegoods_id'],
                'price'=>$goods['price'],
                'num'=>$goods['num'],
                'total_money'=>$goods['total_money'],
                'uid'=>$uid,
                'shop_id'=>$shop_id,
                'partner_id'=>$pid,
                'create_time'=>$time,
                'reserve_time'=>$reserve_time,
                'goods_name' =>$goods['goods_name'],
                'category_id'=>$goods['category_id'],
                'category_name'=>$category_info[0]['category_name'],
                'basecategory_id'=>$category_info[0]['basecategory_id'],
                'format_id' =>$goods['format_id'],
                'format_name' =>$goods['format_name'],
                'attr_str' =>$goods['attr_str'],
            );
            $id = M('order_detail')->add($data);
            $goodsMod->decGoods($goods['goods_id'],$goods['num']);
        }
        \Think\Log::write("tong-test5");
        return $order_id;
    }

    public function isFirst($uid,$partner_id){
        $fdata['uid'] = $uid;
        $fdata['partner_id'] = $partner_id;
        $res = M('user_info')->where($fdata)->getField('first_order_id');
        if(empty($res)){
            $is_first = 1;
        }else{
            $is_first = 0;
        }
        return $is_first;
    }

    public function addActivity($activity_info,$order_id,$uid,$shop_id,$partner_id){
        foreach($activity_info as $key=>$activity){
            $data['oid'] = $order_id;
            $data['uid'] = $uid;
            $data['sid'] = $shop_id;
            $data['pid'] = $partner_id;
            $data['ac_id'] = $activity['id'];
            $data['name'] = $activity['name'];
            $data['money'] = $activity['money'];
            $data['create_time'] = time();
            M('order_activity')->add($data);
        }
    }

    /**
     * 添加订单每日序号
    */
    public function addSqnum($order_id,$shop_id,$reserve_time){
        $start_time = strtotime(date('Y-m-d',$reserve_time));
        $end_time = $start_time+86400;
        $count = M('order')->where('id<='.$order_id.' AND shop_id='.$shop_id.' AND reserve_time>='.$start_time." AND reserve_time<".$end_time)->count();
        $date = date('md',$reserve_time);
        $sqnum = $date.$count;
        M('order')->where(array('id'=>$order_id))->save(array('sqnum'=>$sqnum));
        return $sqnum;
    }

    public function getOrderInfo($order_id){
        $res['base_info'] = M('order')->find($order_id);
        
        $res['list'] = M('order_detail')->where(array('order_id'=>$order_id))->select();
        return $res;
    }

    public function getOrderStatus($base_id){
        $status = M('order')->where(array('id'=>$base_id))->getField('status');
        return $status;
    }

    /** 支付后设置为已支付
     * @param $id
     */
    public function setPay($id){
        M('order')->where(array('id'=>$id))->save(array('is_pay'=>1));

        $is_base = M('order')->where(array('id'=>$id))->getField('is_base');
        if($is_base){
            //有子订单
            M('order')->where(array('base_id'=>$id))->save(array('is_pay'=>1));
        }
    }

    public function setOpenid($id,$openid){
        M('order')->where(array('id'=>$id))->save(array('openid'=>$openid));
    }

    public function getOrderIsPay($id){
        $is_pay = M('order')->where(array('id'=>$id))->getField('is_pay');
        return $is_pay;
    }

    /** 获取订单详情
     * @param $base_id
     * @return array
     */
    public function getOrderDetail($base_id){
        $sql = "select * from tm_order where base_id=".$base_id;
        $list = M()->query($sql);
        $address = M('user_address')->where(array('uid'=>$list[0]['uid'],'shop_id'=>$list[0]['shop_id']))->find();
        $shop = M('shop')->find($list[0]['shop_id']);
        $res = array();
        foreach($list as $item){
            $tmp['order'] = $item;
            $tmp['order']['bonus_money'] = M('bonus')->where(array('id'=>$item['bonus_id']))->getField('money');
            $tmpSql = "select g.name,d.price,d.num,g.img from tm_order_detail d LEFT JOIN tm_goods g on d.base_goodsid=g.id where d.order_id=".$item['id'];
            $tmp['detail'] = M()->query($tmpSql);
            $tmpSql = "select u.openid from tm_sender s LEFT JOIN tm_wechat_user u on s.uid=u.id where s.partner_id=".$item['partner_id'];
            $tmp['sender'] = M()->query($tmpSql);
            $tmp['user'] = $address;
            $tmp['shop'] = $shop;
            $res[] = $tmp;
        }
        return $res;
    }

    public function getOrderShopId($oid)
    {
        $res = M('order')->where(array('id'=>$oid))->getField('shop_id');
        if(empty($res)){
            $res = M('order_bak')->where(array('id'=>$oid))->getField('shop_id');
        }
        return $res;
    }


    public function getPartnerOrder($partner_id,$status,$process_id=''){
        if (is_array($partner_id)) {
            $partner_id = implode(',', $partner_id);
        }
        if(!empty($process_id)){
            $where = " and process_id=".$process_id;
        }
        $sql = "select o.id,o.bonus_id,o.total_price,o.real_price,o.uid,o.shop_id,o.remark,o.user_remark,o.order_num,o.create_time,o.pay_type,o.is_pay,s.name as shop_name,o.pay_type from tm_order o left join tm_shop s on o.shop_id=s.id where partner_id in ($partner_id) and o.status=$status $where order by o.create_time desc";
        $list = M()->query($sql);
        $res = array();
        $userModel = new UserinfoModel();
        foreach($list as $item){
            $tmp['order'] = $item;
            $tmp['order']['bonus_money'] = M('bonus')->where(array('id'=>$item['bonus_id']))->getField('money');
            $tmpSql = "select g.name,d.price,d.num,g.route_img as img from tm_order_detail d LEFT JOIN tm_goods g on d.base_goodsid=g.id where d.order_id=".$item['id'];
            $tmp['detail'] = M()->query($tmpSql);
            $address = $userModel->getUserAddress($item['uid'],$item['shop_id']);
            $tmp['user'] = $address;
            if(!($tmp['order']['pay_type'] == '微信支付' && $tmp['order']['is_pay'] == 0)){
                $res[] = $tmp;
            }
        }
        return $res;
    }
    //展示店铺所配送中的订单
    public function DispatcherProcess($partner_id,$status,$process_id=''){
        if (is_array($partner_id)) {
            $partner_id = implode(',', $partner_id);
        }

        $sql = "select ts.name as user_name,o.process_time,
              o.process_id ,o.id,o.bonus_id,o.real_price,o.total_price,o.uid,o.shop_id,o.remark,o.user_remark,o.order_num,o.create_time,o.pay_type,o.is_pay,s.name as shop_name,o.pay_type
              from tm_order o
              left join tm_shop s on o.shop_id=s.id
              LEFT  join tm_sender ts on ts.uid=o.process_id and ts.partner_id=o.partner_id
              where o.partner_id in ($partner_id) and o.status=$status  order by o.create_time desc";
        $list = M()->query($sql);
        \Think\Log::write('展示店铺所配送中的订单:'.$sql);
        $res = array();
        $userModel = new UserinfoModel();
        foreach($list as $item){
            $tmp['order'] = $item;
            $tmp['order']['bonus_money'] = M('bonus')->where(array('id'=>$item['bonus_id']))->getField('money');
            $tmpSql = "select g.name,d.price,d.num,g.route_img as img from tm_order_detail d LEFT JOIN tm_goods g on d.base_goodsid=g.id where d.order_id=".$item['id'];
            $tmp['detail'] = M()->query($tmpSql);
            $address = $userModel->getUserAddress($item['uid'],$item['shop_id']);
            $tmp['user'] = $address;
            if($tmp['order']['process_id'] ==$process_id){
                $tmp['order']['can_process']=1;
            }else{
                $tmp['order']['can_process']=0;
            }
            if(!($tmp['order']['pay_type'] == '微信支付' && $tmp['order']['is_pay'] == 0)){
                $res[] = $tmp;
            }
        }
        return $res;
    }

    public function updateOrder($id,$uid,$status,$data,$info=''){
        $old_status = M('order')->where(array('id'=>$id))->getField('status');
        if($old_status >= $status){//如果状态已经异常 不更新
            return false;
        }
        if (!empty($info)) {
            $data['show_msg'] = $info;
        }
        $data['process_id'] = $uid;
        $data['status'] = $status;
        $res = M('order')->where(array('id'=>$id))->save($data);
        if ($res) {
            return $res;
        }else{
            return false;
        }
        
    }

    public function thisOrderStatus($id){
        return M('order')->where(array('id'=>$id))->getField('status');
    }

    public function findOrder($id){
        return M('order')->where(array('id'=>$id))->find();
    }


    public function getOrderTplInfo($id){
        $order = M('order')->find($id);
        $partner = M('partner')->find($order['partner_id']);
        return array(
            'order'=>$order,
            'partner'=>$partner,
        );
    }

    public function getOrderTplDetail($id){
        $sql = "select g.name from tm_order_detail od LEFT JOIN tm_goods g on od.base_goodsid=g.id where od.order_id=".$id;
        $list = M()->query($sql);
        $str = "";
        foreach($list as $item){
            $str .= ' '.$item['name'];
        }
        return $str;
    }

    /**
     * 放弃掉未支付订单
     */
    public function giveupAll(){
        $where['status'] = 0;
        $where['pay_type'] = '微信支付';
        $where['is_pay'] = 0;
        $overTime = time()-60*5;
        $where['create_time'] = array('lt',$overTime);
        $list = M('order')->where($where)->field('id')->select();
//        echo M()->getLastSql();
//        var_dump($list);exit;
        foreach($list as $item){
            $this->giveupOne($item['id']);
        }
    }

    public function giveupOne($id){
        $status = M('order')->where(array('id'=>$id))->getField('status');
        if($status == 0){
            M('order')->where(array('id'=>$id))->save(array('status'=>4,'end_time'=>time()));
        }
        return true;
    }

    //获得用户今日订单
    public function getUserOrder($uid){
        $list = M('order')->where(array('uid'=>$uid))->order('id desc')->select();
        foreach($list as $key=>$order){
            $sql = "SELECT od.*,sg.img FROM sm_order_detail od LEFT JOIN sm_shop_goods sg ON od.goods_id=sg.id WHERE od.order_id=".$order['id'];
            $res = M()->query($sql);
            $list[$key]['detail'] = $res;
            $activity = M('order_activity')->where(array('oid'=>$order['id']))->select();
            $list[$key]['activity'] = $activity;
        }
        return $list;
    }
    
    //获得用户历史订单
    public function getUserOrderBak($uid){
        $list = M('order_bak')->where(array('uid'=>$uid))->order('id desc')->limit(10)->select();
        foreach($list as $key=>$order){
            $sql = "SELECT od.*,sg.img FROM sm_order_detail_bak od LEFT JOIN sm_shop_goods sg ON od.goods_id=sg.id WHERE od.order_id=".$order['id'];
            $res = M()->query($sql);
            $list[$key]['detail'] = $res;
            $activity = M('order_activity')->where(array('oid'=>$order['id']))->select();
            $list[$key]['activity'] = $activity;
        }
        return $list;
    }


    //返回用户红包
    public function refundBonus($id){
        return M('bonus')->where(array('id'=>$id))->save(array('status'=>0));
    }


    //纪录返款信息
    public function refundInfo($order,$res){
        $data['order_num'] = $order['order_num'];
        $data['order_id'] = $order['id'];
        $data['money'] = $res['refund_fee']/100;
        $data['info'] = json_encode($res);
        $data['return_code'] = $res['result_code'];
        $data['return_msg'] = $res['return_msg'];
        $data['create_time'] = time();
        return M('refund')->add($data);
    }

    /**
     * 自动将已接单订单转化为已完成
     */
    public function changeStatus(){
        $sql = "select id from tm_order where status=1";
        $res = M()->query($sql);
        $sql = "update tm_order set status=2 where status=1";
        //减库存
        foreach($res as $item){
            $this->changeStore($item['id']);
        }
        M()->execute($sql);
    }

    /** 将微信支付转为微信支付
     * @param $order_id
     */
    public function changeWxpay($order_id){
        $data['pay_type'] = '2';
        return M('order')->where(array('id'=>$order_id))->save($data);
    }

    /** 用户手动放弃微信支付
     * @param $base_id
     */
    public function giveUpWxpay($base_id){
        $sql = "update tm_order set giveup=1 where base_id=".$base_id;
        M()->execute($sql);
    }

    public function getRealPrice($bonus_id,$activity_info,$total_price){
        $res = $total_price;
        if(!empty($bonus_id)){
            $bonus = self::checkbonus($bonus_id);
            if(!empty($bonus)){
                $res -= $bonus['money'];
            }
        }
        if(!empty($activity_info)){
            $str = explode('|',$activity_info);
            $activity_id = $str[1];
            $partner_id = $str[0];

            $m = M('activity')->where(array('id'=>$activity_id,'partner_id'=>$partner_id))->getField('param2');
            $res -= $m;
        }
        return $res;
    }

    public function getCategoryInfo($category_id){
        $sql = "SELECT sc.name as category_name,sc.base_id AS basecategory_id FROM sm_shop_category sc WHERE id=".$category_id;
        return $res = M()->query($sql);
    }

    public function getFeedbackUser($time){
        if(empty($time)){
            $time = date('Y-m-d',time());
        }
        //取关用户
        $sql = "select o.id,o.uid,o.shop_id from tm_order_bak o left join tm_wechat_user u on o.uid=u.id where u.status=1 and from_unixtime(u.leave_time,'%Y-%m-%d')='$time' group by o.uid";
        self::addFeedbackUser($sql,0);
        //首单用户 只购买便利店商品
        $sql = "select id,uid,shop_id from tm_order_bak o where (select count(*) from tm_order_bak where uid=o.uid)=1 and from_unixtime(o.create_time,'%Y-%m-%d')='$time' and (select count(*) from tm_order_detail_bak d where order_id=o.id and d.goods_type=1)=0 and o.status=2";
        self::addFeedbackUser($sql,1);
        $goods = M("feedback_goods")->getField("goods_id",true);
        $goods = implode(',',$goods);
        //购买回头率低的商品的用户
//        $sql = "select id,uid,shop_id from tm_order_bak o where (select count(*) from tm_order_detail_bak d where d.order_id=o.id and d.base_goodsid in ($goods) )>0 and from_unixtime(o.create_time,'%Y-%m-%d')='$time' and o.status=2";
//        self::addFeedbackUser($sql,2);
    }

    private function addFeedbackUser($sql,$type){
        $res = M()->query($sql);
        foreach ($res as $v){
            $data['shop_id'] = $v['shop_id'];
            $data['uid'] = $v['uid'];
            $data['order_id'] = $v['id'];
            $data['create_time'] = time();
            $data['type'] = $type;
            $data['status'] = 0;
            M('feedback_user')->add($data);
        }
    }
    

    public function checkWxpayStatus($order_id){
        $res = M('order')->where(array('id'=>$order_id))->find();
        return $res['is_pay'];
    }

    /** 获取用户所有的订单数量
     * @param $uid
     * @return mixed
     */
    public function getUserOrderNum($uid)
    {
        $num = M("order")->where(array('uid'=>$uid))->count();
        $num += M("order_bak")->where(array('uid'=>$uid))->count();
        return $num;
    }

    /** 获取用户的购物车总价
     * @param $uid
     * @param $sid 店铺id
     * @return mixed
     */
    public function getShopcartMoney($uid,$sid)
    {
        $res = M('shopcart')->where(array('uid'=>$uid,'shop_id'=>$sid))->sum('num*price');
        return $res;
    }

    /** 获取用户在一个店铺的购买次数
     * @param $uid
     * @param $sid
     * @return mixed
     */
    public function getUserBuyTimes($uid, $sid)
    {
        $buy = M('user_info')->where(array('uid'=>$uid,'partner_id'=>$sid))->getField('buy_times');
        return $buy;
    }

    /** 获取订单的总金额
     * @param $oid
     * @return mixed
     */
    public function getOrderTotalMoney($oid)
    {
        $total = M('order')->where(array('id'=>$oid))->getField("total_money");
        return $total;
    }

    /** 获取订单基本信息，通常用做获取金额，配送方式之类的，变化量不用这个
     * @param $oid
     * @return mixed
     */
    public function getOrderBaseInfo($oid)
    {
        $key = "getOrderBaseInfo_".$oid;
        $info = S($key);
        if(empty($info)){
            $info = M('order')->find($oid);
            if(empty($info)){
                $info = M('order_bak')->find($oid);
            }
            S($key,$info,TIME_TEN_MINUTE);
        }
        return $info;
    }


    //获得周餐用户今日订单
    public function getWeekUserOrder($uid){
        $list = M('order')->where(array('uid'=>$uid,'base_id'=>0))->order('id desc')->select();
        foreach($list as $key=>$order){
            $child_orders = M('order')->where(array('base_id'=>$order['id']))->select();
            foreach($child_orders as $child_order){
                $sql = "SELECT od.*,sg.img FROM sm_order_detail od LEFT JOIN sm_shop_goods sg ON od.goods_id=sg.id WHERE od.order_id=".$child_order['id'];
                $detail = M()->query($sql);
                $list[$key]['day'][$child_order['reserve_time']]['order_info'] = $child_order;
                $list[$key]['day'][$child_order['reserve_time']]['detail_info'] = $detail;
            }
            $activity = M('order_activity')->where(array('oid'=>$order['id']))->select();
            $list[$key]['activity'] = $activity;
        }
        // var_dump($list[1]['day']);die;
        return $list;
    }

    //获得周餐用户历史订单
    public function getWeekUserOrderBak($uid){
        $list = M('order_bak')->where(array('uid'=>$uid,'base_id'=>0))->order('id desc')->limit(5)->select();

        foreach($list as $key=>$order){

            //历史订单查找
            $child_orders = M('order_bak')->where(array('base_id'=>$order['id']))->select();         
            foreach($child_orders as $child_order){
                $list[$key]['day'][$child_order['reserve_time']]['order_info'] = $child_order;
                $sql = "SELECT od.*,sg.img FROM sm_order_detail_bak od LEFT JOIN sm_shop_goods sg ON od.goods_id=sg.id WHERE od.order_id=".$child_order['id'];
                $detail = M()->query($sql);
                $list[$key]['day'][$child_order['reserve_time']]['detail_info'] = $detail;
            }
            
            //今日订单查找
            $child_orders = M('order')->where(array('base_id'=>$order['id']))->select();       
            foreach($child_orders as $child_order){
                $list[$key]['day'][$child_order['reserve_time']]['order_info'] = $child_order;
                $sql = "SELECT od.*,sg.img FROM sm_order_detail od LEFT JOIN sm_shop_goods sg ON od.goods_id=sg.id WHERE od.order_id=".$child_order['id'];
                $detail = M()->query($sql);
                $list[$key]['day'][$child_order['reserve_time']]['detail_info'] = $detail;
            }
            


            

            $activity = M('order_activity')->where(array('oid'=>$order['id']))->select();
            $list[$key]['activity'] = $activity;
        }
        // var_dump($list);die;
        return $list;
    }

}