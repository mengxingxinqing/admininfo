<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/27 0027
 * Time: 上午 1:17
 */

namespace Home\Model;


use Base\Model\BaseModel;

class PartnerModel extends BaseModel
{
    /*public function getPartner($id){
        $sql = 'select openid from '.C('DB_PREFIX').'shop_partner p left join '.C('DB_PREFIX').'wechat_user u on p.partner_id=u.id where p.id='.$id;
        return M()->query($sql);
    }*/

    public function getPartnerInfo($partner_id){
        $res = M('partner')->where(array('id'=>$partner_id))->find();
        return $res;
    }


    public function getPartnerId($uid){
        $partner_id = M('Partner')->where(array('uid'=>$uid))->getField('id');
        if (empty($partner_id)) {
            $partner_id = M('sender')->where(array('uid'=>$uid))->getField('partner_id');
        }
        return $partner_id;
    }

    public function getType($partner_id,$shop_id){
        $type = M('partner_shop')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->getField('type');
        return $type;
    }


    public function getPartner_Account_Money($partner_id,$type='today'){

        if ($type=='today') {
            $database = M('order');
        }else{
            $database = M('order_bak'); 
        }
        $list['wechat_count'] = $database->where(array('partner_id'=>$partner_id,'pay_type'=>'微信支付','status'=>2,'is_del'=>0))->count();
        $list['_count'] = $database->where(array('partner_id'=>$partner_id,'pay_type'=>'线下支付','status'=>2,'is_del'=>0))->count();
        $list['count'] = $database->where(array('partner_id'=>$partner_id,'status'=>2,'is_del'=>0))->count();
        $list['money']= $database->where(array('partner_id'=>$partner_id,'status'=>2,'is_del'=>0))->sum('real_price');
        $list['wechat_money']= $database->where(array('partner_id'=>$partner_id,'status'=>2,'pay_type'=>'微信支付','is_del'=>0))->sum('real_price');
        $list['_money']= $database->where(array('partner_id'=>$partner_id,'status'=>2,'pay_type'=>'线下支付','is_del'=>0))->sum('real_price');
        if($type = 'today'){
            $sql = 'SELECT SUM(od.total_price) AS foods_money,SUM(od.num) AS foods_num FROM tm_order_detail od LEFT JOIN tm_goods g ON od.base_goodsid=g.id LEFT JOIN tm_order o ON od.order_id=o.id WHERE g.type=1 AND o.status=2 AND o.is_del=0 AND od.partner_id='.$partner_id;
            $res = M()->query($sql);
            $list['foods_money'] = $res[0]['foods_money'];
            $list['foods_num'] = $res[0]['foods_num'];
        }
       
        return $list;
    }

    public function getPartnerStatus($shop){
        $sql = "select * from tm_partner_shop ps where ps.shop_id=".$shop." and ps.type=2";
        $list = M()->query($sql);
        $data = array();
        foreach($list as $item){
            $d['partner_id'] = $item['partner_id'];
            $res = self::checkTime($item['start_time'],$item['end_time']);
            if($item['is_close'] == 1 || $res == 0){
                $d['is_close'] = 1;
            }else{
                $d['is_close'] = 0;
            }
            $data[] = $d;
        }
        return $data;
    }

    /** 获取合作商户所在的市
     * @param $pid
     */
    public function getPartnerCity($pid)
    {
        $sql = "select a.name from sm_areas a left join sm_partner p on p.city_id=a.id where p.id=$pid";
        $res = M()->query($sql);
        return $res[0]['name'];
    }

     
    //获得商户配送费收费类型
    public function getSendChargeType($partner_id,$shop_id){
        $res= M('shop_conf')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id))->getField('sendcharge_type');
        return $res;
    }

    //获得商户配送费用列表
    public function getSendCharge($partner_id,$shop_id,$type){
        $res = M('sendcharge')->where(array('partner_id'=>$partner_id,'shop_id'=>$shop_id,'charge_type'=>$type))->select();
        return $res;
    }

    public function checkCharge($pid, $sid, $type, $distance)
    {
        if($type == 1){
            $res = M('sendcharge')->where(array('partner_id'=>$pid,'shop_id'=>$sid,'charge_type'=>$type))->getField('send_price');
        }else if($type == 2){
            $sql = "select send_price from sm_sendcharge where partner_id=$pid and shop_id=$sid and send_range_start<=$distance and send_range_end>$distance";

            $res = M()->query($sql);
            $res = $res[0]['send_price'];
        }
        return $res;
    }

    public function getPolygonSend($sid)
    {
        $key = "getPolygonSend_".$sid;
        $list = S($key);
        if(empty($list)){
            $list = M('polygon_send')->where(array('shop_id'=>$sid))->field('id,part,price')->select();
            S($key,$list,TIME_HALF_HOUR);
        }
        return $list;
    }
}