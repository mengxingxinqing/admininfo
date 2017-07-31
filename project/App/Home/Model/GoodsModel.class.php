<?php
namespace Home\Model;
use Base\Model\BaseModel;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/7/26 0026
 * Time: 下午 5:14
 */
class GoodsModel extends BaseModel
{


    /*** 判断一个商品是否可售
     * @param $shop_open 店铺是否正常营业
     * @param $isoff 商品是否下架
     * @param $start_time 商品开始出售时间
     * @param $end_time 商品结束出售时间
     * @param $max_time 多少分钟必达，用来生成消息提示
     * @return array
     */
    public function goodsCanBuy($shop_open,$isoff,$start_time,$end_time,$max_time,$overmsg = "已售完"){
        //\Think\Log::write('shop_open:'.$shop_open.' isoff:'.$isoff.' start_time:'.$start_time.' end_time:'.$end_time." max_time:".$max_time);
        if($shop_open == 0){ //超时 关店的情况
            $res['msg'] = $start_time.'-'.$end_time.'可售';
            $res['can_buy'] = 0;
            return $res;
        }
        if($shop_open == -1){  //手动关店
            $res['can_buy'] = 0;
            $res['msg'] = "暂停售卖";
            return $res;
        }
        if($shop_open == -2){ //暂不外送
            $res['can_buy'] = 0;
            $res['msg'] = "暂不外送";
            return $res;
        }

        if($isoff == 1){
            $res['msg'] = $overmsg;
            $res['can_buy'] = 0;
            return $res;
        }
//        if(self::checkTime($start_time,$end_time) == 0){
//            $res['msg'] = $start_time.'-'.$end_time.'可售';
//            $res['can_buy'] = 0;
//            return $res;
//        }
//        if($max_time != 0){
//            $msg = $max_time."分钟必达";
//        }else{
//            $msg = 0;
//        }
        return array(
            'can_buy'=>1,
            'msg'=>$msg
        );
    }




    /** 修改用户购物车数据
     * @param $goods_id
     * @param $num
     * @param $uid
     * @param $shop_id
     */
    public function changeNum($goods_id,$num,$uid,$shop_id,$goods_discount_set){
        $where['goods_id'] = $goods_id;
        $where['uid'] = $uid;
        $where['shop_id'] = $shop_id;
        $data['num'] = $num;
        $count = M('shopcart')->where($where)->count();
        $data['goods_name'] = self::getGoodsName($goods_id);
        if($count>0){
            if($num <= 0){
                M('shopcart')->where($where)->delete();
            }else{
                $data['create_time'] = time();
                M('shopcart')->where($where)->save($data);
            }
        }else{
            $partner_id = self::getGoodsPartnerId($goods_id);
            $data['goods_id'] = $goods_id;
            $data['price'] = self::getGoodsPrice($goods_id,$goods_discount_set);
            $data['uid'] = $uid;
            $data['shop_id'] = $shop_id;
            $data['create_time'] = time();
            $data['partner_id']= $partner_id;
            M('shopcart')->add($data);
        }
    }

    public function getGoodsName($id){
        $sql = "SELECT name FROM sm_shop_goods WHERE id=".$id;
        $res = M()->query($sql);
        return $res[0]['name'];
    }

    /** 获取商品价格
     * @param $id
     * @return mixed
     */
    public function getGoodsPrice($id,$goods_discount_set){
        $price = M('shop_goods')->where(array('id'=>$id))->getField('price');
        $info = M('shop_goods')->where(array('id'=>$id))->find();
        if(empty($info['discount_price']) || $info['discount_price'] == '0.00' || $goods_discount_set['status'] == 0){
            $price = $info['price'];
        }else{
            $price = $info['discount_price'];
        }
        return $price;
    }

    public function getGoodsPartnerId($id){
        $id = M('shop_goods')->where(array('id'=>$id))->getField('partner_id');
        return $id;
    }

