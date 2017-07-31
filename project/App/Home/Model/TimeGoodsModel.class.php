<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-10-28
 * Time: 14:34
 */

namespace Home\Model;


class TimeGoodsModel
{
    public function getTimeList($pid, $sid)
    {
        $key = "getTimeList_".$pid."_".$sid;
//        $res = S($key);
        if(empty($res)){
            $time = time();
            $week = array(7,1,2,3,4,5,6);
            $str = array(7=>"周日",1=>"周一",2=>"周二",3=>"周三",4=>"周四",5=>"周五",6=>"周六");
            $w = date('w',$time);
            $index = $week[$w];
            $sql = "select id,day_name,status from sm_day_set where partner_id=$pid and shop_id=$sid and day_name>=$index and status>0 order by day_name asc";
            $list = M()->query($sql);
            $sql = "select * from sm_day_set where partner_id=$pid and shop_id=$sid and day_name<$index and status>0 order by day_name asc";
            $list2 = M()->query($sql);
            $res = array_merge($list,$list2);
            foreach($res as $k=>$v){
                $dis = $this->getDist($index,$v['day_name']);
                $t = $dis*TIME_ONE_DAY;
                $res[$k]['day_long'] = date('Y-m-d',$time+$t);
                $res[$k]['day_short'] = (int)date('d',$time+$t);
                $res[$k]['wname'] = $str[$v['day_name']];
            }
            S($key,$res,TIME_ONE_HOUR);
        }
        return $res;
    }

    private function getDist($t, $n)
    {
        if($n>=$t){
            return $n-$t;
        }else{
            return 7-$t+$n;
        }
    }

    /** 根据日期获取商品列表
     * @param $pid
     * @param $sid
     * @param $day_id
     * @return mixed
     */
    public function getDayGoodsList($pid, $sid, $day_id){
        $sql = "select s.*,d.day_id from sm_day_goods d left join sm_shop_goods s on s.id=d.goods_id WHERE d.day_id=$day_id order by d.sort asc";
        $list = M()->query($sql);
        return $list;
    }

    /** 根据日期获取商品详情
     * @param $pid
     * @param $sid
     * @param $day_id
     * @return mixed
     */
    public function loadweekdetail($pid,$sid,$day_id)
    {
        $list = M('day_goods')->where(array('day_id'=>$day_id))->field('goods_id,day_id,status,store')->select();
        $data = array('status'=>"success",'data'=>array());
        foreach ($list as $v){
            if($v['status'] == 0){
                $tmp['status'] = 'success';
            }else{
                $tmp['status'] = 'store_zero';
            }
            $tmp['id'] = $v['goods_id'];
            $tmp['store'] = $v['store'];
            $data['data'][] = $tmp;
        }
        return $data;
    }

    public function loadweekshopcart($pid,$sid,$day_id)
    {

    }

    public function loadTime($pid, $sid)
    {
        $time = date('H:i',time());

        $conf = M('shop_conf')->where(array('shop_id'=>$sid))->find();
        //用营业时间卡
        if($time<$conf['start_time']){
            return 2;
        }elseif ($time>$conf['end_time']){
            return 3;
        }

        $sql = "select count(*) as num from sm_reserve_conf where partner_id=$pid and shop_id=$sid and buy_time>'$time'";
        //用固定配送时间卡
        $res = M()->query($sql);
        $res = $res[0]['num'];
        if($res>0){
            return 0;
        }
        // $conf = M('shop_conf')->where(array('shop_id'=>$sid))->field('sendtime_start,sendtime_end')->find();
        if($time<$conf['sendtime_start']){
            return 2;
        }elseif ($time>$conf['sendtime_end']){
            return 3;
        }else{
            return 1;
        }
    }


    /** 获取可选的配送时间
     * @param $pid
     * @param $sid
     * @return mixed
     */
    public function getSendTime($pid, $sid,$uid)
    {
        //判断购物车有没有今天的商品
        $today = date('Y-m-d',time());
        $today = strtotime($today);
        $num = M('shopcart')->where(array('shop_id'=>$sid,'uid'=>$uid,'buy_time'=>$today))->count();
        if($num>0){
            $time = date('H:i',time());
            $sql = "select pre_time,end_time from sm_reserve_conf where partner_id=$pid and shop_id=$sid and buy_time>'$time' order by pre_time asc";
        }else{
            $sql = "select pre_time,end_time from sm_reserve_conf where partner_id=$pid and shop_id=$sid order by pre_time asc";
        }
        $res = M()->query($sql);
        return $res;
    }

