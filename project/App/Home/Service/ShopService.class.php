<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/10 0010
 * Time: 下午 3:32
 */

namespace Home\Service;


use Home\Model\GoodsModel;
use Home\Model\ShopModel;

class ShopService
{




    public function loadnotice($sid)
    {
        $res = M('shop_conf')->where(array('shop_id'=>$sid))->getField('notice');
        return $res;
    }

    /** 获取店铺开关门信息，以及用户的消息
     *  暂不外送，关门，正常
     * @param $select
     * @param $cat
     */
    public function getShopMsg($select,$cat){
        $shopModel = new ShopModel();
        if($select == 1){ //便利店
            $res = $shopModel->getStoreMsg($cat);
        }else{
            $res = $shopModel->getRiceMsg($cat);
        }
        return $res;
    }

    public function isOpenWxpay($shop_id){
        $model = new ShopModel();
        return $model->isOpenWxpay($shop_id);
    }


    public function getShopById($shop_id){
        $model = new ShopModel();
        return $model->getShopInfo($shop_id);
    }

    public function getSendTime($shop_id,$interval){
        if(empty($interval)){
            return '';
        }
        $model = new ShopModel();
        // $interval = $model->getSendInterval($shop_id);
        //interval单位为分钟
        $time = time();
        $send_time = $time + $interval*60;
        $send_time = date("H:i",$send_time);
        return $send_time;
    }

    public function getShopInfo($shop_id){
        $key = "getShopInfo_".$shop_id;
        $res = S($key);
        $res = '';
        if(empty($res)){
            $model = new ShopModel();
            $res = $model->getShopInfo($shop_id);
            S($key,$res,TIME_FIVE_MINUTE);
        }
        return $res;
    }

    public function getShareBonus($pid){
        $model = new ShopModel();
        $res = $model->getShareBonus($pid);
        return $res;
    }

    //店铺师傅开启线下支付
    public function isSetCashPay($shop_id){
        $model = new ShopModel();
        $pay_type = $model->isSetCashPay($shop_id);
        $arr = explode(",",$pay_type);
        foreach($arr as $key=>$value){
            if($value == 2){
                return 1;
            }
        }
        return 0;
    }

    public function openshop($sid, $status)
    {
        M('shop')->where(array('id'=>$sid))->save(array('is_close'=>$status));
    }

    public function getOpenshop($sid)
    {
        $res = M('shop')->where(array('id'=>$sid))->getField('is_close');
        return $res;
    }

    public function nosend($sid, $status)
    {
        M('shop')->where(array('id'=>$sid))->save(array('no_send'=>$status));
    }

    public function getNosend($sid)
    {
       return M('shop')->where(array('id'=>$sid))->getField('no_send');
    }

    //获得店铺证件列表
    public function getCertsList($partner_id,$shop_id){
        $model = new ShopModel();
        $res = $model->getCertsList($partner_id,$shop_id);
        return $res;
    }

    public function isSetCerts($shop_id){
        $model= new ShopModel();
        $res = $model->isSetCerts($shop_id);
        return $res;
    }

    public function getBuildingList($shop_id){
        $model = new ShopModel();
        $res = $model->getBuildingList($shop_id);
        return $res;
    }

    public function getBuildingById($building_id){
        $model = new ShopModel();
        $res = $model->getBuildingById($building_id);
        return $res;
    }

    //字体楼宇列表
    public function getPickBuilding($shop_id){
        $model = new ShopModel();
        $res = $model->getPickBuilding($shop_id);
        return $res;
    }


    public function checkPick($partner_id,$shop_id){
        $model = new ShopModel();
        $res = $model->getMaxPickTime($shop_id);
//        $now_time = date('H:i',time()+60*20);
        $now_time = date('H:i',time());
        $reserve = $model->checkReserve($partner_id);
        if($res >= $now_time || $reserve == 1){
            $can_pick = 1;
        }else{
            $can_pick = 0;
        }
        return $can_pick;
    }

    public function getPreIndexInfo($partner_id, $shop_id)
    {
        $model = new ShopModel();
        $res = $model->getPreIndexSetting($partner_id);
        return $res;
    }

