<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/7/29 0029
 * Time: 上午 3:04
 */

namespace Home\Model;


use Base\Model\BaseModel;

class ShopModel extends BaseModel
{
    /** 获取店铺类型 只有便利店 或者 便利店餐 或者餐
     * @param $shop
     * @return mixed
     */
    public function shopType($shop){
//        $type = S('shop_type_'.$shop);
//        if(!empty($type)){
//            return $type;
//        }
        $type = M('shop')->where(array('id'=>$shop))->getField('type');
//        S('shop_type_'.$shop,$type,$this->cache6000);
        return $type;
    }


    /** 获取楼宇的便利店信息
     * @param $shop_id
     * @return mixed
     */
    public function getStore($shop_id){
        $store = S('getStore_'.$shop_id);
        if(!empty($store)){
            return $store;
        }
        $store = M('partner_shop')->where(array('shop_id'=>$shop_id,'type'=>1))->find();
        S('getStore_'.$shop_id,$store,$this->cache60);
        return $store;
    }

    /** 获取楼宇名称
     * @param $shop_id
     * @return mixed
     */
    public function getShopName($shop_id){
        $name = S("getShopName_".$shop_id);
        if(empty($name)){
            $name = M('shop')->where(array('id'=>$shop_id))->getField('name');
            S("getShopName_".$shop_id,$name,$this->cache6000);
        }
        return $name;
    }

    /*** 获取店长电话
     * @param $sid
     * @return mixed
     */
    public function getShopTel($sid)
    {
        $tel = S("getShopTel".$sid);
        if(empty($tel)){
            $tel = M('shop')->where(array('id'=>$sid))->getField('tel');
            S("getShopTel".$sid,$tel,$this->cache6000);
        }
        return $tel;
    }

    public function getPartner($shop_id,$partner_id){
        $list = M('partner_shop')->where(array('shop_id'=>$shop_id,'partner_id'=>$partner_id))->find();
        return $list;
    }

    /** 获取小店早午餐晚餐时间
     * @param $shop_id
     * @return mixed
     */
    public function getShopTimeSetting($shop_id){
//        $timeSetting = S('getShopTimeSetting_'.$shop_id);
//        if(!empty($timeSetting)){
//            return $timeSetting;
//        }
        $timeSetting = M('shop_time')->where(array('shop_id'=>$shop_id))->find();
        if(empty($timeSetting)){
            return null;
        }
//        S("getShopTimeSetting_".$shop_id,$timeSetting,$this->cache6000);
        return $timeSetting;
    }

    /**根据当前类别获取当前便利店的 是否关店等状态
     * 返回 关店，不外送，以及对应的消息文本
     * @param $cat
     * @return array
     */
    public function getStoreMsg($cat){
        $category = M('category')->find($cat);
        $shop = M('partner_shop')->where(array('shop_id'=>$category['shop_id'],'partner_id'=>$category['partner_id']))->find();
        $partner = M('partner')->where(array('id'=>$category['partner_id']))->find();
        $res = array();
        $res['type'] = 0;
        //便利店的配送时间默认30
        $res['max_time'] = intval($shop['maxtime']);
        if (empty($res['maxtime'])) {
            $res['max_time'] = 30;
        }

        $res['shop_name'] = $partner['shop_name'];
        $res['base_price'] = intval($shop['base_price']);
        if (empty($res['base_price'])) {
            $res['base_price'] = intval(0);
        }

        
        if($shop['no_send'] == 1){
            $res['type'] = 1;
            $res['shop_name'] = $partner['shop_name'];
            $res['msg'] = "暂不外送";
            $res['show_msg'] = $shop['show_msg'];
            $res['tel'] = $partner['tel'];
            $res['address'] = $partner['shop_address'];
        }
        $shop_status = self::checkTime($shop['start_time'],$shop['end_time']);
        if($shop['is_close'] == 1  || $shop_status== 0){
            if($shop_status == 0){
                $res['msg'] = "尚未开门";
            }else{
                $res['msg'] = "已打烊";
            }
            $res['type'] = 2;
            $res['show_msg'] = $shop['show_msg'];
            $res['shop_name'] = $partner['shop_name'];
            $res['tel'] = $partner['tel'];
            $res['address'] = $partner['shop_address'];
        }
        
        return $res;
    }