    /** 获取用户的购物车统计数据
     * @param $uid
     * @param $shop_id
     */
    public function countShopcart($uid, $shop_id)
    {
        $sql = "select IFNULL(sum(price*num),0) as total_money,IFNULL(sum(num),0) as total_num from sm_shopcart where uid=".$uid." and shop_id=".$shop_id;
        $res = M()->query($sql);
        return $res[0];
    }

    /**获取购物车中便利店的商品
     * @param $cat
     * @param $shop_id
     * @param $uid
     * @return mixed
     */
    public function getGoodsNum($cat, $shop_id, $uid)
    {
        $sql = "select sc.goods_id,sc.num from sm_shopcart sc left join tm_category_goods cg on cg.goods_id=sc.goods_id where cg.category_id=".$cat.' and sc.uid='.$uid;
        $res = M()->query($sql);
        return $res;
    }

    /** 获取购物车中餐合作商户的商品
     * @param $cat
     * @param $shop
     * @param $uid
     * @return mixed
     */
    public function getRiceNum($cat,$shop,$uid){
        $sql = "select goods_id,num from sm_shopcart where uid=".$uid.' and shop_id='.$shop.' and partner_id='.$cat;
        $res = M()->query($sql);
        return $res;
    }


    /** 重新构造购物车商品结构
     * @param $uid
     * @param $shop_id
     * @return array
     */
    public function loadShopcart($uid,$shop_id,$remove=0){
        //goods_info
        $sql = "SELECT sc.id AS sc_id,sc.goods_id,sc.goods_name,sc.format_id,sc.format_name,sc.attr_str,sc.partner_id,sc.num,sc.price,sc.num*sc.price AS total_money,s.name AS shop_name,s.is_close,s.no_send,sconf.base_price,sconf.start_time AS ps_start,sconf.end_time AS ps_end,sg.is_off,sg.basegoods_id,sg.category_id,(sg.lunchbox_price*sg.lunchbox_num*sc.num) AS lunchbox_money FROM sm_shopcart sc LEFT JOIN sm_shop s ON sc.shop_id=s.id LEFT JOIN sm_shop_conf sconf ON sc.shop_id=sconf.shop_id LEFT JOIN sm_shop_goods sg ON sc.goods_id=sg.id WHERE sc.uid=".$uid." AND sc.shop_id=".$shop_id."";
        $goods_list = M()->query($sql);

        $order_money = 0;
        $order_num = 0;
        $lunchbox_money = 0;
        foreach($goods_list as $key=>$item){
            $order_money = bcadd($order_money, $item['total_money'],2);
            $order_num += $item['num'];
            $shop_open = self::shopIsOpen($item['is_close'],$item['no_send'],$item['ps_start'],$item['ps_end']);
            $canBuyInfo = self::goodsCanBuy($shop_open,$item['is_off'],$item['ps_start'],$item['ps_end'],0);
            $goods_canbuy = $canBuyInfo['can_buy'];

            $goods_list[$key]['can_buy'] = $goods_canbuy;
            $goods_list[$key]['store'] = 999;
            $lunchbox_money += $item['lunchbox_money'];
        }
        $result['goods_info'] = $goods_list;
        
        //shop_info
        $sql = "SELECT s.partner_id,s.name,sconf.base_price FROM sm_shop s LEFT JOIN sm_shop_conf sconf ON s.id=sconf.shop_id WHERE s.id=".$shop_id;
        $shop_res = M()->query($sql);
        $shop_info = $shop_res['0'];
        $need_money = $shop_info['base_price'] - $order_money;
        if($need_money<0) $need_money = 0;
        // $need_money = -1;
        $shop_info['need_money'] = $need_money;
        $result['shop_info'] = $shop_info;

        $result['can_next'] = self::checkCanNext($goods_list,$shop_info);
        $result['total_money'] = $order_money;
        $result['total_num'] = $order_num;
        $result['lunchbox_money'] = $lunchbox_money;
        return $result;
    }

    public function loadGroupShopcart($sid,$uid){
        M('shopcart')->where(array('shop_id'=>$sid,'uid'=>$uid))->select();
    }

