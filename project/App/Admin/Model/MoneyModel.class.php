<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-08-12
 * Time: 10:36
 */

namespace Partner\Model;


class MoneyModel extends BaseModel
{
    public function getShopMoneyList($pid,$m='')
    {
        if(empty($m)){
            $m = (int)date('m');
        }
//        $list = M('shop_conf')->where(array('partner_id'=>$pid))->select();
//        $shopModel = new ShopModel();
//        foreach ($list as $k=>$v){
//            $list[$k]['shop_name'] = $shopModel->getShopName($v['shop_id']);
//
//        }
        $sql = "select c.*,s.name as shop_name, p.dec_money,p.dec_phone_money,p.back_inc_money,p.inc_money from sm_shop_conf c LEFT JOIN sm_shop s on c.shop_id=s.id LEFT JOIN sm_month_paylog p on c.shop_id=p.shop_id and p.months=$m WHERE s.is_del=0 and c.partner_id=".$pid;
        $list = M()->query($sql);
        return $list;
    }

    public function addOrder($data){
        return M('partner_payorder')->add($data);
    }

    public function getLeastLog($pid,$page){
        // return M('partner_paylog')->where(array('partner_id'=>$pid))->order('id desc')->limit($num)->select();
        $sql = "SELECT * FROM sm_partner_paylog WHERE partner_id=".$pid." ORDER BY id DESC";
        $data = $this->getPage($sql,$page);
        return $data;
    }

    public function getDeclist($pid)
    {
        $list = M('partner_declog')->where(array('partner_id'=>$pid))->order('id desc')->select();
        return $list;
    }

    public function getMonthMoney($pid,$m=''){
        if(empty($m)){
            $m = (int)date('m');
        }
        $info = M('month_paylog')->where(array('partner_id'=>$pid,'months'=>$m))->find();
        return $info;
    }
}