    public function getDefaultPickList($partner_id,$shop_id){
        $pick_type = self::getShopPickType($partner_id,$shop_id);
        $model = new ShopModel();
        $res = $model->getDefaultPickList($partner_id,$shop_id,$pick_type);
        return $res;
    }

    //获得门店自提类型，单点自提or多点自提
    public function getShopPickType($partner_id,$shop_id){
        $model = new ShopModel();
        $res = $model->getShopPickType($partner_id,$shop_id);
        return $res;
    }

    public function searchPick($partner_id,$shop_id,$pick){
        $pick_type = self::getShopPickType($partner_id,$shop_id);
        $model = new ShopModel();
        $res = $model->searchPick($partner_id,$shop_id,$pick,$pick_type);
        return $res;
    }

    //通过用户定位获取自提点
    public function getPicklistByLocation($partner_id,$shop_id,$user_lat,$user_lng){
        // $user_lat = 0;
        if($user_lat == 0 || $user_lng == 0){
            $res = self::getDefaultPickList($partner_id,$shop_id);
        }else{
            $model = new ShopModel();

            //首先获取用户和地图模板位置
            $tmp_list = $model->getTmpLoationList();
            foreach($tmp_list as $key=>$tmp){
                $dis = getDistance($user_lng,$user_lat,$tmp['lng'],$tmp['lat']);
                //距离做key值存数组
                $dis_arr[$dis] = $tmp;
            }

            //距离升序排序
            ksort($dis_arr);
            // $a = reset($dis_arr);
            // var_dump($a);die;
            $pick_list = array();
            foreach($dis_arr as $key=>$tmp){
                $pick_list = $model->getPicklistByTmpId($tmp['id']);
                if(!empty($pick_list)){
                    break;  
                }
            }

            //用户和具体自提点位置
            if(empty($pick_list)){
                $res = self::getDefaultPickList($partner_id,$shop_id);
            }else{
                foreach($pick_list as $key=>$pick){
                    $user_pick_dis = getDistance($user_lng,$user_lat,$pick['lng'],$pick['lat']);
                    $user_pick_dis_arr[$user_pick_dis] = $pick;
                }
                ksort($user_pick_dis_arr);
                $res = $user_pick_dis_arr; 
            }
        }
        return $res;
    }

    public function getDayDiscount($partner_id,$shop_id,$res){
        $today = strtotime('today');
        krsort($res);
        $max_day = key($res);
        $day = ($max_day - $today)/86400;
        if($day > 0){
            $model = new ShopModel();
            $discount = $model->getDayDiscount($partner_id,$shop_id,$day);
        }else{
            $discount = 0.00;
        }
        return $discount;
    }

    public function getGoodsDiscountInfo($partner_id,$shop_id){
        $model = new ShopModel();
        $res = $model->getGoodsDiscountInfo($partner_id,$shop_id);
        if(empty($res)){
            $res['status'] = 0;
            $res['goods_discount_num'] = 0;
        }
        return $res;
    }

    public function getGoodsDiscount($partner_id,$shop_id,$uid,$res,$goods_discount_num){
        $arr = array();
        $model = new ShopModel();
        foreach($res as $key=>$day){
            $buy_date = date('Y-m-d',$day['day_info']['buy_time']);
            foreach($day['goods_info'] as $k=>$goods){
                $discount = $model->getGoodsDiscount($partner_id,$shop_id,$uid,$goods['goods_id'],$buy_date,$goods_discount_num);
                if($discount){
                    $arr[] = $discount;
                }
            }
        }
        return $arr;
    }

    public function isExistGoodsDiscount($goods_info){
        $flag = false;
        foreach ($goods_info as $key => $goods) {
            $original_price = M('shop_goods')->where(array('id'=>$goods['goods_id']))->getField('price');
            if($goods['price'] < $original_price){
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    public function getNearShop($pid, $lat, $lng)
    {
        $model = new ShopModel();
        return $model->getNearShop($pid,$lat,$lng);
    }


}