    public function clearUnFullGoods($uid,$shop_id){

    }

    public function checkCanNext($goods_list,$shop_info){
        $can_next = 1;
        if($shop_info['need_money'] > 0){
            $can_next = 0;
            return $can_next;
        }
        foreach($goods_list as $item){
            if($item['can_buy'] == 0){
                $can_next = 0;
                break;
            }
        }
        return $can_next;
    }

    /** 过滤掉用户购物车里不可购买的商品
     * @param $uid
     * @param $shop_id
     */
    public function filterShopCart($uid,$shop_id){

        $sql = "SELECT s.is_close,s.no_send,sc.start_time,sc.end_time FROM  sm_shop s LEFT JOIN sm_shop_conf sc ON s.id=sc.shop_id WHERE s.id=".$shop_id;
        $res = M()->query($sql);
        $shop_info = $res[0];
        $shop_open = self::shopIsOpen($shop_info['is_close'],$shop_info['no_send'],$shop_info['start_time'],$shop_info['end_time']);
        if($shop_open !=1){
            M('shopcart')->where(array('uid'=>$uid,'shop_id'=>$shop_id))->delete();
        }else{
            $shop_open = 1;
            $sql = "SELECT sc.id,sg.is_off FROM sm_shopcart sc LEFT JOIN sm_shop_goods sg ON sc.goods_id=sg.id WHERE sc.uid=".$uid." AND sc.shop_id=".$shop_id;
            $list = M()->query($sql);
            $arr = array();

            foreach($list as $item){

                $canBuyInfo = self::goodsCanBuy($shop_open,$item['is_off'],$item['start_time'],$item['end_time'],0);
                if($canBuyInfo['can_buy'] <= 0){
                    $arr[] = $item['id'];
                    continue;
                }
            }
            if(!empty($arr)){
                M('shopcart')->where(array('id'=>array('in',$arr)))->delete();
            }
        }  
    }


    /** 判断店铺是否开门
     * @param $is_close
     * @param $no_send
     * @param $start_time
     * @param $end_time
     * @return int
     */
    public function shopIsOpen($is_close,$no_send,$start_time,$end_time){
        if($is_close == 1) return -1;
        if($no_send == 1) return -2;
        return self::checkTime($start_time,$end_time);
    }


    /** 清空本店购物车
     * @param $uid
     * @param $shop_id
     */
    public function clearall($uid,$shop_id){
        $where['uid'] = $uid;
        $where['shop_id'] = $shop_id;
        M('shopcart')->where($where)->delete();
    }

    public function clearZero($uid){
        $where['uid'] = $uid;
        $where['num'] = 0;
        M('shopcart')->where($where)->delete();
    }

    /** 判断用户购物车是否为空
     * @param $uid
     * @param $shop_id
     */
    public function shopcartEmpty($uid,$shop_id){
        $where['uid'] = $uid;
        $where['shop_id'] = $shop_id;
        $count = M('shopcart')->where($where)->count();
        return $count>0?0:1;
    }


    public function getCategory($shopid){
        $res = M('shop_category')->where(array('shop_id'=>$shopid,'status'=>0,'is_del'=>0,'is_off'=>0))->order('sort asc')->select();
        return $res;
    }

    public function getGoodsList($cat,$shop_id){
        $list = M('shop_goods')->where(array('shop_id'=>$shop_id,'category_id'=>$cat,'status'=>0,'is_del'=>0,'is_display'=>1))->order('sort')->select();
        return $list;
    }

    public function getPreGoodsList($cat, $shop_id)
    {
        $type = $cat==1?0:1;
        $sql = "select sg.*,pg.store as pstore from sm_pre_goods pg left join sm_shop_goods sg on pg.goods_id=sg.id  where pg.shop_id=$shop_id and pg.type=".$type;
        $list = M()->query($sql);
        return $list;
    }

    public function getGroupGoodsList($shop)
    {
        $sql = "select sg.*,pg.price as p_price,pg.num as p_num from sm_groupmeal_goods pg left join sm_shop_goods sg on pg.goods_id=sg.id  where pg.shop_id=$shop ";
        $list = M()->query($sql);
        return $list;
    }