    public function changeWeekNum($day_id, $time, $gid, $num,$pid,$sid,$uid)
    {
        $time = strtotime($time);
        $where['shop_id'] = $sid;
        $where['uid'] = $uid;
        $where['goods_id'] = $gid;
        $where['day_id'] = $day_id;
        $where['buy_time'] = $time;
        $gmodel = new GoodsModel();
        $res = M('shopcart')->where($where)->count();
        if($res != 0){
            if($num>0){
                M('shopcart')->where($where)->setInc('num',$num);
            }else{
                M('shopcart')->where($where)->setDec('num',abs($num));
                $n = M('shopcart')->where($where)->getField('num');
                if($n<=0){
                    M('shopcart')->where($where)->delete();
                }
            }
        }else{
            if($num>0){
                $data = $where;
                $data['create_time'] = time();
                $data['num'] = $num;
                $data['partner_id'] = $pid;
                $data['price'] = $gmodel->getGoodsPrice($gid);
                $data['goods_name'] = $gmodel->getGoodsName($gid);
                $data['buy_time'] = $time;
                M('shopcart')->add($data);
            }
        }
    }


     /** 重新构造购物车商品结构-周餐
     * @param $uid
     * @param $shop_id
     * @return array
     */
    public function loadShopcartWeek($uid,$shop_id,$remove=0){
        //goods_info
        $sql = "SELECT sc.id AS sc_id,sc.goods_id,sc.goods_name,sc.partner_id,sc.num,sc.price,sc.num*sc.price AS total_money,sc.buy_time,sc.day_id,s.name AS shop_name,s.is_close,s.no_send,sconf.base_price,sconf.start_time AS ps_start,sconf.end_time AS ps_end,sg.is_off,sg.basegoods_id,sg.category_id FROM sm_shopcart sc LEFT JOIN sm_shop s ON sc.shop_id=s.id LEFT JOIN sm_shop_conf sconf ON sc.shop_id=sconf.shop_id LEFT JOIN sm_shop_goods sg ON sc.goods_id=sg.id WHERE sc.uid=".$uid." AND sc.shop_id=".$shop_id;
        $goods_list = M()->query($sql);

        $order_money = 0;
        $order_num = 0;
        $res = array();
        $goods_model = new GoodsModel();
        foreach($goods_list as $key=>$item){
            $index = $item['buy_time'];
            


            $order_money = bcadd($order_money, $item['total_money'],2);
            $order_num += $item['num'];


            if(empty($res[$index])){
                $res[$index] = array();
                $time_info = self::getWeekTime($item['buy_time']);
                $res[$index]['day_info']['day'] = $time_info['day']; 
                $res[$index]['day_info']['time'] = $time_info['time'];
                $res[$index]['day_info']['day_id'] = $item['day_id'];
                $res[$index]['day_info']['buy_time'] = $item['buy_time'];
                $res[$index]['shop_info']['base_price'] = $item['base_price'];
                $res[$index]['shop_info']['need_money'] = 0;
                $res[$index]['shop_info']['total_money'] = 0;
            }

            //该日期的商品总钱数
            $res[$index]['shop_info']['total_money'] = bcadd($res[$index]['shop_info']['total_money'],$item['total_money'],2);
            //该日期的与起送价相差的钱
            // $res[$index]['shop_info']['need_money'] = $item['base_price'] - $res[$index]['shop_info']['total_money'];
            // if($res[$index]['shop_info']['need_money']<0) $res[$index]['shop_info']['need_money'] = 0;


            /*$shop_open = $goods_model->shopIsOpen($item['is_close'],$item['no_send'],$item['ps_start'],$item['ps_end']);

            $canBuyInfo = $goods_model->goodsCanBuy($shop_open,$item['is_off'],$item['ps_start'],$item['ps_end'],0);

            $goods_canbuy = $canBuyInfo['can_buy'];    
            $item['can_buy'] = $goods_canbuy;*/ 
            $store = M('day_goods')->where(array('shop_id'=>$shop_id,'goods_id'=>$item['goods_id'],'day_id'=>$item['day_id']))->getField('store');
            $item['store'] = $store;

            $res[$index]['goods_info'][] = $item; 
        }
        ksort($res);
        $result['res'] = $res;                
        //shop_info
        $sql = "SELECT s.partner_id,s.name,sconf.base_price FROM sm_shop s LEFT JOIN sm_shop_conf sconf ON s.id=sconf.shop_id WHERE s.id=".$shop_id;
        $shop_res = M()->query($sql);
        $shop_info = $shop_res['0']; 
        $need_money = $shop_info['base_price'] - $order_money;
        if($need_money < 0) $need_money = 0;
        $shop_info['need_money'] = $need_money;
        $result['shop_info'] = $shop_info;
        if($need_money == 0){
            $can_next = 1;
        }else{
            $can_next = 0;
        }
        $result['can_next'] = $can_next;//self::checkCanNext($res);
        $result['total_money'] = $order_money;
        $result['total_num'] = $order_num;
        // var_dump($result);die;
        return $result;
    }

    public function getWeekTime($time){
        $index = date('w',$time);
        switch ($index) {
            case 1:
                $day = '周一';
                break;
            case 2:
                $day = '周二';
                break;
            case 3:
                $day = '周三';
                break;
            case 4:
                $day = '周四';
                break;
            case 5:
                $day = '周五';
                break;
            case 6:
                $day = '周六';
                break;
            case 0:
                $day = '周日';
                break;            
            default:
                $day = '周一';
                break;
        }
        $data['day'] = $day;
        $data['time'] = date('H:i',$time);
        return $data;
    }

