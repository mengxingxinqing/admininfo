<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/11/12 0012
 * Time: 下午 6:19
 */

namespace Home\Service;


use Base\Service\WechatService;
use Home\Model\BonusModel;
use Home\Model\CountModel;

class IndexService
{
    public function getShopInfo($shop_id){
//        $shopModel = new
    }

    public function getQrcode($uid){
        $wechatService = new WechatService();
        $showpath = $wechatService->getMyQrcode($uid);
        $bonusModel = new BonusModel();
        $bonusModel->addQrcode($uid,$showpath);
        return $showpath;
    }

    public function getMyQrcode($uid){
        $bonusModel = new BonusModel();
        if($bonusModel->qrcodeOvertime($uid)){
            $path = self::getQrcode($uid);
        }else{
            $path = $bonusModel->getMyQrcode($uid);
        }
        $path = __ROOT__.'/'.$path;
        return $path;
    }

    public function setShareType($type){
         $model = new CountModel();
         return $model->setShareType($type);

    }
}