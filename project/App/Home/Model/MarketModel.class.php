<?php

namespace Home\Model;


class MarketModel
{

    //判断用户是否发过邀请
    public function hasSendInvite($partner_id,$uid){
        $count = M('market_user')->where(array('partner_id'=>$partner_id,'uid'=>$uid))->count();
        return $count == 0 ? 0 : 1;
    }

    public function addMarketUser($partner_id,$uid){
        $data['partner_id'] = $partner_id;
        $data['uid'] = $uid;
        $data['create_time'] = time();
        $data['send_times'] = 1;
        M('market_user')->add($data);
    }

    public function updateMarketUser($partner_id,$uid){
        M('market_user')->where(array('partner_id'=>$partner_id,'uid'=>$uid))->setInc('send_times');
    }

}