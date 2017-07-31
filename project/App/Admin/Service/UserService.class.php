<?php
namespace Partner\Service;

use Base\Service\WechatHelper;
use Partner\Model\UserModel;
use Partner\Model\PartnerModel;

class UserService extends BaseService{
	public function followData($shop_id){
		$model = new UserModel();
		$start_time = strtotime('today');
        $end_time = time();

		$res['leave_count'] = $model->leaveCount($shop_id,$start_time,$end_time);
		$res['scan_count'] = $model->scanCount($shop_id,$start_time,$end_time);
		return $res;
	}

	public function sendBonus($user_id,$money,$param,$day,$bonus_name=''){
		$time = time();
		$end_time = $time+DAY_SECONDS*$day;

		$model = new UserModel();
		$user_info = $model->getUserInfoById($user_id);
		$data['partner_id'] = $user_info['partner_id'];
		$data['shop_id'] = $user_info['shop_id'];
		$data['type'] = 3;
		$data['uid'] = $user_id;
		$data['money'] = $money;
		$data['param'] = $param;
		$data['end_time'] = $end_time;
		$data['create_time'] = $time;
		$data['name'] = $bonus_name;
		$bonus_id = $model->addBonus($data);
		self::sendBonusMsg($user_id,$user_info['partner_id'],$day,$money,$bonus_name);
		return $bonus_id;
	}


	/** 发送红包 消息模板
	 * @param $uid
	 * @param $pid
	 * @param $lastdate
	 * @param $money
	 */
	public function sendBonusMsg($uid,$pid,$lastdate,$money,$bonus_name=''){
		$partner = new PartnerModel();
		$tpl = $partner->getTplId($pid,TPL_BONUS);
		if(empty($tpl)){
			return;
		}
		$partner_info = $partner->getInfo($pid);
		$partner_name = $partner_info['name'];
		$user = new UserModel();
		$user_info = $user->getUserInfoById($uid);
		$openid = $user_info['openid'];
		if(!empty($bonus_name)){
			$data['first'] =  $bonus_name;
		}else{
			$data['first'] =  '"'.$partner_name.'"送您一个红包';
		}
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
		$helper = WechatHelper::get($pid);
//		$url = $helper->getUrl(U('Home/Index/myjf'));
		$res = $helper->sendTpl($tpl,$openid,"",$data);
	}
}