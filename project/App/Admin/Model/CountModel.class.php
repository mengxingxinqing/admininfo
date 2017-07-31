<?php
namespace Partner\Model;

class CountModel extends BaseModel
{
    public function goodsSalesPercent($shop_id,$count_type,$start_time,$end_time){
        $where = ' AND odb.reserve_time>='.strtotime($start_time).' AND odb.reserve_time<'.strtotime($end_time);
        if($shop_id !=0){
            $where .= ' AND odb.shop_id='.$shop_id;
        }
        $where .= ' AND ob.status=3';
        if($count_type == 1){
            $count_type = 'odb.total_money';
        }else{
            $count_type = 'odb.num';
        }

        $sql = "SELECT SUM(".$count_type.") AS value,sg.name FROM sm_order_detail_bak odb LEFT JOIN sm_base_goods sg ON odb.basegoods_id=sg.id LEFT JOIN sm_order_bak ob ON odb.order_id=ob.id WHERE odb.partner_id=".session('partner_id').$where.' GROUP BY odb.basegoods_id';
        $res = M()->query($sql);
        return $res;
    }

    public function allOrderCount($shop_id,$start_time,$end_time){
    	$where = ' AND o.create_time>='.$start_time.' AND o.create_time<'.$end_time;
    	if($shop_id !=0){
    		$where .= ' AND o.shop_id='.$shop_id;
    	}
    	$sql = "SELECT o.shop_name,o.shop_id,count(*) AS order_count,SUM(o.pay_money) AS total_sales FROM sm_order_bak o WHERE o.partner_id=".session('partner_id').$where.' GROUP BY o.shop_id';
    	$res = M()->query($sql);
    	return $res;
    }

    public function newOrderCount($shop_id,$start_time,$end_time){
    	$where = ' AND o.create_time>='.$start_time.' AND o.create_time<'.$end_time;
    	if($shop_id !=0){
    		$where .= ' AND o.shop_id='.$shop_id;
    	}
    	$sql = " SELECT count(*) AS order_count,o.shop_id FROM sm_user_info ui LEFT JOIN sm_order_bak o ON ui.first_order_id=o.id WHERE ui.partner_id =".session('partner_id').$where;
    	$res = M()->query($sql);
    	return $res;
    }

    public function twoWeekOrder($start_time,$end_time,$shop_id){
		$before_start_time = $start_time-86400*14;
		$before_end_time = $start_time-86400;
		$sql = "SELECT count(*) AS counts FROM sm_order_bak ord WHERE ord.uid IN (SELECT DISTINCT o.uid FROM sm_order_bak o WHERE o.shop_id =".$shop_id." AND o.partner_id=".session('partner_id')." AND o.status=2 AND o.is_del=0 AND o.create_time>=".$before_start_time." AND o.create_time<".$before_end_time.") AND ord.shop_id=".$shop_id." AND ord.partner_id=".session('partner_id')." AND ord.status=2 AND ord.is_del=0 AND ord.create_time>=".$start_time." AND ord.create_time<".$end_time;

		$res = M()->query($sql);
		return $res[0]['counts'];
	}

	public function orderAnalysisByPartner($start_time,$end_time){
		$sql = "SELECT datepos,total_num,total_money,avg_money,old_money FROM sm_partner_count WHERE partner_id=".session('partner_id')." AND datepos>='".$start_time."' AND datepos<='".$end_time."' GROUP BY datepos";
		$res = M()->query($sql);
		return $res;
	}

	public function orderAnalysisByShop($shop_id,$start_time,$end_time){
		$sql = "SELECT datepos,total_num,total_money,avg_money,old_money FROM sm_shop_count WHERE partner_id=".session('partner_id')." AND shop_id=".$shop_id." AND datepos>='".$start_time."' AND datepos<='".$end_time."' GROUP BY datepos";
		$res = M()->query($sql);
		return $res;
	}

    public function userNum($pid, $start_time, $end_time)
    {
        $sql = "SELECT datepos,total_num,inc_num,dec_num FROM sm_wechat_user_log WHERE partner_id=".$pid." AND datepos>='".$start_time."' AND datepos<='".$end_time."' GROUP BY datepos";
        $res = M()->query($sql);
        return $res;
	}

    public function userFunnel($partner_id){
        $sql = "SELECT type AS name,num AS value FROM sm_user_step WHERE partner_id=".$partner_id." ORDER BY type";
        return M()->query($sql);
    }

    public function goodsSalesSort($partner_id,$shop_id,$start_time,$end_time,$sort){
        $where = ' AND odb.reserve_time>='.strtotime($start_time).' AND odb.reserve_time<'.strtotime($end_time);
        if($shop_id !=0){
            $where .= ' AND odb.shop_id='.$shop_id;
        }
        $where .= ' AND ob.status=3';
        if($sort == 1){
            $sort = 'sales_money';
        }else{
            $sort = 'sales_num';
        }
        $sql = "SELECT sg.name AS goods_name,SUM(odb.total_money) as sales_money,SUM(odb.num) as sales_num FROM sm_order_detail_bak odb LEFT JOIN sm_base_goods sg ON odb.basegoods_id=sg.id LEFT JOIN sm_order_bak ob ON odb.order_id=ob.id WHERE odb.partner_id=".$partner_id.$where." GROUP BY odb.basegoods_id ORDER BY ".$sort." DESC";
        $res = M()->query($sql);
        return $res;
    }