    public function checkCanNext($res){
        $can_buy = 1;
        foreach($res as $key=>$day){
            if($day['shop_info']['need_money']>0){
                $can_buy = 0;
                break;
            }
        }
        return $can_buy;
    }

    public function loadShopMsg($partner_id,$shop_id)
    {
        $res = M('shop_conf')->where(array('shop_id'=>$shop_id))->getField('dayset_announce');
        return $res;
    }

    public function addOrderWeek($uid,$pid,$shop_id,$res,$input,$activity_info){
        \Think\Log::write("tong-test3 add order");
        $order_model = new OrderModel();
        $time = time();
//        if(empty($input['reserve_time'])){
            $reserve_time = $time;
//        }else{
//            $reserve_time = $input['reserve_time'];
//        }
        $real_price = $total_money = $res['total_money']+$input['send_money'];
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
        );

        //扣除红包金额
        if($input['bonus_id'] != ""){
            $bonus_info = $order_model->checkbonus($input['bonus_id']);
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


        //存主订单
        $data['is_base'] = 1;
        $data['base_id'] = 0;
        $base_id = M('order')->add($data);
        
        //生成子订单
        $child_sqnum = '';
        $index = 0;
        foreach($res['res'] as $key_day=>$day){
            // $data['reserve_time'] = $key_day;
            $data['total_money'] = $day['shop_info']['total_money'];
            $data['pay_money'] = $day['shop_info']['total_money'];
            $data['is_base'] = 0;
            $data['base_id'] = $base_id;
            $data['reserve_time']= self::addTime($key_day,$input['reserve_time']);


            if($index == 0){
                //第一天订单
                //总订单时间按第一天时间
                M('order')->where(array('id'=>$base_id))->save(array('reserve_time'=>$data['reserve_time']));
                $data['total_money'] += $input['send_money'];
                $data['pay_money'] += $input['send_money'];

                if($input['bonus_id'] !== ""){
                    $bonus_info = $order_model->checkbonus($input['bonus_id']);
                    $bonus_money = $bonus_info['money'];
                    if(!empty($bonus_info) && $bonus_info['partner_id'] == $pid){    
                        $data['pay_money'] -= $bonus_money;
                        $data['bonus_id'] = $bonus_info['id'];
                        $data['bonus_money'] = $bonus_money;
                    }
                }

                //扣除活动金额(不包含首单优惠)
                foreach ($activity_info as $key => $activity) {
                    if($activity['type'] == 'dec' && !isset($activity['buy_date'])){
                        $data['pay_money'] -= $activity['money'];
                    }
                }

            }else{
                unset($data['bonus_id']);
                unset($data['bonus_money']);
                unset($data['send_money']);
            }

            //扣除首单优惠活动金额
            foreach ($activity_info as $key => $activity) {
                if($activity['type'] == 'dec' && isset($activity['buy_date']) && $activity['buy_date'] == $key_day){
                    $data['pay_money'] -= $activity['money'];
                }
            }
            $order_id = M('order')->add($data);

            if($index == 0){
                //所有活动存到第一天(不包含每日首单优惠)
                $all_activity = array();
                foreach($activity_info as $key=>$activity){
                    if(!isset($activity['buy_date'])){
                        $all_activity[] = $activity;
                    }
                }
                $order_model->addActivity($all_activity,$order_id,$uid,$shop_id,$pid);
            }

            //存每日首单优惠活动
            $goods_discount_activity = array();
            foreach ($activity_info as $key => $activity) {
                if(isset($activity['buy_date']) && $activity['buy_date'] == $key_day){
                    $goods_discount_activity[] = $activity;
                }
            }
            $order_model->addActivity($goods_discount_activity,$order_id,$uid,$shop_id,$pid);
            



            $sqnum = $order_model->addSqnum($order_id,$shop_id,$data['reserve_time']);
            $child_sqnum .= $sqnum.',';
            $goods_list = $day['goods_info'];
            foreach($goods_list as $goods){
                $category_info = $order_model->getCategoryInfo($goods['category_id']);
                $data1 = array(
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
                    'reserve_time'=>$data['reserve_time'],
                    'goods_name' =>$goods['goods_name'],
                    'category_id'=>$goods['category_id'],
                    'category_name'=>$category_info[0]['category_name'],
                    'basecategory_id'=>$category_info[0]['basecategory_id']
                );
                $id = M('order_detail')->add($data1);
            }
            $index++;
        }
        $child_sqnum = substr($child_sqnum, 0,-1);
        //主单存储子单订单号
        M('order')->where(array('id'=>$base_id))->save(array('user_attrs'=>$child_sqnum,'sqnum'=>$base_id));

        //生成订单序号并存储
        // $this->addSqnum($order_id,$shop_id,$reserve_time);

        //父订单存活动
        $order_model->addActivity($activity_info,$base_id,$uid,$shop_id,$pid);
        \Think\Log::write("tong-test5");
        return $base_id;
    }

    private function addTime($time,$add){
        $res = explode(':',$add);
        $time = (int)$time+$res[0]*3600+$res[1]*60;
        return $time;
    }

}