<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-22
 * Time: 11:07
 */

//一天的秒数
define("DAY_SECONDS",86400);
//系统api访问授权key
define('API_KEY',"haodianwangluo");

//微信支付
define("PAY_WX",1);
define("PAY_XIANXI",2);
define("PAY_RECHARGE",3);

//是否开启
define("IS_OPEN",1);

//红包类型
define("BONUS_NEW",1);
define("BONUS_INV",2);
define("BONUS_ACTIVE",3);
//活动 红包获取方式
define('MARKET_GET_BASE',0);
define('MARKET_GET_RANGE',1);
define('MARKET_GET_ORDER',2);

//模板类型
define("TPL_BONUS",2);
define("TPL_PROCESSORDER",7);
define("TPL_USERASK",6);
define("TPL_CANCELORDER",4);

//微信自动回复类型 0 全文默认回复 1 关注自动回复 2 关键字回复
define("AUTO_DEFAULT",0);
define("AUTO_SUB",1);
define("AUTO_TEXT",2);
define("AUTO_VOICE",3);
define("AUTO_IMG",4);

//支付类型 1微信支付 2.线下支付
define("PAY_WECHAT",1);
define("PAY_MONEY",2);



//用户信息类型
define("ASK_TXT",1);

define("SUCCESS","success");
define("ERROR","error");

//商户金额基准线
define("MONEY_LINE",20);

//活动类型
//新用户发红包
define('ACTIVE_TYPE_NEWBONUS',7);


//支付位置
//0 支付到商户微信 1支付给系统
define("PAYTO_PARTNER",0);
//支付到系统微信
define("PAYTO_SYSTEM",1);

//当前充值状态 1 系统在收钱 0 商户在收钱
define("PAY_STATUS_INC",1);
define("PAY_STATUS_DEC",0);


//商户充值方式 1 流水充值 2 后台手动充值
define("PAY_TYPE_ORDER",1);
define("PAY_TYPE_PAY",2);


//缓存时间
define("TIME_FIVE_MINUTE", 300);
define("TIME_TEN_MINUTE", 600);
define("TIME_HALF_HOUR", 1800);
define("TIME_ONE_HOUR", 3600);
define("TIME_ONE_DAY", 86400);

return array();