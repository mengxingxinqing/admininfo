<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-21
 * Time: 10:14
 */

namespace Home\Service;


use Base\Service\WechatHelper;

class ShareService
{

    public function getShare($pid,$uid='')
    {
        $helper = WechatHelper::get($pid);
        $data['signPackage'] = $helper->getJssdk()->GetSignPackage();
        $setting = self::getSetting($pid);
        $data['is_open'] = $setting['is_open'];
        if($data['is_open'] == 1 &&!empty($uid)){
            $data['hy']['title'] = $setting['title1'];
            $data['hy']['imgUrl'] = C('BASE_URL').$setting['img1'];
            $data['hy']['desc'] = $setting['content1'];
            $data['hy']['link'] = C('BASE_URL').U('Share/index',array('f'=>base64_encode($uid),'p'=>$pid));
            $data['pyq']['title'] = $setting['title2'];
            $data['pyq']['imgUrl'] = C('BASE_URL').$setting['img2'];
            $data['pyq']['link'] = C('BASE_URL').U('Share/index',array('f'=>base64_encode($uid),'p'=>$pid));
        }
        return $data;
    }

    public function getSetting($pid)
    {
        $setting = M('market_setting')->where(array('partner_id'=>$pid))->find();
        return $setting;
    }

}