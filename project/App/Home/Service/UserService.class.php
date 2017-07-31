<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/14 0014
 * Time: 下午 4:35
 */

namespace Home\Service;


use Base\Service\WechatHelper;
use Home\Model\GoodsModel;
use Home\Model\ShopModel;
use Home\Model\UserinfoModel;

class UserService
{
    /** 改变购物车数据
     * @param $uid
     * @param $shop_id
     * @param $goods_id
     * @param $num
     */
    public function changeNum($uid,$shop_id,$goods_id,$num)
    {
        $shopService = new ShopService();
        $partner_id = session('partner_id');
        $goods_discount_set = $shopService->getGoodsDiscountInfo($partner_id,$shop_id);
        $goodsModel = new GoodsModel();
        $goodsModel->changeNum($goods_id,$num,$uid,$shop_id,$goods_discount_set);
    }

    /** 获取用户购物车统计数据
     * @param $uid
     * @param $shop_id
     * @return mixed
     */
    public function countShopcart($uid,$shop_id){
        $goodsModel = new GoodsModel();
        return $goodsModel->countShopcart($uid,$shop_id);
    }



    /** 获取购物车结构体
     * @param $uid
     * @param $shop_id
     * @param int $remove
     * @return array
     */
    public function getShopCartInfo($uid,$shop_id,$remove=0){
        $model = new GoodsModel();
       if($remove == 1){
           $model->filterShopCart($uid,$shop_id);
       }
        $list = $model->loadShopcart($uid,$shop_id,$remove);
        return $list;
    }
    
    public function clearUnFullGoods($uid,$shop_id){
        $model = new GoodsModel();
        $model->clearUnFullGoods($uid,$shop_id);
    }

    public function clearall($uid,$shop_id){
        $model = new GoodsModel();
        $model->clearall($uid,$shop_id);
    }

    public function shopcartEmpty($uid,$shop_id){
        $model = new GoodsModel();
        return $model->shopcartEmpty($uid,$shop_id);
    }

    public function updateUser(){

    }

    public function getAddress($uid,$shop_id){
        $model = new UserinfoModel();
        //初始化用户自提地址
        $num = M('pick_building')->where(['shop_id'=>$shop_id])->count();
        \Think\Log::write("pick num=".$num);
        if($num == 1){
            $pick = M('pick_building')->where(['shop_id'=>$shop_id])->find();
            $data['pick_name'] = $pick['address'];
            $data['pick_id'] = $pick['id'];
            \Think\Log::write("pick info=".json_encode($pick));
            self::setPickInfo($uid,$pick['partner_id'],$data);
        }
        return $model->getUserAddress($uid,$shop_id);
    }

    public function setAddress($uid,$partner_id,$data){
        $data = array(
            'name'=>$data['name'],
            'tel'=>$data['tel'],
            'address'=>$data['address'],
            'detail_address'=>$data['detail_address'],
            'lat'=>$data['lat'],
            'lng'=>$data['lng']
        );

        $address_info = M('user_address')->where(array('partner_id'=>$partner_id,'uid'=>$uid))->find();
        if(empty($address_info)){
            $data['uid']= $uid;
            $data['partner_id'] = $partner_id;
            $data['create_time'] = time();
            M('user_address')->add($data);
        }else{
            $where['uid']= $uid;
            $where['partner_id'] = $partner_id;
            M('user_address')->where($where)->save($data);
        }
        \Think\Log::write("sql =".M()->getLastSql()."  error=".M()->getDbError());
    }

    public function setPickInfo($uid,$partner_id,$data){
        $data = array(
            'name'=>$data['name'],
            'tel'=>$data['tel'],
            'pick_address'=>$data['pick_name'],
            'pick_id'=>$data['pick_id']
        );
        if(empty($data['name'])){
            unset($data['name']);
        }
        if(empty($data['tel'])){
            unset($data['tel']);
        }

        $address_info = M('user_address')->where(array('partner_id'=>$partner_id,'uid'=>$uid))->find();
        if(empty($address_info)){
            $data['uid']= $uid;
            $data['partner_id'] = $partner_id;
            $data['create_time'] = time();
            M('user_address')->add($data);
            \Think\Log::write("set pick address"." ".M()->getLastSql());
        }else{
            $where['uid']= $uid;
            $where['partner_id'] = $partner_id;
            M('user_address')->where($where)->save($data);
            \Think\Log::write("set pick address"." ".M()->getLastSql());
        }
    }

    public function getUserPartnerId($uid){
        $model = new UserinfoModel();
        return $model->getUserPartnerId($uid);
    }

    public function getAllPartnerId($uid){
        $model = new UserinfoModel();
        return $model->getAllPartnerId($uid);
    }

    public function getShop($uid){
        $model = new UserinfoModel();
        return $model->getShop($uid);
    }

    public function getUseInfoById($uid){
//        $key = "getUseInfoById_".$uid;
//        $res = S($key);
//        if(empty($res)){
            $model = new UserinfoModel();
            $res = $model->getById($uid);
//            S($key,$res,TIME_FIVE_MINUTE);
//        }
        return $res;
    }


    public function getPersonInfo($uid)
    {
        $data['money'] = M('user_info')->where(array('uid'=>$uid))->getField('account_money');
        $data['bonus'] = M('bonus')->where(array('uid'=>$uid))->count();
        $data['order'] = self::getUserOrderNum($uid);
        $data['score'] = 100;
        return $data;
    }

