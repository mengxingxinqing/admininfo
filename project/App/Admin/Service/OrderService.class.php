<?php
namespace Partner\Service;
use Api\Service\SendService;
use Home\Service\MoneyService;
use Partner\Model\OrderModel;
use Base\Service\WechatHelper;

class OrderService extends BaseService{

	public function changeOrderStatus($order_id,$status,$type='order'){
		$model = new OrderModel();
		if($status == 1){
			$model->confirmOrder($order_id,$type);
		}elseif($status == 2){
			$model->sendOrder($order_id,$type);
		}elseif($status == 3){
			$model->completeOrder($order_id,$type);
			$model->toOldOrder($order_id);
		}elseif($status ==4){
			$model->cancelOrder($order_id,$type);
		}
	}

    public function changeSendType($pid,$sid,$oid, $sendkey)
    {
        $ser = new SendService();
        $val = S('sendtype_'.$oid.'_'.$sendkey);
        $res = $ser->send($pid,$sid,$oid,$sendkey,$val);
        if($res['code'] == 0){
            return '';
        }else{
            return $res['msg'];
        }
	}

    public function giveUpSend($oid)
    {
        $ser = new \Api\Service\OrderService();
        $data = $ser->resetSendStatus();
        $data['send_status'] = "已取消";
        $data['status'] = 1;
        M('order')->where(array('id'=>$oid))->save($data);
	}

	public function addRefundList($order_info,$refund,$type){
		$model = new OrderModel();
		$model->addRefundList($order_info,$refund);
		$model->setOrderRefundStatus($order_info['id'],$type);
	}

	public function addRechargeRefundList($order_info,$type){
		$model = new OrderModel();
		$model->addRechargeRefundList($order_info);
		$model->setOrderRefundStatus($order_info['id'],$type);
	}

	public function getOrderList($p,$partner_id,$shop_id,$key,$reserve_time,$is_reserve=0,$pick_id){
		$model = new OrderModel();
		$res = $model->getOrderList($p,$partner_id,$shop_id,$key,$reserve_time,$is_reserve,$pick_id);
		foreach($res['data'] as $key=>$order){
			$activity = $model->getActivity($order['id']);
			$res['data'][$key]['activity'] = $activity;
		}
		return $res;
	}

	public function getBakOrderList($p,$shop_id,$key,$start_time,$end_time,$status){
		$model = new OrderModel();
		$res = $model->getBakOrderList($p,$shop_id,$key,$start_time,$end_time,$status);
		foreach($res['data'] as $key=>$order){
			$activity = $model->getActivity($order['id']);
			$res['data'][$key]['activity'] = $activity;
		}
		return $res;
	}

	public function getOrderListByUid($p,$uid){
		$model = new OrderModel();
		$res = $model->getOrderListByUid($p,$uid);
		foreach($res['data'] as $key=>$order){
			$activity = $model->getActivity($order['id']);
			$res['data'][$key]['activity'] = $activity;
		}
		return $res;
	}

	public function refund($partner_id,$order_id,$type="order"){
        $model = new OrderModel();

        $base_id = M($type)->where(array('id'=>$order_id))->getField('base_id');
        //判断是否有父订单
        if($base_id > 0){
        	//有父订单
        	$is_set = M($type)->where(array('id'=>$base_id))->find();

        	if(empty($is_set)){

        		if($type == 'order'){
        			$type = 'order_bak';
        		}else{
        			$type = 'order';
        		}

        	}
        	//有父订单，退款使用父订单id
        	$order_id = $base_id;
        }

        $order_info = $model->getOrderById($order_id,$type);
        $pay_to = $order_info['pay_to'];
        if($order_info['pay_type'] == 1){
        	//微信支付退单
            $helper = WechatHelper::get($partner_id);
            $res = $helper->refund($order_id,$order_info['order_num'],$order_info['pay_money'],$pay_to);

            if($res['result_code'] == "SUCCESS" && $order_info['is_refund'] == 0){
                //微信支付成功且首次点击取消按钮
                self::addRefundList($order_info,$res,$type);

				$mser = new MoneyService();
				if($pay_to == PAYTO_SYSTEM){
					//如果是从系统里退款 则商户充值要往下减
					$mser->refund($order_id,$partner_id,$order_info['pay_money']);
				}
				//订单扣点 返还回来
				$mser->giveUpOrder($order_id,$pay_to);

                return $res;
            }else{
            	//退款失败
            	if($order_info['is_refund'] !=0 ){
            		$res['err_code_des'] = '已退过款';
            	}
            	if(empty($res['err_code_des'])){
            		$res['err_code_des'] = '未知状况';
            	}
            	return $res;
            }
        }elseif($order_info['pay_type'] == 3){
        	//储值支付退单

        	if($order_info['is_refund'] == 0){
        		//首次退款
        		self::addRechargeRefundList($order_info,$type);
        		$mser = new MoneyService();
        		//订单扣点 返还回来
				$mser->giveUpOrder($order_id,$pay_to);
				$res['result_code'] = "SUCCESS";
				return $res;
        	}else{
        		$res['result_code'] = "FALSE";
        		$res['err_code_des'] = '已退过款';
        		return $res;
        	}

        }else{
            $mser = new MoneyService();
            //订单扣点 返还回来
            $mser->giveUpOrder($order_id,$pay_to);
            $res['result_code'] = "SUCCESS";
            return $res;
        }
	}

