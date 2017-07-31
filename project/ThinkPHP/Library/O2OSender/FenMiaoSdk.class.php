<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/20
 * Time: 21:12
 */

namespace O2OSender;


class FenMiaoSdk
{
    private $URL = "http://139.196.19.182/xa/";
    //private $url = "http://xian.fenmiao.cc/";
    private $API_ADDORDER = 'api/shop_addOrder';
    private $API_CANCELORDER = 'api/shop_cancelOrder';
    private $key;

    public function __construct($key)
    {
        $this->key = $key;
    }
    public function addOrder($data)
    {
        $arr['key'] = $this->key;
//        $json['customerName'] = $data['customerName'];
//        $json['customerPhone'] = $data['customerPhone'];
//        $json['charge'] = $data['charge'];
//        $json['latitude'] = $data['latitude'];
//        $json['longitude'] = $data['longitude'];
//        $json['address'] = $data['address'];
//        $json['addressDetail'] = $data['addressDetail'];
//        $json['remark'] = $data['remark'];
//        $json['sourceCode'] = $data['sourceCode'];
//        $json['timeStatus'] = $data['timeStatus'];
        $json['productType'] = 4;
        $arr['jsonStr'] = json_encode($data);
        $url = $this->URL.$this->API_ADDORDER;
        $res = self::http_post($url,$arr);
        return json_decode($res,true);
    }

    public function cancelOrder($oid)
    {
        $arr['key'] = $this->key;
        $arr['code'] = $oid;
        $url = $this->URL.$this->API_CANCELORDER;
        $res = self::http_post($url,$arr);
        return json_decode($res,true);
    }

    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    private function http_post($url,$param,$post_file=false){
        $oCurl = curl_init();
        var_dump($param);
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach($param as $key=>$val){
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
        $header = array(
            'Content-Type: application/x-www-form-urlencoded',
        );
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);

        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }
}