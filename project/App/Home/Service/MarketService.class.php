<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-23
 * Time: 16:34
 */

namespace Home\Service;
use Home\Model\MarketModel;


class MarketService
{
    public function getSetting($pid)
    {
        $setting = M('market_setting')->where(array('partner_id'=>$pid))->find();
        return $setting;
    }

    public function addMarketUser($partner_id,$uid){
		$model = new MarketModel();
		$res = $model->hasSendInvite($partner_id,$uid);

		if($res){
			$model->updateMarketUser($partner_id,$uid);
		}else{
			$model->addMarketUser($partner_id,$uid);
		}
	}
}