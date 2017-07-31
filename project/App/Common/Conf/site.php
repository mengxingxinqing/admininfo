<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-26
 * Time: 11:48
 */
return array(
    'BASE_PATH' =>'/home/wwwroot/test.weilai517.com/',
    'BASE_URL' =>'http://test.weilai517.com/',
    'ACCESS_KEY'=>'haodianwangluo',
    "API_WEATHER"=>'http://api.avatardata.cn/Weather/Query?key=f4e0a9bed3834887af93f199e53c938d&cityname=',
    "OSS_PATH"=>'https://sfwm.oss-cn-beijing.aliyuncs.com/',
    'API_QRCODE'=>'http://pan.baidu.com/share/qrcode',//?w=300&h=300&url=http://lanyes.org
    
    //云吧推送配置
    "YB_URL"=>"http://rest.yunba.io:8080",
    "YB_ID"=>'578464716e72de8249271750',
    'YB_SEC'=>'sec-mwjZjMCyd2evdEmTCfR9pkjnPcGsOtDzfSgeGdffsFUhnhvf',
    "YB_METHOD"=>'publish',
    'YB_ORDER_TOPIC'=>'order',
    //系统客服电话
    'SERVICE_PHONE'=>"02989652288",
    //资金低于下线+10 发送短信
    'SMS_LINE'=>10,
    //系统电话
    'BACK_PARTNER_INC_MONEY'=>200,

    //基础费率配置
    "MONEY_MIN"=>20,
    "MONEY_MAX"=>200,
    "MONEY_RATE"=>0.03,
    "MONEY_DEFAULT"=>100,
    //电话费扣费
    "PHONE_RATE"=>0.08,
    
);