    public function getPreGoodsStatus($shop, $partnerid, $cat)
    {
        $type = $cat==1?0:1;
        $field = 'sg.id,sg.is_off,sg.is_multi,pg.store';
        $sql = "select $field from sm_pre_goods pg left join sm_shop_goods sg on pg.goods_id=sg.id  where pg.shop_id=$shop and pg.type=".$type;
        $list = M()->query($sql);
        return self::processGoodsStatus($shop,$partnerid,$list);
    }


    public function getGoodsStatus($shop,$partnerid,$cat){
        $field = 'id,is_off,is_multi,store';
        $list = M('shop_goods')->where(array('shop_id'=>$shop,'category_id'=>$cat,'is_del'=>0))->field($field)->select();
        return self::processGoodsStatus($shop,$partnerid,$list);
    }

    private function processGoodsStatus($shop,$partnerid,$list){
        $shopModel = new ShopModel();
        $shopInfo = $shopModel->getShopSetting($shop);
        $hasStore = $shopModel->getPartnerHasStore($partnerid);
        if((self::checkTime($shopInfo['start_time'],$shopInfo['end_time']) == 0)){
            $res['status'] = "close";
            return $res;
        }

        if($shopInfo['is_close'] == 1){
            $res['status'] = 'manual_close';
            return $res;
        }

        if($shopInfo['no_send'] == 1){
            $res['status'] = "no_send";
            return $res;
        }

        $res['status'] = "success";
//

        foreach($list as $item){
            $itid = $item['id'];
            $data['id'] = $itid;
            $data['type'] = 1;

            if($item['is_off'] == 1){
                $data['status'] = 'is_off';
            }else{
                $data['status'] = 'success';
                if($item['is_multi'] == 1){
                    $data['status'] = "format";
                }
                if($hasStore && $item['store'] === '0'){
                    $data['status'] = 'no_store';
                }
            }
            $data['store'] = $item['store'];
            $res['data'][] = $data;
        }
        return $res;
    }


    public function getShopcart($uid,$shopid){
        $res['total_num'] = M('shopcart')->where(array('uid'=>$uid,'shop_id'=>$shopid))->sum('num');
        $res['total_money'] = self::sumShopcart($uid,$shopid);
        $list = M('shopcart')->where(array('uid'=>$uid,'shop_id'=>$shopid))->field('goods_id,goods_name,price,num,format_id,format_name,attr_str')->select();
        $res['data'] = $list;
        return $res;
    }

    public function sumShopcart($uid,$shopid){
        $sql = "select sum(price*num) as totalmoney from sm_shopcart where uid=".$uid.' and shop_id='.$shopid;
        $res = M()->query($sql);
        return $res[0]['totalmoney'];
    }

    public function getPartnerList($shop_id){
        $sql = "select ps.partner_id as id,p.shop_name as name,ps.start_time,ps.end_time,ps.is_close,ps.no_send from tm_partner_shop ps
            left join tm_partner p on p.id=ps.partner_id
            where  ps.status=0 and ps.shop_id=$shop_id and ps.type=2 and p.is_self!=1 and p.status=0 order by ps.is_close asc,ps.sort desc";
        $list = M()->query($sql);
        return $list;
    }

    public function clearUserShopcart(){
        $sql = "delete from sm_shopcart";
        M()->query($sql);
    }

    public function checkTime($start_time,$end_time){
        if(empty($start_time)){
            return 1;
        }
        $time = date('H:i');

        if($end_time>$start_time){//自然日
            if($time>=$start_time && $time<=$end_time){
                return 1;
            }
        }else{ //非自然日
            if(($time<="23:59" && $time>=$start_time) || $time<=$end_time){
                return 1;
            }
        }
        return 0;
    }

    public function offGoods($sid,$gid,$is_off){
        return M('shop_goods')->where(array('id'=>$gid,'shop_id'=>$sid))->save(array('is_off'=>$is_off));
    }