    public function getRiceMsg($cat){
        $shop = M('partner_shop')->where(array('partner_id'=>$cat))->find();
        $max_time = M('shop_goods')->where(array('partner_id'=>$cat))->max('max_time');
        $partner = M('partner')->find($shop['partner_id']);
        $res = array();
        $res['type'] = 0;
        $res['max_time'] = intval($shop['maxtime']);
        if (empty($res['maxtime'])) {
            $res['max_time'] = 30;
        }
       
        $res['shop_name'] = $partner['shop_name'];
        $res['base_price'] = intval($shop['base_price']);
        if (empty($res['base_price'])) {
            $res['base_price'] = intval(0);
        }
            //$res['msg'] = "外卖本店".$shop['base_price'].'元起送';
        
        if($shop['no_send'] == 1){
            $res['type'] = 1;
            $res['shop_name'] = $partner['shop_name'];
            $res['msg'] = "暂不外送";
            $res['show_msg'] = $shop['show_msg'];
            $res['tel'] = $partner['tel'];
            $res['address'] = $partner['shop_address'];
        }
        $shop_status = self::checkTime($shop['start_time'],$shop['end_time']);
        if($shop['is_close'] == 1  || $shop_status== 0){
            if($shop_status == 0){
                $res['msg'] = "尚未开门";
            }else{
                $res['msg'] = "已打烊";
            }
            $res['type'] = 2;
            $res['show_msg'] = $shop['show_msg'];
            $res['shop_name'] = $partner['shop_name'];
            $res['tel'] = $partner['tel'];
            $res['address'] = $partner['shop_address'];
        }
        return $res;
    }
    
    public function getshopCate($partner_id,$shop_id){
        return M('Category')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->order('sort asc')->select();
    }





    /** 判断当前时间段
     * @param $shop_id
     * @return int
     */
    public function isInTime($shop_id){
        $timeSetting = self::getShopTimeSetting($shop_id);
        if(empty($timeSetting)){
            return 0;
        }
        $time = date('H:i',time());
        if($time>=$timeSetting['breakfast_start'] && $time<=$timeSetting['breakfast_end']){
            return 1;
        }
        if($time>=$timeSetting['lunch_start'] && $time<=$timeSetting['lunch_end']){
            return 2;
        }
        if($time>=$timeSetting['dinner_start'] && $time<=$timeSetting['dinner_end']){
            return 3;
        }
        return 0;
    }

    public function getPartnerInfo($shop_id,$partner_id){

    }

    public function getBaseInfo($shop_id){
//        $res = S('getBaseInfo_'.$shop_id);
//        if(!empty($res)){
//            return $res;
//        }
        $res = M('shop')->find($shop_id);
//        S('getBaseInfo_'.$shop_id,$shop_id,$this->cache6000);
        return $res;
    }


    public function getShopSetting($sid)
    {
        $key = "getShopSetting_".$sid;
        $res = S($key);
        if(empty($res)){
            $sql = "SELECT s.no_send,s.is_close,sc.* FROM sm_shop s LEFT JOIN sm_shop_conf sc ON s.id=sc.shop_id WHERE s.id=".$sid;
            $info = M()->query($sql);
            $res = $info[0];
            S($key,$res,TIME_HALF_HOUR);
        }
        return $res;
    }

    public function getPartnerHasStore($pid)
    {
        $key = "getPartnerHasStore_".$pid;
        $res = S($key);
        if(empty($res)){
            $res = M('partner_conf')->where(array('partner_id'=>$pid))->getField('has_store');
            S($key,$res,TIME_ONE_DAY);
        }
        return $res;
    }


    public function getPartnerShop($uid){
        $partner_id = M('partner')->where(array('uid'=>$uid))->getField('id');
        if (empty($partner_id)) {
            $partner_id = M('sender')->where(array('uid'=>$uid))->getField('partner_id');
        }
        $sql = "select ps.*,s.address as shop_name from sm_partner_shop ps left join sm_shop s on ps.shop_id=s.id where partner_id=".$partner_id.' and ';
        $data = M()->query($sql);

        return $data;
    }

    public function getGoodsList($partner_id,$shop_id){
        $sql  = 'select sg.id,sg.is_off,sg.price,sg.max_time,g.name from tm_shop_goods sg left join tm_goods g on sg.goods_id=g.id where sg.shop_id='.$shop_id.' and sg.partner_id='.$partner_id.' order by sg.is_off asc,sg.sort asc';
        $data = M()->query($sql);
        return $data;
    }

