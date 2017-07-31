<?php
namespace Partner\Model;

class SettingModel extends BaseModel{

	public function checkPwd($tel,$pwd){
		$res = M('partner')->where(array('tel'=>$tel,'pwd'=>$pwd))->find();
		return $res;
	}

	public function changePwd($partner_id,$pwd){
		$res = M('partner')->where(array('id'=>$partner_id))->save(array('pwd'=>$pwd));
		return $res;
	}

	public function getPartnerInfo($partner_id){
		$res = M('partner')->where(array('id'=>$partner_id))->find();
		return $res;
	}

	public function getPartnerConf($pid)
	{
		$res = M('partner_conf')->where(array('partner_id'=>$pid))->find();
		return $res;
	}

	public function changePartnerInfo($partner_id,$data){
		$res = M('partner')->where(array('id'=>$partner_id))->save($data);
		return $res;
	}

	public function changeShopInfo($partner_id,$data){
		$res = M('shop_conf')->where(array('partner_id'=>$partner_id))->save($data);
		return $res;
	}

	public function changeSendChargeFixed($partner_id,$shop_id,$charge){
		M('sendcharge')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->delete();
		$charge['partner_id'] = $partner_id;
		$charge['shop_id'] = $shop_id;
		M('sendcharge')->add($charge);
	}

	public function changeSendChargeDynamic($partner_id,$shop_id,$chargelist){
		M('sendcharge')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->delete();
		foreach($chargelist['send_range_start'] as $key=>$charge){
			$data['send_range_start'] = $charge*1000;
			$data['send_range_end'] = $chargelist['send_range_end'][$key]*1000;
			$data['send_price'] = $chargelist['send_money'][$key];
			$data['send_interval'] = $chargelist['send_interval'][$key];
			$data['partner_id'] = $partner_id;
			$data['shop_id'] = $shop_id;
			$data['charge_type'] = 2;
			M('sendcharge')->add($data);
		}
	}

	public function getSendCharge($partner_id,$shop_id){
		$res = M('sendcharge')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->select();
		return $res;
	}

	public function changeTplType($partner_id,$tpl_type){
		$res = M('partner')->where(array('id'=>$partner_id))->save(array('tpl_type'=>$tpl_type));
		return $res;
	}

	public function getTplType($partner_id){
		$type = M('partner')->where(array('id'=>$partner_id))->getField('tpl_type');
		return $type;

	}

	public function fixedTimeOff($partner_id,$shop_id,$data){
		M('shop_conf')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->save(array('fixed_time_status'=>$data['fixed_time_status']));
		M('reserve_conf')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->delete();
	}

	public function fixedTimeOn($partner_id,$shop_id,$reserve_list){
		M('shop_conf')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->save(array('fixed_time_status'=>$reserve_list['fixed_time_status']));
		M('reserve_conf')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->delete();
		$time = time();
		foreach($reserve_list['pre_time'] as $key=>$reserve){
			$data['pre_time'] = formatTime($reserve);
            $data['end_time'] = formatTime($reserve_list['end_time'][$key]);
			$data['buy_time'] = formatTime($reserve_list['buy_time'][$key]);
			$data['partner_id'] = $partner_id;
			$data['shop_id'] = $shop_id;
			$data['create_time'] = $time;
			M('reserve_conf')->add($data);
		}
	}



	public function getReserveList($partner_id,$shop_id){
		$res = M('reserve_conf')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->select();
		return $res;
	}

    public function saveOrUpdatePreindex($data)
    {

	}

    public function getSetting($partner_id, $shop_id)
    {
        $info = M('preindex_setting')->where(array('partner_id'=>$partner_id))->find();
        return $info;
	}

    public function getImg($partner_id, $type, $len)
    {
        $list = M('preindex_img')->where(array('partner_id'=>$partner_id,'type'=>$type))->select();
        $res = array();
        foreach ($list as $k=>$v){
            $res[$v['key']] = $v;
        }
        for($i=1;$i<=$len;$i++){
            if(empty($res[$i])){
                $res[$i] = 0;
            }
        }
        return $res;
	}

