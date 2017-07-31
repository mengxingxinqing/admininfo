<?php
namespace Partner\Service;
use Base\Service\WechatHelper;
use Partner\Model\PartnerModel;
use Partner\Model\ShopModel;
use Partner\Model\WechatModel;
use Partner\Model\SettingModel;

class ShopService extends BaseService{
	public function addShop($data){
		$model = new ShopModel();
        $pid = session('partner_id');
		//shop表添加
		$shop['name'] = $data['name'];
		$shop['shop_manager'] = $data['shop_manager'];
		$shop['account'] = $data['account'];
		$shop['pwd'] = $data['pwd'];
		$shop['partner_id'] = session('partner_id');
		// $shop['address'] = $data['address'];
		$shop['tel'] = $data['tel'];
		$shop['create_time'] = time();
		// $shop['lng'] = $data['lng'];
		// $shop['lat'] = $data['lat'];
		$shop['admin_uid'] = $data['admin_uid'];
		$shop['is_close'] = 1;
		$shop_id = $model->add('shop',$shop);
		//shop_conf表添加
		$conf['partner_id'] = session('partner_id');
		$conf['shop_id'] = $shop_id;


		if(strlen($data['start_time']) == 4){
            $data['start_time'] = '0'.$data['start_time'];
        }
        if(strlen($data['end_time']) == 4){
            $data['end_time'] = '0'.$data['end_time'];
        }

		
		$conf['start_time'] = $data['start_time'];
		$conf['end_time'] = $data['end_time'];
        $conf['sendtime_start'] = $data['start_time'];
        $conf['sendtime_end'] = $data['end_time'];
		// $conf['send_range'] = $data['send_range'];
		// $conf['base_price'] = $data['base_price'];
		$conf['bottom_address'] = $data['bottom_address'];
		$conf['is_pick'] = $data['is_pick'];
		if(isset($data['show_bottom_address'])){
			$conf['show_bottom_address'] = 1;
		}
        //费率信息同步
        $pmodel = new PartnerModel();
        $pconf = $pmodel->getConf($pid);
        $conf['min_money'] = $pconf['min_money'];
        $conf['max_money'] = $pconf['max_money'];
        $conf['rate'] = $pconf['rate'];
        $conf['top_money'] = $pconf['top_money'];
        $conf['money'] = 0;
        $conf['curr_status'] = PAY_STATUS_INC;

        $conf['reserve_day'] = $data['reserve_day'];
		if($conf['reserve_day'] == 0){
			$conf['reserve_status'] = 0;
		}else{
			$conf['reserve_status'] = 1;
		}
        $conf['print_times'] = $data['print_times'];
		$model->add('shop_conf',$conf);
		//添加分店分类
		$model->addShopCategory($shop_id);
		//添加分店商品
		$model->addShopGoods($shop_id);

		if($conf['is_pick'] == 1){
			//添加自提地址
			//$model->addPickAddress(session('partner_id'),$shop_id,$data['pick_address']);
			//添加自提时间
			//$model->addPickTime(session('partner_id'),$shop_id,$data['pick_start_time'],$data['pick_end_time']);
		}


		
		//添加微信组
		$helper = WechatHelper::get($_SESSION['partner_id']);
		$res = $helper->createGroup($shop_id,$shop['name']);
		$model = new WechatModel();
		$model->createShopGroup(array(
			'partner_id'=>$_SESSION['partner_id'],
			'shop_id'=>$shop_id,
			'group_name'=>$res['name'],
			'group_id'=>$res['id'],
			'create_time'=>time(),
			'update_time'=>time()
		));
		S('shopList_'.$pid,null);
		return $shop_id;
	}

    public function getConf($sid)
    {
        $res = M('shop_conf')->where(array('shop_id'=>$sid))->find();
        return $res;
	}

    public function checkShopNum()
    {
        $pid = session('partner_id');
        $model = new ShopModel();
        return $model->checkShopNum($pid);
	}