    public  function getGoodsPageList($partner_id,$shop_id,$p,$cate){
        $limit = self::getPageLimit($p,1);
        $sql  = 'select sg.id,sg.is_off,sg.goods_id,sg.price,sg.max_time,g.name,g.img from tm_shop_goods sg left join tm_category_goods cg on sg.id=cg.goods_id left join  tm_goods g on sg.goods_id=g.id where sg.shop_id='.$shop_id.' and cg.category_id='.$cate.' and sg.partner_id='.$partner_id.' order by sg.is_off asc,sg.sort asc '." $limit";
        $data = M()->query($sql);
        return $data;
    }

    public function setShopGoodsTime($id,$max_time){
        $data['max_time'] = $max_time;
        $res = M('shop_goods')->where(array('id'=>$id))->save($data);
        if ($res) {
            return true;
        }else{
            return false;
        }
    }


    public function setShopGoodsAttr($id,$val){
        $shop_goods = M('shop_goods');
        $goods = $shop_goods->where(array('id'=>$id))->find();
        if ($val == 1) {
            $status['is_off']  =  0;
        }else{
            $status['is_off']  =  1;
        }
        $res = $shop_goods->where(array('goods_id'=>$goods['goods_id'],'partner_id'=>$goods['partner_id']))->save($status);
        if ($res) {
            return true;
        }else{
            return false;
        }
    }

    /** 楼宇是否开启微信支付
     * @param $shop_id
     * @return mixed
     */
    public function isOpenWxpay($shop_id){
        return M('shop')->where(array('id'=>$shop_id))->getField('open_wechatpay');
    }

    public function getGroupId($shop_id){
        return M('wechat_group')->where(array('shop_id'=>$shop_id))->getField('group_id');
    }

    public function getSendInterval($shop_id){
        $interval = M('shop_conf')->where(array('shop_id'=>$shop_id))->getField('send_interval');
        return $interval;
    }

    public function getShopInfo($shop_id){
        $sql = "SELECT s.*,sconf.start_time,sconf.end_time,sconf.base_price,sconf.send_price,sconf.send_interval,sconf.is_order_auto,sconf.pay_type,sconf.bottom_address,sconf.show_bottom_address,sconf.send_range,sconf.is_building_send,sconf.is_pick,sconf.is_godoor,sconf.is_pick_point,p.name AS partner_name,p.tpl_type,pc.is_user_recharge FROM sm_shop s LEFT JOIN sm_shop_conf sconf ON s.id=sconf.shop_id LEFT JOIN sm_partner p ON s.partner_id=p.id LEFT JOIN sm_partner_conf pc ON s.partner_id=pc.partner_id WHERE s.id=".$shop_id;
        $res = M()->query($sql);
        return $res[0];
    }


    public function getShareBonus($pid){
        $money = M('market_setting')->where(array('partner_id'=>$pid,'is_open'=>1))->getField('money');
        return $money;
    }

    //店铺是否开启自动接单
    public function isOrderAuto($shop_id){
        $res = M('shop_conf')->where(array('shop_id'=>$shop_id))->getField('is_order_auto');
        return $res;
    }

    //店铺是否设置线下支付
    public function isSetCashPay($shop_id){
        $res = M('shop_conf')->where(array('shop_id'=>$shop_id))->getField('pay_type');
        return $res;
    }

    //获得店铺证件列表
    public function getCertsList($partner_id,$shop_id){
        $res = M('shop_certs')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->select();
        return $res;
    }

    public function isSetCerts($shop_id){
        $count = M('shop_certs')->where(array('shop_id'=>$shop_id))->count();
        return $count;
    }

    public function shopAdminUid($id)
    {
        $uid = M("shop")->where(array('id'=>$id))->getField('admin_uid');
        return $uid;
    }

    public function getBuildingList($shop_id){
        $res = M('building')->where(array('shop_id'=>$shop_id))->select();
        return $res;
    }

    public function getBuildingById($building_id){
        $res = M('building')->where(array('id'=>$building_id))->find();
        return $res;
    }

    public function getPickBuilding($shop_id){
        $res = M('pick_building')->where(array('shop_id'=>$shop_id))->select();
        return $res;
    }

    public function getMaxPickTime($shop_id){
        $sql = "SELECT MAX(pick_end_time) AS max_time FROM sm_pick_time WHERE shop_id=".$shop_id;
        $res = M()->query($sql);
        return $res[0]['max_time'];
    }

    public function checkReserve($partner_id){
        $res = M('partner_conf')->where(array('partner_id'=>$partner_id))->getField('reserve_status');
        return $res;
    }