	public function checkAccountRepeat($partner_id,$tel){
		$res = M('partner')->where(array('tel'=>$tel))->find();
		if(!empty($res)){
			return 1;
		}else{
			$res = M('partner_admin')->where(array('tel'=>$tel))->find();
			if(!empty($res)){
				return 1;
			}else{
				return 0;
			}
		}
	}

	public function addAccount($partner_id,$data){
		$data1['partner_id'] = $partner_id;
		$data1['tel'] = $data['tel'];
		$data1['pwd'] = $data['pwd'];
		M('partner_admin')->add($data1);
	}

	public function getAccountList($partner_id){
		$res =M('partner_admin')->where(array('partner_id'=>$partner_id))->select();
		return $res;
	}

	public function getAccountById($id){
		$res = M('partner_admin')->where(array('id'=>$id))->find();
		return $res;
	}

	public function editAccount($id,$pwd){
		M('partner_admin')->where(array('id'=>$id))->save(array('pwd'=>$pwd));
	}

	public function deleteAccount($partner_id,$id){
		M('partner_admin')->where(array('partner_id'=>$partner_id,'id'=>$id))->delete();
	}

	public function getMenus($partner_id){
		$menus_list = M('menus')->where(array('parent_id'=>0,'is_root_show'=>0))->order('sort')->select();
		foreach($menus_list as $key=>$parent){
			$child_list = M('menus')->where(array('parent_id'=>$parent['id'],'is_root_show'=>0))->order('sort')->select();
			$menus_list[$key]['child_list'] = $child_list;
		}
		return $menus_list;
	}

	public function addRole($partner_id,$role_name){
		$id = M('partner_admin_type')->add(array('partner_id'=>$partner_id,'name'=>$role_name));
		return $id;
	}

	public function addRoleAccount($data){
		$id = M('partner_admin')->add($data);
		return $id;
	}

	public function addRoleMenus($partner_id,$role_id,$menus){
		foreach($menus as $key=>$menu){
			M('partner_admin_menu')->add(array('partner_id'=>$partner_id,'type_id'=>$role_id,'menu_id'=>$menu));
		}
	}

	public function getAuthorityRoleList($partner_id){
		$sql = "SELECT pat.id,pat.name AS role_name,pa.tel,pa.pwd FROM sm_partner_admin_type pat LEFT JOIN sm_partner_admin pa ON pat.id=pa.type_id WHERE pat.partner_id=".$partner_id;
		$res = M()->query($sql);
		return $res;
	}

	public function getRoleAccountById($partner_id,$role_id){
		$sql = "SELECT pat.id,pat.name AS role_name,pa.tel,pa.pwd FROM sm_partner_admin_type pat LEFT JOIN sm_partner_admin pa ON pat.id=pa.type_id WHERE pat.partner_id=".$partner_id." AND pat.id=".$role_id;
		$account = M()->query($sql);
		return $account[0];		
	}

	public function getRoleMenusById($partner_id,$role_id){
		$menus_list = M('partner_admin_menu')->where(array('partner_id'=>$partner_id,'type_id'=>$role_id))->select();
		return $menus_list;
	}

	public function editRole($role_id,$role_name){
		M('partner_admin_type')->where(array('id'=>$role_id))->save(array('name'=>$role_name));
	}

	public function editRoleAccount($partner_id,$role_id,$pwd){
		M('partner_admin')->where(array('partner_id'=>$partner_id,'type_id'=>$role_id))->save(array('pwd'=>$pwd));
	}

	public function editRoleMenus($partner_id,$role_id,$menus){
		M('partner_admin_menu')->where(array('partner_id'=>$partner_id,'type_id'=>$role_id))->delete();
		foreach($menus as $key=>$menu){
			M('partner_admin_menu')->add(array('partner_id'=>$partner_id,'type_id'=>$role_id,'menu_id'=>$menu));
		}

	}

	public function deleteRoleMenus($partner_id,$role_id){
		M('partner_admin_menu')->where(array('partner_id'=>$partner_id,'type_id'=>$role_id))->delete();
	}

	public function deleteRoleAccount($partner_id,$role_id){
		M('partner_admin')->where(array('partner_id'=>$partner_id,'type_id'=>$role_id))->delete();
	}

	public function deleteRole($role_id){
		M('partner_admin_type')->where(array('id'=>$role_id))->delete();
	}

}