	public function getReserveDays($partner_id,$shop_id=0){
		$model = new OrderModel();
		$res = $model->getReserveDays($partner_id,$shop_id);
		$now_time = strtotime('today');
		$arr = array();
		$i = 1;
		while($i <= $res){
			if($i == 1){
				$arr[$i-1]['name'] = '明天';
				
			}else{
				$arr[$i-1]['name'] = date('m-d',$now_time + $i*86400);
			}
			$arr[$i-1]['date'] = date('Y-m-d',$now_time + $i*86400);
			$i++;
		}
		return $arr;
	}

    public function addSendTip($oid, $money)
    {
        M('order')->where(array('id'=>$oid))->save(array('send_tip'=>$money));
	}

	public function getHistoryList(){
		$time = time();
		for($i=1;$i<=6;$i++){
			$time = strtotime(date('Y-m',$time));
			$time  = $time - 86400;
			$arr[] = date('Y-m',$time);
		}
		return $arr;
	}

	public function getOrderCount($partner_id,$shop_id){
		//$days 不包含今天
		$days = self::getReserveDays($partner_id,$shop_id);
		$model = new OrderModel();
		$start_time = strtotime('today');
		$end_time = $start_time + 86400;
		$start_time = 0;
		$count = $model->getOrderCount($partner_id,$shop_id,$start_time,$end_time);
		$data[] = $count;
		foreach($days as $key=>$day){
			$start_time = strtotime($day['date']);
			$end_time = $start_time + 86400;
			$count = $model->getOrderCount($partner_id,$shop_id,$start_time,$end_time);
			$data[] =  $count;
		}
		return $data;
	}

	public function orderRealTimeCount($partner_id){
		$start_time = strtotime('today');
		$end_time = $start_time + 86400;
		$model = new OrderModel();
		$goods_list = $model->goodsCountList($partner_id,$start_time,$end_time);
		foreach($goods_list as $key=>$goods){
			$res = $model->goodsCountByShop($partner_id,$goods['basegoods_id'],$start_time,$end_time);
			$goods_list[$key]['shop_goods_count'] = $res;
			$goods_list[$key]['shop_num'] = count($res);
		}
		return $goods_list;
	}

	//导出今日订单用
	public function getAllOrderList($partner_id,$shop_id,$key,$reserve_time){
		$model = new OrderModel();
		$res = $model->getAllOrderList($partner_id,$shop_id,$key,$reserve_time);
		foreach($res['data'] as $key=>$order){
			$activity = $model->getActivity($order['id']);
			$res['data'][$key]['activity'] = $activity;
		}
		return $res['data'];
	}

	public function getBaseWeekOrderList($p,$partner_id,$shop_id,$search_key,$reserve_time){
		$model = new OrderModel();
		$res = $model->getBaseWeekOrderList($p,$partner_id,$shop_id,$search_key,$reserve_time);
		foreach($res['data'] as $key=>$order){
			$activity = $model->getActivity($order['id']);
			$res['data'][$key]['activity'] = $activity;
		}
		return $res;
	}

	public function getBakBaseWeekOrderList($p,$shop_id,$search_key,$start_time,$end_time,$status){
		$model = new OrderModel();
		$res = $model->getBakBaseWeekOrderList($p,$shop_id,$search_key,$start_time,$end_time,$status);
		foreach($res['data'] as $key=>$order){
			$activity = $model->getActivity($order['id']);
			$res['data'][$key]['activity'] = $activity;
		}
		return $res;
	}

    public function setRemark($id, $remark)
    {
        M('order')->where(array('id'=>$id))->save(array('shop_remark'=>$remark));
	}
}