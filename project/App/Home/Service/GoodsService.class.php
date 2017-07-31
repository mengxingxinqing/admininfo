<?php
namespace Home\Service;
use Home\Model\GoodsModel;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/10 0010
 * Time: 下午 3:21
 */
class GoodsService
{
    public function getCategory($shopid){
        $partner_id = session('partner_id'); 
        $res = S('category_'.$partner_id.'_'.$shopid);
        // $res = '';
        if(empty($res)){
            $model = new GoodsModel();
            $res = $model->getCategory($shopid);
            S('category_'.$partner_id.'_'.$shopid,$res,TIME_FIVE_MINUTE);
        }
        return $res;
        
    }

    public function getGoodsList($cat,$shop){
        //$partner_id = session('partner_id');
        $res = S('goodsList_'.$cat);
        $res = '';
        if(empty($res)){
            $model = new GoodsModel(); 
            $res = $model->getGoodsList($cat,$shop);
            S('goodsList_'.$cat,$res,TIME_FIVE_MINUTE);
        }   
        return $res;
    }

    public function getPreGoodsList($cat,$shop)
    {
        $res = S('goodsList_'.$cat."_".$shop);
        $res = '';
        if(empty($res)){
            $model = new GoodsModel();
            $res = $model->getPreGoodsList($cat,$shop);
            S('goodsList_'.$cat."_".$shop,$res,TIME_FIVE_MINUTE);
        }
        return $res;
    }

    // getGroupGoodsList

    public function getGroupGoodsList($shop)
    {
        $res = S('goodsList_'.$shop);
        $res = '';
        if(empty($res)){
            $model = new GoodsModel();
            $res = $model->getGroupGoodsList($shop);
            S('goodsList_'.$shop,$res,TIME_FIVE_MINUTE);
        }
        return $res;
    }


    public function getPreGoodsStatus($shop_id, $partner_id, $cat)
    {
        $model = new GoodsModel();
        return $model->getPreGoodsStatus($shop_id,$partner_id,$cat);
    }

    public function getAllGoodsList($cat,$shop){
        $model = new GoodsModel();

        $res = $model->getAllGoodsList($cat,$shop);
        return $res;
    }


    public function getGoodsStatus($shop,$partnerid,$cat){
        $model = new GoodsModel();
        return $model->getGoodsStatus($shop,$partnerid,$cat);
    }

    public function getShopcart($uid,$shopid){
        $model = new GoodsModel();
        return $model->getShopcart($uid,$shopid);
    }


    public function getPartnerList($shop){
        $model = new GoodsModel();
        return $model->getPartnerList($shop);
    }
    
    public function offGoods($sid,$gid,$is_off){
        $model = new GoodsModel();
        return $model->offGoods($sid,$gid,$is_off);
    }
    public function getCatByBaseCat($base_id, $shop_id){
        $model = new GoodsModel();
        return $model->getCatByBaseCat($base_id,$shop_id);
    }

    public function getGoodsFormat($id)
    {
        $model = new GoodsModel();
        return $model->getGoodsFormat($id);
    }

    public function loadGroupSet($pid,$sid)
    {
        $model = new GoodsModel();
        return $model->loadGroupSet($pid,$sid);
    }
}