<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/4 0004
 * Time: 上午 12:44
 */

namespace Home\Model;


use Base\Service\WechatService;

class BonusModel
{
    public function hasBonus($shop_id,$uid){
        $where['uid'] = $uid;
        $where['shop_id'] = $shop_id;
        $where['is_used'] = 0;
        return M('bonus')->where($where)->count();
    }
    public function getList($uid,$shop_id,$partner_id='0'){
        self::overtime($uid);
        $where['uid'] = $uid;
        $where['shop_id'] = $shop_id;
        $where['status'] = 0;
        $where['partner_id'] = array('in',$partner_id);
//        $where['is_open'] = 1;
        $where['over_time'] = array('gt',time());
        $list = M('bonus')->where($where)->order('money desc')->select();
        return $list;
    }

    public function getActivityBonus($uid){
        $count = M('bonus')->where(array('uid'=>$uid))->count();
        if($count == 0){
            M('bonus')->add(array('uid'=>$uid,'bonus_num'=>5));
        }
        $num = M('bonus')->where(array('uid'=>$uid))->getField('bonus_num');
        return $num;
    }


    public function getUserBonus($uid){
        self::overtime($uid);
        $where = 'b.uid='.$uid;
        $where .= ' AND status=0';
        $sql = "SELECT b.*,bt.name AS bonus_type FROM sm_bonus b LEFT JOIN sm_bonus_type bt ON b.type=bt.id WHERE ".$where;
        $list = M()->query($sql);
        return $list;
    }

    public function getAllOverdue($uid,$shop_id){
        $where['uid'] = $uid;
        $where['shop_id'] = $shop_id;
        $where['is_used'] = 2;
        $overtime = time()-2*24*60*60;
        $where['over_time'] = array('gt',$overtime);
        $list = M('bonus')->where($where)->order('create_time desc')->select();
        return $list;
    }

    public function getMoney($id,$total){
        $where['id'] = $id;
        $where['is_used'] = 0;
//        $where['is_open'] = 1;
        $where['over_time'] = array('gt',time());
        $money = M('bonus')->where($where)->getField('money');
        if(empty($money)){
            $money = 0;
        }
        if((float)$money>=(float)$total){
            return 0;
        }else{
            return (float)$total-(float)$money;
        }
    }

    public function useBonus($id,$order_id){
        $data['used_time'] = time();
        $data['is_used'] = 1;
        $data['order_id'] = $order_id;
        $where['id'] = $id;
        M('bonus')->where($where)->save($data);
    }

    public function overtime($uid){
        $where['uid'] = $uid;
        $where['end_time'] = array('lt',time());
        $where['status'] = 0;
        M('bonus')->where($where)->save(array('status'=>2));
    }

    public function addQrcode($uid,$path){
        $exist = M('user_qrcode')->where(array('uid'=>$uid))->count();
        $data['qrcode'] = $path;
        $data['overtime'] = time()+1500;
        if($exist){
            $qrcode = M('user_qrcode')->where(array('uid'=>$uid))->getField('qrcode');
            $path = $basepath = $_SERVER['DOCUMENT_ROOT'].__ROOT__.'/'.$qrcode;
            //历史图片删掉
            delfile($path);
            M('user_qrcode')->where(array('uid'=>$uid))->save($data);
        }else{
            $data['uid'] = $uid;
            M('user_qrcode')->add($data);
        }
    }

    public function qrcodeOvertime($uid){
        $overtime = M('user_qrcode')->where(array('uid'=>$uid))->getField('overtime');
        return $overtime<time();
    }

    public function getMyQrcode($uid){
        return M('user_qrcode')->where(array('uid'=>$uid))->getField('qrcode');
    }

    /**
     * 红包过期提醒
     */
    public function alertBonus(){
        $time = date('Y-m-d',time());
        $sql = "select u.openid,count(*) as num from tm_bonus b left join tm_wechat_user u on u.id=b.uid where from_unixtime(b.end_time,'%Y-%m-%d')='".$time."' and b.status=0 and (select count(*) from tm_base_order bo where bo.uid=b.uid)=0 group by b.uid";
        $list = M()->query($sql);
        $data['first'] = '您好，您有红包即将到期';
        $data['keyword2'] = $time;
        $data['remark'] = '请您尽快使用，避免过期失效。';
        $service = new WechatService();
//        $url = $service->createBaseUrl(U("Home/Index/myjf"));
        foreach($list as $item){
            $data['keyword1'] = $item['num'].'个';
            $service->sendtpl(C('EXTEND_TPL'),$item['openid'],"",$data);
        }
    }
}