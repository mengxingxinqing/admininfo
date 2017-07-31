<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-16
 * Time: 16:28
 */

namespace Home\Service;


use Home\Model\ActivityModel;

class ActivityService
{
    /**下订单前 获取活动信息
     * @param $pid
     * @param $sid
     * @param $uid
     * @return array
     */
    public function getListBeforeOrder($pid,$sid,$uid,$ext){
        $model = new ActivityModel();
        return $model->getListBeforeOrder($pid,$sid,$uid,$ext);
    }

    public function getListAfterOrder( $oid)
    {
        $model = new ActivityModel();
        return $model->getListAfterOrder( $oid);
    }

    public function getNowActivity($partner_id,$shop_id){
        $res = S("nowActivity_".$partner_id."_".$shop_id);
        if(empty($res)){
            $model = new ActivityModel();
            $res = $model->getNowActivity($partner_id,$shop_id);
            S("nowActivity_".$partner_id."_".$shop_id,$res,TIME_TEN_MINUTE);
        }
        return $res;
    }
}