	public function editShop($data){
		$model = new ShopModel();
		//shop表修改
		$shop['name'] = $data['name'];
		$shop['shop_manager'] = $data['shop_manager'];
		$shop['account'] = $data['account'];
		$shop['pwd'] = $data['pwd'];
		$shop['partner_id'] = $_SESSION['partner_id'];
		// $shop['address'] = $data['address'];
		$shop['tel'] = $data['tel'];
		$shop['create_time'] = time();
		$shop['id'] = $data['id'];
		// $shop['lng'] = $data['lng'];
		// $shop['lat'] = $data['lat'];
		$shop['admin_uid'] = $data['admin_uid'];
		$model->editShop($shop);
		//shop_conf表修改
		$conf['partner_id'] = $_SESSION['partner_id'];
		$conf['shop_id'] = $data['id'];
        if(strlen($data['start_time']) == 4){
            $data['start_time'] = '0'.$data['start_time'];
        }
        if(strlen($data['end_time']) == 4){
            $data['end_time'] = '0'.$data['end_time'];
        }
		$conf['start_time'] = $data['start_time'];
		$conf['end_time'] = $data['end_time'];
		// $conf['send_range'] = $data['send_range'];
		// $conf['base_price'] = $data['base_price'];
		$conf['bottom_address'] = $data['bottom_address'];
		$conf['is_pick'] = $data['is_pick'];
		if(isset($data['show_bottom_address'])){
			$conf['show_bottom_address'] = 1;
		}else{
			$conf['show_bottom_address'] = 0;
		}

		$conf['reserve_day'] = $data['reserve_day'];
		if($conf['reserve_day'] == 0){
			$conf['reserve_status'] = 0;
		}else{
			$conf['reserve_status'] = 1;
		}
		$conf['print_times'] = $data['print_times'];

		$model->editShopConf($conf);

		if($conf['is_pick'] == 1){
			//添加自提地址
			//$model->addPickAddress(session('partner_id'),$data['id'],$data['pick_address']);
			//添加自提时间
			// $model->addPickTime(session('partner_id'),$data['id'],$data['pick_start_time'],$data['pick_end_time']);
		}

		//清理相关缓存
		S('shopList_'.$_SESSION['partner_id'],null);
	}

	public function deleteShop($shop_id){
		$model = new ShopModel();
		$model->deleteShop($shop_id);
	}

	public function checkAccountRepeat($account,$shop_id=''){
		$model = new ShopModel();
		$repeat = $model->checkAccountRepeat($account,$shop_id);
		return $repeat;
	}

	public function getNotice($sid)
	{
		$res = M('shop_conf')->where(array('shop_id'=>$sid))->getField('notice');
		return $res;
	}

	public function saveNotice($sid, $content)
	{
		M('shop_conf')->where(array('shop_id'=>$sid))->save(array('notice'=>$content));
	}

	public function setBuilding($partner_id,$shop_id,$data){
		$model = new ShopModel();
		if(isset($data['address'])){
			foreach($data['address'] as $key=>$value){
				$data1[$key]['partner_id'] = $partner_id;
				$data1[$key]['shop_id'] = $shop_id;
				$data1[$key]['name'] = $value;
				$data1[$key]['lat'] = $data['lat'][$key];
				$data1[$key]['lng'] = $data['lng'][$key];
				$data1[$key]['remark'] = $data['remark'][$key];
			}
			$model->setBuilding($data1);
		}else{
			//删除
			$model->deleteBuilding($shop_id);
		}
	}

