<?php
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-06-29
 * Time: 10:03
 */

namespace Base\Service;


class AccesstokenService
{
    /**
     * 获取微信的accesstoken,jsticket
     */
    public function index($pid,$type=0){
        return self::getacc($pid,$type);
    }





    private function getacc($pid,$type)
    {
        if($type == 0){
            $key = "access_token";
            $time = "ac_endtime";
        }else if($type == 1){
            $key = "js_ticket";
            $time = "js_endtime";
        }
        $acc = false;
        $info = M('wechat')->where(array('partner_id'=>$pid))->field('id,appid,appsecret,'.$key.','.$time)->find();
        $info['partner_id'] = $pid;
        if($info[$key]&&$info[$time]>time()){
            $acc = $info[$key];
        }else{
            $num = 4;
            for($i=0;$i<$num;$i++){
                if($type == 0){
                    $acc = self::refresh($info);
                }elseif($type == 1){
                    $acc = self::refreshJsTick($info);
                }
//                echo("pid=$pid type=$key  i=$i res:$acc");
                if($acc) break;
            }
        }

        return $acc;
    }

    private function refresh($info)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$info['appid']."&secret=".$info['appsecret'];
        $json = file_get_contents($url);
        $data = json_decode($json,true);
        if($data){
            if($data['errcode']){
                \Think\Log::write("error code ".$data['errcode'].' msg='.$data['errmsg']);
                return false;
            }else{
                $res['access_token'] = $data['access_token'];
                $res['ac_endtime'] = time()+3600;
                M('wechat')->where(array('id'=>$info['id']))->save($res);
                return $res['access_token'];
            }
        }

    }

    private function refreshJsTick($info){
        $pid = $info['partner_id'];
        $accessToken = self::getacc($pid,0);
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
        $res = json_decode(file_get_contents($url));
        $ticket = $res->ticket;
        if ($ticket) {
            $data['js_endtime'] = time() + 7000;
            $data['js_ticket'] = $ticket;
            M('wechat')->where(array('partner_id'=>$pid))->save($data);
            return $data['js_ticket'];
        }
    }
}