    public function getInitTime($partner_id,$start_time,$end_time){
        if(empty($start_time)){
            $today = strtotime('today');
            $start_time = $today - 30*86400;
            $create_time = M('partner')->where(array('id'=>$partner_id))->getField('create_time');
            $create_time = strtotime(date('Y-m-d',$create_time));
            if($start_time<$create_time){
                $start_time = $create_time;
            }
            $end_time = $today-86400;
            if($end_time<$start_time){
                $end_time = $start_time;
            }
            $time['start_time'] = date('Y-m-d',$start_time);
            $time['end_time'] = date('Y-m-d',$end_time);
        }else{
            $time['start_time'] = $start_time;
            $time['end_time'] = $end_time;
        }
        return $time;
    }

    public function o2oData($p,$partner_id,$shop_id,$start_time,$end_time,$pay_type,$order_type){
        if($shop_id != 0 ){
            $where = " AND o.shop_id=".$shop_id;
        }else{
            $where = "";
        }
        // $where .= " AND o.pay_to=0 ";
        $where .= ' AND o.base_id=0';
        if($order_type == 1){
            $sql = "SELECT SUM(o.pay_money) AS pay_money,SUM(o.total_money) AS total_money,SUM(o.send_money) as send_money,FROM_UNIXTIME(o.reserve_time,'%Y-%m-%d') AS order_date FROM sm_order o WHERE (o.status=3 OR o.status=1 OR o.status=2) AND o.partner_id=$partner_id ".$where." AND o.pay_type=$pay_type GROUP BY order_date ORDER BY order_date DESC";
        }else{
            $end_time = $end_time+86400;
            $sql = "SELECT SUM(o.pay_money) AS pay_money,SUM(o.total_money) AS total_money,SUM(o.send_money) as send_money,FROM_UNIXTIME(o.reserve_time,'%Y-%m-%d') AS order_date FROM sm_order_bak o WHERE (o.status=3 OR o.status=1 OR o.status=2) AND o.partner_id=$partner_id ".$where." AND o.pay_type=$pay_type AND o.reserve_time>$start_time AND o.reserve_time<$end_time GROUP BY order_date ORDER BY order_date DESC";
        }
        $res = M()->query($sql);
        foreach($res as $key=>$value){
            $arr[$value['order_date']] = $value;
        }
        return $arr;
    }


    //充值
    public function getRecharg($partner_id,$shop_id,$start_time,$end_time){
        if($shop_id != 0){
            $where = ' AND shop_id='.$shop_id;
        }else{
            $where = '';
        }
        $end_time = $end_time+86400;

        $sql = "SELECT SUM(order_money) AS recharge,FROM_UNIXTIME(create_time,'%Y-%m-%d') AS order_date FROM sm_partner_paylog WHERE status=0 AND partner_id=$partner_id ".$where." AND create_time>=$start_time AND create_time<$end_time GROUP BY order_date";
        $res = M()->query($sql);
        foreach($res as $key=>$value){
            $arr[$value['order_date']] = $value;
        }
        return $arr;
    }

    //订单充值
    public function getOrderRecharg($partner_id,$shop_id,$start_time,$end_time){
        if($shop_id != 0){
            $where = ' AND shop_id='.$shop_id;
        }else{
            $where = '';
        }
        $end_time = $end_time+86400;

        $sql = "SELECT SUM(order_money) AS order_recharge,FROM_UNIXTIME(create_time,'%Y-%m-%d') AS order_date FROM sm_partner_paylog WHERE status=0 AND type=1 AND partner_id=$partner_id ".$where." AND create_time>=$start_time AND create_time<$end_time GROUP BY order_date";
        $res = M()->query($sql);
        foreach($res as $key=>$value){
            $arr[$value['order_date']] = $value;
        }
        return $arr;
    }

    //电话扣费
    public function getPhoneMoney($partner_id,$shop_id,$start_time,$end_time){
        if($shop_id != 0){
            $where = ' AND shop_id='.$shop_id;
        }else{
            $where = '';
        }
        $end_time = $end_time+86400;

        $sql = "SELECT SUM(money) AS phone_money,FROM_UNIXTIME(create_time,'%Y-%m-%d') AS order_date FROM sm_partner_declog WHERE status=0 AND type=1 AND partner_id=$partner_id ".$where." AND create_time>=$start_time AND create_time<$end_time GROUP BY order_date";
        $res = M()->query($sql);
        foreach($res as $key=>$value){
            $arr[$value['order_date']] = $value;
        }
        return $arr;
    }

    //用户充值
    public function getUserRecharge($partner_id,$start_time,$end_time){
        $end_time = $end_time+86400;
        $sql = "SELECT SUM(money) AS user_recharge,FROM_UNIXTIME(create_time,'%Y-%m-%d') AS order_date FROM sm_user_recharge_log WHERE status=1 AND partner_id=$partner_id AND create_time>=$start_time AND create_time<$end_time GROUP BY order_date";
        $res = M()->query($sql);
        foreach($res as $key=>$value){
            $arr[$value['order_date']] = $value;
        }
        return $arr;
    }
}