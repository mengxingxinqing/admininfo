<?php
namespace Partner\Service;
use Partner\Model\PartnerModel;
use Partner\Model\SettingModel;

class SettingService extends BaseService{
	public function changePwd($oldtel,$oldpwd,$newpwd,$repwd){
		$model = new SettingModel();
		if($newpwd != $repwd){
			$res['status'] = 'error';
			$res['msg'] = '确认密码与新密码不一致';
		}else{
			$info = $model->checkPwd($oldtel,$oldpwd);
			if($info){
				$partner_id = $info['id'];
				$model->changePwd($partner_id,$newpwd);
				$res['status'] = 'success';
				$res['msg'] = '密码修改成功';
			}else{
				$res['status'] = 'error';
				$res['msg'] = '账号密码验证失败';
			}
		}
		return $res;
	}

	public function getPartnerInfo($partner_id){
		$model = new SettingModel();
		$res= $model->getPartnerInfo($partner_id);
		$res['conf'] = $model->getPartnerConf($partner_id);
		return $res;
	}

	public function changeBaseInfo($partner_id,$data){
		//partner
		$data1['name'] = $data['name'];
		if(isset($data['logo'])){
			$data1['logo'] = $data['logo'];
		}

        if(isset($data['select_img'])){
            $data1['select_img'] = $data['select_img'];
        }
		// $data1['sendcharge_type'] = $data['sendcharge_type'];
		$data1['admin_uid'] = $data['admin_uid'];

		foreach($data['pay_type'] as $key=>$value){
			$pay_type .= $value.',';
		}
		$pay_type = substr($pay_type, 0,-1);
		$data1['pay_type'] = $pay_type;
		$data1['is_order_auto'] = $data['order_auto'];

		$model = new SettingModel();
		$model->changePartnerInfo($partner_id,$data1);

		/*//sendcharge
		if($data['sendcharge_type'] == 1){
			//固定收费
			$charge['charge_type'] = 1;
			$charge['send_price'] = $data['send_money_fixed'];
			$charge['send_interval'] = $data['send_interval_fixed'];
			$model->changeSendChargeFixed($partner_id,$charge);

		}else{
			//距离收费
			$model->changeSendChargeDynamic($partner_id,$data);
		}


		//reserve 预定
		$model = new SettingModel();

		if($data['reserve_day'] == 0){
			$reserve_status = 0;
		}else{
			$reserve_status = 1;
		}
		if($data['fixed_time_status'] == 0){
			//关闭固定时间配送
			$model->fixedTimeOff($partner_id,$reserve_status,$data);
		}else{
			//打开固定时间配送
			$model->fixedTimeOn($partner_id,$reserve_status,$data);
		}*/

		//shop
		$data2['pay_type'] = $pay_type;
		$data2['is_order_auto'] = $data['order_auto']; 
		$model->changeShopInfo($partner_id,$data2);

		$pmodel = new PartnerModel();
		$pmodel->openPhone($partner_id,$data['open_phone']);
	}

	public function getSendCharge($partner_id,$shop_id){
		$model = new SettingModel();
		$res = $model->getSendCharge($partner_id,$shop_id);
		foreach($res as $key=>$value){
			$res[$key]['send_range_start'] = $value['send_range_start']/1000;
			$res[$key]['send_range_end'] = $value['send_range_end']/1000;
		}
		return $res;
	}

	public function getMenus($partner_id){
		$model = new SettingModel();
		$list = $model->getMenus($partner_id);
		return $list;
	}

	public function addAuthority($partner_id,$data){
		$model = new SettingModel();
		$role_name = $data['role_name'];
		$role_id = $model->addRole($partner_id,$role_name);
		$account['partner_id'] = $partner_id;
		$account['type_id'] = $role_id;
		$account['tel'] = $data['tel'];
		$account['pwd'] = $data['pwd'];
		$account_id = $model->addRoleAccount($account);
		$menus = $data['menus_id'];
		$model->addRoleMenus($partner_id,$role_id,$menus);
	}

	public function getAuthorityRoleById($partner_id,$role_id){
		$model = new SettingModel();
		$account = $model->getRoleAccountById($partner_id,$role_id);
		$menus = $model->getRoleMenusById($partner_id,$role_id);
		$res['account'] = $account;
		$res['selected_menus'] = $menus;
		return $res;
	}

	public function editAuthority($partner_id,$data){
		$model = new SettingModel();
		$role_id = $data['role_id'];
		$role_name = $data['role_name'];
		$model->editRole($data['role_id'],$data['role_name']);
		$model->editRoleAccount($partner_id,$data['role_id'],$data['pwd']);

		$menus = $data['menus_id'];
		$model->editRoleMenus($partner_id,$role_id,$menus);
	}

	public function deleteAuthority($partner_id,$role_id){	
		$model = new SettingModel();
		$model->deleteRoleMenus($partner_id,$role_id);
		$model->deleteRoleAccount($partner_id,$role_id);
		$model->deleteRole($role_id);
	}
}