<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-08
 * Time: 14:28
 */

namespace Partner\Model;


class PartnerModel extends BaseModel
{
    public function getStep($pid)
    {
        return M('partner')->where(array('id'=>$pid))->getField('status');
    }

    public function getInfo($pid)
    {
        $info = M('partner')->find($pid);
        return $info;
    }

    public function openPhone($pid, $status)
    {
        M('partner_conf')->where(array('partner_id'=>$pid))->save(array('is_open_phone'=>$status));
    }

    /** 获取合作商户的模板id号
     * @param $pid 合作商户
     * @param $type 模板类型
     * @return mixed
     */
    public function getTplId($pid,$type){
        $tpl = M('wechat_tpl')->where(array('partner_id'=>$pid,'type'=>$type))->getField('tmpid');
        return $tpl;
    }

    public function getPartnerAccountMoney($partner_id){
        $money = M('shop_conf')->where(array('partner_id'=>$partner_id))->sum('money');
        return $money;
    }

    //获得商户的抽成比率
    public function getCommissionRate($partner_id){
        $rate = M('partner_conf')->where(array('partner_id'=>$partner_id))->getField('rate');
        return $rate;
    }

    public function getConf($pid)
    {
        $conf = M('partner_conf')->find($pid);
        return $conf;
    }

    public function isSetWeek($partner_id){
        $res = M('partner_conf')->where(array('partner_id'=>$partner_id))->getField('is_week');
        return $res;
    }

    public function isPcPrint($pid)
    {
        $res = M('partner_conf')->where(array('partner_id'=>$pid))->getField('is_pcclient');
        return $res;
    }

    public function getPartnerRate($partner_id){
        $rate = M('partner_conf')->where(array('partner_id'=>$partner_id))->getField('rate');
        return $rate;

    }
}