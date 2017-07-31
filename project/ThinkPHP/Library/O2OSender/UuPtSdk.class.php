<?php
/**
 * Created by PhpStorm.
 * User: tongshuai
 * Date: 2016-12-27
 * Time: 22:28
 */

namespace O2OSender;


class UuPtSdk
{

    private $appid = '';
    private $appKey = '';

    public function __construct($appid, $appsec)
    {
        $this->appid = $appid;
        $this->appKey = $appsec;
    }
    private function request_post($url = '', $post_data = array()) {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $arr = [];
        foreach ($post_data as $key => $value) {
            $arr[] = $key.'='.$value;
        }
        $curlPost = implode('&', $arr);

        $postUrl = $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$postUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    // 生成guid
    private function guid(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
            return $uuid;
        }
    }

    // 生成签名
    private function sign($data) {
        $arr = [];
        foreach ($data as $key => $value) {
            $arr[] = $key.'='.$value;
        }
        $arr[] = 'key='.$this->appKey;
        $str = strtoupper(implode('&', $arr));
        var_dump('签名前:'.$str);
        echo "<br />";
        return strtoupper(md5($str));
    }

    public function sendSms($tel)
    {
        $url = "http://openapi.uupaotui.com/v2_0/binduserapply.ashx";
        $data['user_mobile'] = $tel;
        $data['user_ip'] = $_SERVER['SERVER_ADDR'];
        $res = self::getResult($url,$data);
        return $res;
    }

    public function getOpenid($tel,$code)
    {
        $url = "http://openapi.uupaotui.com/v2_0/bindusersubmit.ashx";
        $data['user_mobile'] = $tel;
        $data['validate_code'] = $code;
        $res = self::getResult($url,$data);
        return $res;
    }

    public function getPrice($openid)
    {
        $url = 'http://openapi.uupaotui.com/v2_0/getorderprice.ashx';
        $data['openid'] = $openid;
        $data['from_lng'] = 0;
        $data['from_lat'] = 0;
        $data['to_lng'] = '';
        $data['to_lat'] = '';
        $data['city_name'] = '西安市';
        $data['to_address'] = '';
        $data['from_address'] = '';
        $res = self::getResult($url,$data);
        return $res;
    }

    public function addOrder($openid,$price,$price_token)
    {
        $url = "http://openapi.uupaotui.com/v2_0/addorder.ashx";
        $data['special_type'] = 0;
        $data['push_type'] = 0;
        $data['openid'] = $openid;
        $data['balance_paymoney'] = 0;
        $data['order_price'] = $price;
        $data['price_token'] = $price_token;
        $res = self::getResult($url,$data);
        return $res;
    }

    public function cancelOrder($openid,$order_code)
    {
        $url = "http://openapi.com/v2_0/cancelorder.ashx";
        $data['openid'] = $openid;
        $data['reason'] = "无人接单";
        $data['order_code'] = $order_code;
        $res = self::getResult($url,$data);
        return $res;
    }

    private function getResult($url,$data){
        $guid = str_replace('-', '', self::guid());
        $data['appid'] = $this->appid;
        $data['nonce_str'] = $guid;
        $data['timestamp'] = time();
        ksort($data);
        $data['sign'] = self::sign($data);
        $res = self::request_post($url,$data);
        return $res;
    }
}