    public function getAllGoodsList($cat,$shop_id){
        $list = M('shop_goods')->where(array('shop_id'=>$shop_id,'category_id'=>$cat,'status'=>0,'is_del'=>0))->select();
        // $list = M()->query($sql);
        return $list;
    }

    /** 根据总分类id 获取在各店的子分类id
     * @param $base_id
     * @param $shop_id
     * @return mixed
     */
    public function getCatByBaseCat($base_id, $shop_id)
    {
        $key = 'getCatByBaseCat_'.$base_id.'_'.$shop_id;
        $cat = S($key);
        if(empty($cat)){
            $cat = M('shop_category')->where(array('shop_id'=>$shop_id,'base_id'=>$base_id))->getField('id');
            S($key,$cat,TIME_HALF_HOUR);
        }
        return $cat;
    }


    /** 增加库存
     * @param $id
     * @param $num
     */
    public function incGoods($id, $num)
    {
        $store = M('shop_goods')->where(array('id'=>$id))->getField('store');
        if($store == null || $store == '' || empty($id)){
            return ;
        }
        M('shop_goods')->where(array('id'=>$id))->setInc('store',$num);
    }

    /** 减少库存
     * @param $id
     * @param $num
     */
    public function decGoods($id, $num)
    {
        $store = M('shop_goods')->where(array('id'=>$id))->getField('store');
        if($store == null || $store == ''|| empty($id)){
            return ;
        }
        M('shop_goods')->where(array('id'=>$id))->setDec('store',$num);
    }




/** 根据购物车 检查库存
     * @param $uid
     * @return mixed
     */
    public function checkStore($uid,$pid,$sid)
    {
        $ser = new \Shop\Service\GoodsService();
        $hasStore = $ser->partnerHasStore($pid,$sid);
        if(empty($hasStore)){
            return null;
        }
        $sql = "select s.id,s.goods_name,s.num,g.store from sm_shopcart s LEFT JOIN sm_shop_goods g on s.goods_id=g.id where s.uid=$uid and s.num>g.store and g.store is not NULL;";
        $res = M()->query($sql);
        return $res;
    }

    /** 将购物车中库存不足的删掉
     * @param $res
     */
    public function removeShopcartNoStore($res)
    {
        foreach ($res as $v){
            M('shopcart')->delete($v['id']);
        }
    }

    /** 获取库存不足商品的数量
     * @param $res
     * @return string
     */
    public function getNoStoreStr($res)
    {
        $msg = '';
        foreach ($res as $v){
            $msg .= $v['goods_name'].' ';
        }
        $msg .= '库存不足,请重新下单';
        return $msg;
    }

    public function getCatByBase($base, $shop_id)
    {
        $key = "getCatByBase_".$base."_".$shop_id;
        $res = S($key);
        if(empty($res)){
            $res =M('shop_category')->where(array('base_id'=>$base,'shop_id'=>$shop_id))->getField('id');
            S($key,$res,TIME_ONE_HOUR);
        }
        return $res;
    }

    public function getGoodsFormat($id)
    {
        $key = "getGoodsFormat_".$id;
        $res = S($key);
        if(empty($res)){
            $fs = M('shop_goods_format')->where(array('goods_id'=>$id))->order('id asc')->select();
            $as = M('shop_goods_attr')->where(array('goods_id'=>$id))->order('id asc')->select();
            $img = M('shop_goods')->where(array('id'=>$id))->getField('detail_img');
            $res['format'] = $fs;
            $res['attr'] = $as;
            $res['img'] = $img;
            S($key,$res,TIME_ONE_HOUR);
        }
        return $res;
    }

    public function loadGroupSet($pid, $sid)
    {
        $key = "loadGroupSet_".$pid."_".$sid;
        $res = S($key);
        if(empty($res)){
            $res = M('groupmeal_setting')->where(array('partner_id'=>$pid,'shop_id'=>$sid))->find();
            S($key,$res,TIME_ONE_HOUR);
        }
        return $res;
    }

}