    public function getUserOrderNum($uid)
    {

        $num1 = M('order')->where(array('uid'=>$uid))->count();
        $key = "getUserOldOrderNum_".$uid;
        $num2 = S($key);
        if(empty($num2) && $num2 !== '0'){
            $num2 = M('order_bak')->where(array('uid'=>$uid))->count();
            S($key,$num2,TIME_HALF_HOUR);
        }
        return (int)$num1+(int)$num2;
    }


    public function addUser($weObj,$inviter,$json){
        $model = new UserinfoModel();

        if(empty($json)){
            $json = $weObj->getOauthAccessToken();
        }
        $user_info = $weObj->getOauthUserinfo($json['access_token'],$json['openid']);
        if(empty($user_info)){
            return null;
        }else{

            $model->addUser($weObj,$inviter,$user_info);

        }

        
    }

    /** 根据openid  获取用户的id
     * @param $openid
     * @return mixed
     */
    public function getUid($openid){
        $mod = new UserinfoModel();
        return $mod->getUid($openid);
    }

    /** 根据uid 获取openid
     * @param $uid
     * @return mixed
     */
    public function getOpenid($uid){
        $mod = new UserinfoModel();
        $openid = $mod->getOpenid($uid);
        return $openid;
    }

    /** 根据uid 获取用户的shopid
     * @param $uid
     * @return mixed
     */
    public function getShopId($uid)
    {
        $mod = new UserinfoModel();
        return $mod->getShopId($uid);
    }

    /**获取用户的个人二维码
     * @param $pid
     * @param $uid
     * @return mixed
     */
    public function getQrcode($pid,$uid){
        $mod = new UserinfoModel();
        return $mod->getQrcode($pid,$uid);
    }


    /**获取用户的头像
     * @param $pid
     * @param $uid
     * @return mixed
     */
    public function getUserHead($pid,$uid){
        $mod = new UserinfoModel();
        return $mod->getUserHead($pid,$uid);
    }

    /** 用户切换店铺
     * @param $pid
     * @param $uid
     * @param $sid
     */
    public function selectShop($pid,$uid,$sid){
        \Think\Log::write("selectShop pid".$pid.' uid='.$uid.' sid='.$sid);
        $mod = new UserinfoModel();
        $mod->selectShop($pid,$uid,$sid);
    }

    /** 发送邀请用户红包
     * @param $uid 新关注的用户id
     */
    public function sendInviteBonus($uid)
    {
        $invid = M("wechat_user")->where(array('id'=>$uid))->getField('inviter_id');
        
    }

    public function setVisit($uid){
        M('user_info')->where(array('uid'=>$uid))->setInc('visit_index_num');
    }

    public function userAsk($uid){
        $sql = "select * from sm_wechat_ask where uid=$uid and parent_id=0 and type in ('text','image') order by id desc limit 10";
        $list = M()->query($sql);
        $list = array_reverse($list);
        for($i=0;$i<count($list);$i++){
            if($list[$i]['is_answer'] == 0){
                $list[$i]['answer'] = M("wechat_ask")->where(array('parent_id'=>$list[$i]['id']))->select();
            }
        }

        return $list;
    }

    //获取流失用户的信息，包括最近一次订单
    public function getLoseUserInfo($uid){
        $model = new UserinfoModel();
        $res = $model->getLoseUserInfo($uid);
        return $res;
    }


    public function getUserRechargeSet($partner_id){
        $model = new UserinfoModel();
        $res = $model->getUserRechargeSet($partner_id);
        foreach($res as $key=>$value){
            $res[$key]['money'] = floatval($value['money']);
            $res[$key]['giveaway_money'] = floatval($value['giveaway_money']);
        }
        return $res;
    }

    public function getUserRechargeSetById($recharge_id){
        $model = new UserinfoModel();
        $res = $model->getUserRechargeSetById($recharge_id);
        return $res;
    }

    public function getUserRechargeSetByMoney($partner_id,$money){
        $model = new UserinfoModel();
        $res = $model->getUserRechargeSetByMoney($partner_id,$money);
        return $res;
    }

    public function addUserRecharge($partner_id,$shop_id,$user_id,$order_num,$recharge_info){
        $model = new UserinfoModel();
        $res = $model->addUserRecharge($partner_id,$shop_id,$user_id,$order_num,$recharge_info);
        return $res;
    }

    public function getUserAccountMoney($uid){
        $model = new UserinfoModel();
        $res = $model->getUserAccountMoney($uid);
        return $res;
    }

    public function getUserRechargeDetail($uid,$type,$page){
        $model = new UserinfoModel();
        $res = $model->getUserRechargeDetail($uid,$type,$page);
        foreach($res as $key=>$value){
            $res[$key]['create_time'] = date('Y-m-d H:i',$value['create_time']);
            if($type == 1){
                $res[$key]['type_str'] = '充值';
            }else{
                if($value['type'] == 1 || $value['type'] == 3 || $value['type'] == 4){
                    $res[$key]['type_str'] = '支出';
                }else{
                    $res[$key]['type_str'] = '退款';
                }
                
            }
        }
        return $res;
    }

    //余额支付扣费
    public function rechargeDec($order_id){
        $model = new UserinfoModel($order_id);
        $model->rechargeDec($order_id);
    }

    public function pointRechargeDec($pid,$sid,$uid,$point,$money){
        $model = new UserinfoModel();
        return $model->pointRechargeDec($pid,$sid,$uid,$point,$money);
    }

    public function pointPayList($uid,$pid,$limit=5){
        $model = new UserinfoModel();
        return $model->pointPayList($uid,$pid,$limit);
    }

    //获得教练充值二维码信息
    public function getRechargeQrcode($uid){
        $model = new UserinfoModel();
        $res = $model->getRechargeQrcode($uid);
        return $res;
    }


}