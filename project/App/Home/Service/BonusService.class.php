<?php
namespace Home\Service;
use Base\Service\WechatHelper;
use Home\Model\BonusModel;
class BonusService
{

	public function getUserBonus($uid){
		$model = new BonusModel();
		return $model->getUserBonus($uid);
	}

	public function getActivityBonus($uid){
		$model = new BonusModel();
		return $model->getActivityBonus($uid);
	}


	//检测用户可使用的红包
    public function checkBonus($uid,$partner_id,$shop_id,$total_money=0){
        $where = "b.uid=".$uid;
        $where .= " AND b.partner_id=".$partner_id;
        // $where .= " AND b.shop_id=".$shop_id;
        $where .= " AND b.end_time>".time();
        $where .= " AND status=0";
        $where .= " AND param<=".$total_money;
        $sql = "SELECT b.*,bt.name AS bonus_type FROM sm_bonus b LEFT JOIN sm_bonus_type bt ON b.type=bt.id WHERE ".$where;
		$data = M()->query($sql);        
        return $data;
    }

	/** 发送新用户红包
	 * @param $uid
	 * @param $sid
	 * @param $pid
	 */
	public function sendNewBonus($uid,$sid,$pid)
	{
        \Think\Log::write("发送新用户红包 uid = ".$uid.' shop_id='.$sid);

		$sql = "SELECT a.param1,a.param2,a.param3 FROM `sm_activity_shop` s left join `sm_activity` a on s.`act_id` =a.`id`  WHERE s.status=0 and s.type=".ACTIVE_TYPE_NEWBONUS." and s.`partner_id` =".$pid;
        \Think\Log::write("发送新用户红包sql = ".$sql);
		$info = M()->query($sql);
		if(empty($info)) return;
		$info = $info[0];
		if(!empty($info)){
			$money = $info['param1'];
			$day = $info['param2'];
			$data['partner_id'] = $pid;
			$data['shop_id'] = $sid;
			$data['uid'] = $uid;
			$data['money'] = $money;
			$data['day'] = $day;
            $data['param'] = $info['param3'];
			$data['type'] = BONUS_NEW;
			$id = self::addBonus($data);

			//发送消息模板
			$pser = new PartnerService();
			$pname = $pser->getPartnerName($pid);
			$user = new UserService();
			$openid = $user->getOpenid($uid);
			$data['first'] =  '"'.$pname.'"送您一个新用户红包';
			$data['keyword1'] = '"'.$pname.'"专属红包（1个）';
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
//			$data['remark'] = '您已成功邀请一位朋友关注'.$pname.'，恭喜获得一个'.$money.'元邀请红包～';
			$tpl = $pser->getTplId($pid,TPL_BONUS);
			if(empty($tpl)) return;
			$helper = WechatHelper::get($pid);
			$res = $helper->sendTpl($tpl,$openid,"",$data);
		}

	}

	/**发送邀请红包
	 * @param $uid 新进入系统的用户id
	 * @param $type
	 */
	public function sendInvBonus($uid,$type){
		$invid = M('wechat_user')->where(array('id'=>$uid))->getField('inviter_id');
		if(!empty($invid)){
			$uinfo = M('wechat_user')->where(array('id'=>$invid))->field('partner_id,shop_id')->find();
			$mser = new MarketService();
			$setting = $mser->getSetting($uinfo['partner_id']);
			if($setting['is_open'] == IS_OPEN && $setting['get_type'] == $type){
				$data['partner_id'] = $uinfo['partner_id'];
//				$data['shop_id'] = $uinfo['shop_id'];
				$data['uid'] = $invid;
				$data['sender_id'] = $uid;
				$data['money'] = $setting['money'];
				$data['day'] = $setting['active_time'];
				$data['type'] = BONUS_INV;
				$id = self::addBonus($data);
				//发送模板
				self::sendBonusMsgInviter($invid,$uinfo['partner_id'],$data['day'],$data['money']);
                M('wechat_user')->where(array('id'=>$invid))->setInc('bonus_money',$setting['money']);
			}
		}
	}

	/**数据库中添加红包信息
	 * @param $data
	 */
	public function addBonus($data)
	{
		$data['create_time'] = time();
		if(empty($data['day'])){
			$data['day'] = 7;
		}
		$data['end_time'] = time()+DAY_SECONDS*$data['day'];
		$id = M('bonus')->add($data);
		return $id;
	}

	/** 发送消息模板
	 * @param $uid
	 * @param $pid
	 * @param $lastdate
	 * @param $money
	 */
	public function sendBonusMsgInviter($uid,$pid,$lastdate,$money){
		$pser = new PartnerService();
		$pname = $pser->getPartnerName($pid);
		$user = new UserService();
		$openid = $user->getOpenid($uid);
		$data['first'] =  '"'.$pname.'"送您一个红包';
		$data['keyword1'] = '"'.$pname.'"专属红包（1个）';
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
		$data['remark'] = '您已成功邀请一位朋友关注'.$pname.'，恭喜获得一个'.$money.'元邀请红包～';
		$tpl = $pser->getTplId($pid,TPL_BONUS);
		if(empty($tpl)) return;
		$helper = WechatHelper::get($pid);
//		$url = $helper->getUrl(U('Home/Index/myjf'));
		$res = $helper->sendTpl($tpl,$openid,"",$data);
	}

    public function sendBonus($uid,$pid, $title, $remark,$lastdate,$money)
    {
        \Think\Log::write("发送红包 uid=".$uid);
        $pser = new PartnerService();
        $pname = $pser->getPartnerName($pid);
        $user = new UserService();
        $openid = $user->getOpenid($uid);

        $ndata['partner_id'] = $pid;
//				$data['shop_id'] = $uinfo['shop_id'];
        $ndata['uid'] = $uid;
        $ndata['money'] = $money;
        $ndata['day'] = $lastdate;
        $ndata['type'] = BONUS_ACTIVE;
        $id = self::addBonus($ndata);
        \Think\Log::write("发送红包 成功 id=".$id);
        $data['first'] =  $title;
        $data['keyword1'] = '"'.$pname.'"专属红包（1个）';
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
        $data['remark'] = $remark;
        $tpl = $pser->getTplId($pid,TPL_BONUS);
        if(empty($tpl)) return;
        $helper = WechatHelper::get($pid);
//		$url = $helper->getUrl(U('Home/Index/myjf'));
        $res = $helper->sendTpl($tpl,$openid,"",$data);
	}

}