<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-10-28
 * Time: 16:47
 */

namespace Home\Service;
use Home\Model\GoodsModel;
use Home\Model\TimeGoodsModel;

class TimeGoodsService
{
    public function getTimeList($pid,$sid)
    {
        $mod = new TimeGoodsModel();
        return $mod->getTimeList($pid,$sid);
    }

    public function getDayGoodsList($pid, $sid, $day_id)
    {
        $mod = new TimeGoodsModel();
        return $mod->getDayGoodsList($pid, $sid, $day_id);
    }

    public function loadTime($pid,$sid)
    {
        $mod = new TimeGoodsModel();
        return $mod->loadTime($pid,$sid);
    }

    public function getSendTime($pid,$sid,$uid)
    {
        $mod = new TimeGoodsModel();
        return $mod->getSendTime($pid,$sid,$uid);
    }

    public function loadweekdetail($pid, $sid, $day_id)
    {
        $mod = new TimeGoodsModel();
        return $mod->loadweekdetail($pid,$sid,$day_id);
    }

    public function changeWeekNum($day_id, $time, $gid, $num,$pid,$sid,$uid)
    {
        $mod = new TimeGoodsModel();
        $mod->changeWeekNum($day_id, $time, $gid, $num,$pid,$sid,$uid);
    }

    /** 获取购物车结构体-周餐
     * @param $uid
     * @param $shop_id
     * @param int $remove
     * @return array
     */
    public function getShopCartInfoWeek($uid,$shop_id,$remove=0){
        $model = new TimeGoodsModel();
        $list = $model->loadShopcartWeek($uid,$shop_id,$remove);
        // var_dump($list);die;
        return $list;
    }

    public function loadShopMsg($partner_id,$shop_id)
    {
        $model = new TimeGoodsModel();
        return $model->loadShopMsg($partner_id,$shop_id);
    }


    public function saveOrderWeek($uid,$pid,$shop_id,$partner_id,$inputData){
        $res = self::getShopCartInfoWeek($uid,$shop_id);

        $activityService = new ActivityService();
        $activity_info  = $activityService->getListBeforeOrder($partner_id,$shop_id,$uid);

        $shopService = new ShopService();

        //提前预定优惠
        $day_discount = $shopService->getDayDiscount($partner_id,$shop_id,$res['res']);
        if($day_discount > 0){
            $day_discount_activity['type'] = 'dec';
            $day_discount_activity['id'] = 0;
            $day_discount_activity['name'] = '提前预定优惠'.floatval($day_discount).'元';
            $day_discount_activity['money'] = $day_discount;
            $activity_info[] = $day_discount_activity;
        }


        //特价商品优惠
        $goods_discount_set = $shopService->getGoodsDiscountInfo($partner_id,$shop_id);
        if($goods_discount_set['status'] == 1){
            $goods_discount = $shopService->getGoodsDiscount($partner_id,$shop_id,$uid,$res['res'],$goods_discount_set['goods_discount_num']);
            if(!empty($goods_discount)){

                if($goods_discount_set['share_with_other'] == 0){
                    foreach($activity_info as $key=>$activity){
                        if($activity['type'] == 'dec' && $activity['id'] >0){
                            unset($activity_info[$key]);
                        }
                    }
                }
                
                foreach($goods_discount as $key=>$discount){
                    $goods_discount_activity['type'] = 'dec';
                    $goods_discount_activity['id'] = 0;
                    $goods_discount_activity['name'] = $discount['discount_msg'];
                    $goods_discount_activity['money'] = $discount['discount'];
                    $goods_discount_activity['buy_date'] = $discount['buy_date'];
                    $activity_info[] = $goods_discount_activity;
                }
            }
        }

        $orderModel = new TimeGoodsModel();
        $order_id = $orderModel->addOrderWeek($uid,$pid,$shop_id,$res,$inputData,$activity_info);

        $goodsModel = new GoodsModel();
        $goodsModel->clearall($uid,$shop_id);
        return $order_id;
    }
}