    public function getPreIndexSetting($partner_id,$shop_id){
        $res['info'] = M('preindex_setting')->where(array('partner_id'=>$partner_id))->find();
        $imgs = M('preindex_img')->where(array('partner_id'=>$partner_id))->select();
        foreach ($imgs as $v){
            if($v['type'] == 0){
                $res['banner'][] = $v;
            }elseif ($v['type'] == 1){
                $res['small'][] = $v;
            }elseif($v['type'] == 2){
                $res['big'][] = $v;
            }
        }
        return $res;
    }

    public function getShopPickType($partner_id,$shop_id){
        $res = M('shop_conf')->where(array('shop_id'=>$shop_id))->getField('is_pick_point');
        return $res;
    }

    public function getDefaultPickList($partner_id,$shop_id,$pick_type){
        $res = M('pick_building')->where(array('shop_id'=>$shop_id,'is_pick_point'=>$pick_type))->limit(5)->select();
        /*$start = M('pick_time')->where(array('shop_id'=>$shop_id,'pick_point_id'=>0))->min('pick_start_time');
        foreach($res as $key=>$value){

        }*/
        return $res;
    }

    public function searchPick($partner_id,$shop_id,$pick,$pick_type){
        $sql = "SELECT * FROM sm_pick_building WHERE shop_id=".$shop_id." AND is_pick_point=".$pick_type." AND (address like '%".$pick."%' or number like '".$pick."') LIMIT 10";
        $res = M()->query($sql);
        return $res;
    }

    //获得模板坐标列表
    public function getTmpLoationList(){
        $res = M('map_range')->select();
        return $res;
    }

    public function getPicklistByTmpId($map_rang_id){
        $res = M('pick_building')->where(array('map_range_id'=>$map_rang_id))->select();
        return $res;
    }

    public function getDayDiscount($partner_id,$shop_id,$day){
        $discount = M('day_discount')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id,'days'=>$day))->getField('discount_money');
        return $discount;
    }

    //获取每日商品优惠功能是否打开
    public function getGoodsDiscountInfo($partner_id,$shop_id){
        $sql = "SELECT * FROM sm_goods_discount WHERE partner_id=$partner_id AND FIND_IN_SET($shop_id,shop_ids)";
        $res = M()->query($sql);
        return $res[0];
    }

    public function getGoodsDiscount($partner_id,$shop_id,$uid,$goods_id,$buy_date,$goods_discount_num){
        $create_start_time = strtotime('today');
        $create_end_time = $create_start_time+86400;
        $start_time = strtotime($buy_date);
        $end_time = $start_time+86400;
        $sql = "SELECT count(*) AS count FROM sm_order_detail od LEFT JOIN sm_order o ON od.order_id=o.id WHERE o.is_pay=1 AND o.status!=4 AND od.goods_id=".$goods_id." AND o.uid=".$uid." AND o.reserve_time>=".$start_time." AND o.reserve_time<".$end_time." AND o.create_time>=".$create_start_time." AND o.create_time<".$create_end_time;
        $res = M()->query($sql);
        $count = $res[0]['count'];
        if($count < $goods_discount_num){
            //首次
            $goods_info = M('shop_goods')->where(array('id'=>$goods_id))->find();
            if(!empty($goods_info['discount_price'])){
                $discount = $goods_info['price'] - $goods_info['discount_price'];
                $arr['discount'] = $discount;
                $arr['discount_msg'] = $goods_info['name'].'优惠'.$discount."元";
                $arr['buy_date'] = $start_time;
                return $arr;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }

    public function getShopList($pid)
    {
        $key = "getShopList_".$pid;
        $res = S($key);
        if(empty($res)){
            $sql = "SELECT s.*,sc.is_pick_point FROM sm_shop s LEFT JOIN sm_shop_conf sc ON s.id=sc.shop_id WHERE s.partner_id=".$pid." AND s.is_del=0 AND sc.is_pick_point!=1";
            $res = M()->query($sql);
            S($key,$res,TIME_ONE_HOUR);
        }
        return $res;
    }

    public function getNearShop($pid, $lat, $lng)
    {
        $list = self::getShopList($pid);
        for ($k=0; $k <count( $list); $k++) {
            $tmp_dis = getDistance($lng, $lat, $list[$k]['lng'], $list[$k]['lat']);
            $list[$k]['distance_raw']= $tmp_dis;
        }
        $list = bubble_sort($list,'distance_raw','asc');
        $list[0]['shop_num'] = count($list);
        return $list[0];
    }

}