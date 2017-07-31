<?php
namespace Base\Model;
class WechatModel {

    /** 获取合作商户的微信配置信息
     * @param $partner_id
     * @return mixed
     */
    public function getConfig($partner_id){
        $key = "wechat_getconfig_".$partner_id;
        $conf = S($key);
        if(empty($conf)){
            $conf = M('wechat')->where(array('partner_id'=>$partner_id))->find();
            $conf['access_token'] = getAccessToken($partner_id);
            S($key,$conf,3);
        }
        return $conf;
    }
}

?>