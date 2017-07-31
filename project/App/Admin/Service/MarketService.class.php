<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-08
 * Time: 15:53
 */

namespace Partner\Service;
use Partner\Model\MarketModel;
use Partner\Model\UserModel;
use Partner\Model\BonusModel;


class MarketService extends BaseService
{
	public function stop($pid){
		$market_model = new MarketModel();
		$market_model->stop($pid);
		self::bakMarketLog($pid);
		$market_model->clearMarketUser($pid);
	}

	private function bakMarketLog($pid){
		$market_model = new MarketModel();
		$market_set = $market_model->getMarketSetInfo($pid);
		$data['partner_id'] = $pid;
		$data['start_time'] = $market_set['open_time'];
		$data['end_time'] = $market_set['end_time'];

		$data['send_num'] = $market_model->getSendNum($pid);

		$user_model = new UserModel();
		$data['user_num'] = $user_model->getUserNum($pid);

		$data['get_num'] = $user_model->getInviteUserNumInTime($pid,$data['start_time'],$data['end_time'],2);

		$data['new_user_num'] = $user_model->getInviteUserNumInTime($pid,$data['start_time'],$data['end_time'],1);

		$data['send_bonus_num'] = $data['get_num'];
		$data['send_bonus_money'] = $data['get_num']*$market_set['money'];

		$bmod = new BonusModel();
        $bonus_info = $bmod->getInviteNum($pid,$data['start_time'],$data['end_time']);

		$data['bonus_num'] = $bonus_info['num'];
		$data['bonus_money'] = $bonus_info['total_money'];
		$res = M('market_log')->add($data);
		return $res;
	}

	public function getMarketLogData($partner_id,$id){
		$model = new MarketModel();
		$market_log = $model->getMarketLogData($partner_id,$id);
		$info = $model->getInviteInfo($partner_id,$market_log['start_time'],$market_log['end_time']);
		// var_dump($info);die;
		$tmp = array();
		$tmp['invite_bonus_num'] = 0;
		$tmp['invite_bonus_used_num'] = 0;
		$tmp['invited_str'] = 0;
		foreach($info as $key=>$invite){
			$tmp['invite_bonus_num'] += $invite['invite_bonus_num'];
			$tmp['invite_bonus_used_num'] += $invite['invite_bonus_used_num'];
			$tmp['invited_str'] += $invite['invited_num'];
			$info[$key]['invite_user'] = $invite['nickname'].'/'.$invite['uid'];
			$info[$key]['invite_bonus_info'] = $invite['invite_bonus_num'].'个';
			$info[$key]['invite_bonus_used_info'] = $invite['invite_bonus_used_num'].'个'.'('.$invite['invite_bonus_used_num']*$market_log['bonus_set_money'].'元)';
		}
		$tmp['invite_user'] = count($info).'人';
		$tmp['invited_str'] .= '人';
		$tmp['invite_bonus_info'] = $tmp['invite_bonus_num'].'个';
		$tmp['invite_bonus_used_info'] = $tmp['invite_bonus_used_num'].'个'.'('.$tmp['invite_bonus_used_num']*$market_log['bonus_set_money'].'元)';
		array_unshift($info,$tmp);
		$res['set_info'] = $market_log;
		$res['invite_info'] = $info;
		return $res;
	}
}