	public function setSend($partner_id,$data){
		$model = new ShopModel();
		$shop_id = $data['shop_id'];
		$data1['send_range'] = $data['send_range'];
		$data1['base_price'] = $data['base_price'];
		$data1['address'] = $data['address'];
		$data1['lng'] = $data['lng'];
		$data1['lat'] = $data['lat'];
        $data1['sendtime_start'] = formatTime($data['sendtime_start']);
        $data1['sendtime_end'] = formatTime($data['sendtime_end']);
		$model->setBaseSend($shop_id,$data1);

		//sendcharge
		$setting_model = new SettingModel();
		$model->editShopConf(array('shop_id'=>$shop_id,'partner_id'=>$partner_id,'sendcharge_type'=>$data['sendcharge_type']));
		if($data['sendcharge_type'] == 1){
			//固定收费
			$charge['charge_type'] = 1;
			$charge['send_price'] = $data['send_money_fixed'];
			$charge['send_interval'] = $data['send_interval_fixed'];
			$setting_model->changeSendChargeFixed($partner_id,$shop_id,$charge);
		}else if($data['sendcharge_type'] == 2){
			//距离收费
			$setting_model->changeSendChargeDynamic($partner_id,$shop_id,$data);
		}else if($data['sendcharge_type']){
            self::setPolygonPrice($data);
        }


		/*//reserve 预定
		if($data['reserve_day'] == 0){
			$reserve_status = 0;
		}else{
			$reserve_status = 1;
		}*/
		if($data['fixed_time_status'] == 0){
			//关闭固定时间配送
			$setting_model->fixedTimeOff($partner_id,$shop_id,$data);
		}else{
			//打开固定时间配送
			$setting_model->fixedTimeOn($partner_id,$shop_id,$data);
		}
	}

    public function setAddress($data)
    {
        M('shop')->where(array('id'=>$data['shop_id']))->save($data);
	}

    public function getShopPos($sid)
    {
        $point = M('shop')->where(array('id'=>$sid))->field('lng,lat')->find();
        return $point;
	}

    public function setPolygonPrice( $data)
    {
        $ids = $data['part_id'];
        $vals = $data['part_money'];
        foreach ($ids as $k=>$v){
            M('polygon_send')->where(array('id'=>$v))->save(array('price'=>$vals[$k]));
        }
	}

    /** 获取多边形配送设定
     * @param $sid
     * @return mixed
     */
    public function getPolygonSetting($sid)
    {
        $list = M('polygon_send')->where(array('shop_id'=>$sid))->select();
        return $list;
	}

    public function choosePartSend($sid)
    {
        $num = M('polygon_send')->where(array('shop_id'=>$sid))->count();
        if($num>0){
            M('shop_conf')->where(array('shop_id'=>$sid))->save(array('sendcharge_type'=>3));
            return 0;
        }
        return 1;
	}

    public function savePolygonSetting($data)
    {
        return M('polygon_send')->add($data);
	}

    public function delPolygon($sid,$id)
    {
        M('polygon_send')->where(array('shop_id'=>$sid,'id'=>$id))->delete();
	}

    public function getSendConf($sid)
    {
        $sql = "select st.name,st.keystr as mkey,st.headerstr,ss.*,st.needparam from sm_sendtype st left join sm_shop_send ss on st.keystr=ss.keystr and ss.shop_id=$sid where st.is_show=1";
        $list = M()->query($sql);
        foreach ($list as $k=>$v){
            $params = null;
            if(!empty($v['headerstr'])){
                $list[$k]['headerstr'] = explode(',',$v['headerstr']);
            }
            for($i = 1;$i<=$v['needparam'];$i++){
                $params[] = $v['param'.$i];
            }
            $list[$k]['params'] = $params;
        }

        return $list;
	}

    public function updateSendStatus($pid,$sid, $key, $status,$params)
    {
        if($status == 0){
            M('shop_send')->where(array('shop_id'=>$sid,'keystr'=>$key))->delete();
        }elseif($status == 1){
            $data['shop_id'] = $sid;
            $data['partner_id'] = $pid;
            $data['keystr'] = $key;
            for ($i =1;$i<=count($params);$i++){
                $data['param'.$i] = $params[$i-1];
            }
            $data['status'] = 1;
            $data['sort'] = 1;
            M('shop_send')->add($data);
        }
	}

}