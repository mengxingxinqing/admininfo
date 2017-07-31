<?php
namespace Partner\Model;
/**
 * Created by PhpStorm.
 * User: 仝帅
 * Date: 2016-05-17
 * Time: 16:32
 */
class UserModel extends BaseModel
{

	public function getOpenid($uid){
		$openid = M('wechat_user')->where(array('id'=>$uid))->getField('openid');
		return $openid;
	}
	
    public function addGoods($data){
        return M('base_goods')->add($data);
    }

    public function getUserList($p,$shop_id,$data){
    	if($shop_id == 0){
    		$where = '';
    	}else{
    		$where .= ' and u.shop_id='.$shop_id;
    	}

        if($data['tel'] != ''){
            $where .= ' and ua.tel="'.$data['tel'].'"';
        }

        if($data['start_time'] !='' && $data['end_time'] !=''){
            $where .= ' and u.create_time>='.strtotime($data['start_time']).' and u.create_time<'.strtotime($data['end_time']);
        }

        if($data['buy_moneys_start'] !='' && $data['buy_moneys_end'] !=''){
            $where .= ' and ui.buy_moneys>='.$data['buy_moneys_start'].' and ui.buy_moneys<'.$data['buy_moneys_end'];
        }

        if($data['buy_times_start'] !='' && $data['buy_times_end'] !=''){
            $where .= ' and ui.buy_times>='.$data['buy_times_start'].' and ui.buy_times<'.$data['buy_times_end'];
        }

        if($data['invite_num_start'] !='' && $data['invite_num_end'] !=''){
            $where .= ' and ui.invite_num>='.$data['invite_num_start'].' and ui.invite_num<'.$data['invite_num_end'];
        }

    	$sql = "SELECT u.id,u.nickname,u.create_time,u.remark,u.from_way,ui.buy_times,ui.invite_num,ui.visit_index_num,ui.buy_moneys,ua.address,ua.tel FROM sm_wechat_user u LEFT join sm_user_address ua ON u.id=ua.uid LEFT JOIN sm_user_info ui ON u.id=ui.uid WHERE u.partner_id=".$_SESSION['partner_id'].$where." ORDER BY u.id DESC";
    	return $this->getPage($sql,$p);
    }

    public function getShopList(){
    	return M('shop')->where(array('partner_id'=>$_SESSION['partner_id'],"status"=>0,'is_del'=>0))->select();
    }

    public function setRemark($uid,$remark){
        return M('wechat_user')->where(array('id'=>$uid))->save(array('remark'=>$remark));
    }

    public function setAddress($uid,$address){
        $res = M('user_address')->where(array('uid'=>$uid))->save(array('address'=>$address));
        return $res;
    }

    public function scanCount($shop_id,$start_time,$end_time,$partner_id=''){
        if(empty($partner_id)){
            $partner_id = session('partner_id');
        }

        $where = 'scan_time>='.$start_time.' and scan_time<'.$end_time.' and partner_id='.$partner_id;
        if($shop_id != 0){
            $where .= ' and shop_id='.$shop_id;
        }
        $res = M('wechat_user')->where($where)->count();
        // echo M()->getLastSql();die;
        return $res;
    }

    public function leaveCount($shop_id,$start_time,$end_time,$partner_id=''){
        if(empty($partner_id)){
            $partner_id = session('partner_id');
        }
        $where = 'leave_time>='.$start_time.' and leave_time<'.$end_time.' and partner_id='.$partner_id;
        if($shop_id != 0){
             $where .= ' and shop_id='.$shop_id;
        }
        $res = M('wechat_user')->where($where)->count();
        // echo M()->getLastSql();die;
        return $res;
    }

    public function getBonusList($p,$shop_id,$start_time,$end_time){
        $where = '';
        if($shop_id != 0){
            $where .= ' AND b.shop_id='.$shop_id;
        }

        if(!empty($start_time) && !empty($end_time)){
            $where .= ' AND b.create_time>='.strtotime($start_time).' AND b.create_time<'.strtotime($end_time);
        }

        $sql = "SELECT b.uid,u.nickname,b.money,b.create_time,b.used_time,b.end_time,b.param,bt.name AS bt_name FROM sm_bonus b LEFT JOIN sm_wechat_user u ON b.uid=u.id LEFT JOIN sm_bonus_type bt ON b.type= bt.id WHERE b.partner_id=".$_SESSION['partner_id'].$where." ORDER BY b.create_time DESC";
        return $this->getPage($sql,$p);

    }

    //获取今日新关注用户
    public function getNewUserNum($partner_id){
        $time = getTodayTimespan();
        $sql = "select count(*) as num from sm_wechat_user where create_time>".$time.' and partner_id='.$partner_id.' and status=0';
        $res = self::getOneKey($sql,'num');
        return $res;
    }

    public function totalUser($partner_id){
        $sql = "select count(*) as num from sm_wechat_user where partner_id=".$partner_id.' and status=0';
        $res = self::getOneKey($sql,'num');

        return $res;
    }

    /** 获取合作商户的用户数
     * @param $pid
     * @return mixed
     */
    public function getUserNum($pid)
    {
        $num = M('wechat_user')->where(array('partner_id'=>$pid))->count();
        return $num;
    }

    /** 获取合作商户某段时间内新用户数 1 所有新用户 2 被邀请活动邀请的 3被渠道二维码邀请的
     * @param $pid
     * @param $start
     * @param $end
     * @param $type
     */
    public function getInviteUserNumInTime($pid,$start,$end,$type)
    {
        if($type == 1){
            $af = '';
        }elseif ($type == 2){
            $af = "  and inviter_id>0";
        }elseif ($type == 3){
            $af = " and qd_id>0";
        }
        $sql = "select count(*) as num from sm_wechat_user where create_time>$start and create_time<$end and partner_id=$pid".$af;
        $res = self::getOneKey($sql,'num');
        return $res;
    }

    public function getUserInfoById($id){
        $key = "getUserInfoById_".$id;
        $res = S($key);
        if(empty($res)){
            $res = M('wechat_user')->where(array('id'=>$id))->find();
            S($key,$res,TIME_ONE_HOUR);
        }
        return $res;
    }

    public function addBonus($data){
        $res = M('bonus')->add($data);
        return $res;
    }

    public function userHot($pid){
       $data = S("userHot_".$pid);
       $data = '';
        if(empty($data)){
            if($pid == 8){
                $sql = "SELECT a.lng,a.lat,o.buy_times+1 as count FROM sm_user_address a left join sm_user_info o on o.uid=a.uid where a.lat!=''";
            }else{
                $sql = "SELECT a.lng,a.lat,o.buy_times+1 as count FROM sm_user_address a left join sm_user_info o on o.uid=a.uid where a.partner_id=$pid and a.lat!=''";
            }
            
            $list = M()->query($sql);

            if(!empty($list)){
                $data['center'] = $list[0]['lng'].','.$list[0]['lat'];
                $list = json_encode($list);
                $data['list'] = $list;
                S("userHot_".$pid,$data,TIME_FIVE_MINUTE);
            }
        }
        return $data;
    }

    public function getPartnerCity($pid)
    {
        $sql = "select name from sm_areas where id=(select city_id from sm_partner where id=$pid)";
        $name = self::getOneKey($sql,'name');
        return $name."市";
    }

    public function orderTime($pid,$start,$end){
        $timediff = $end-$start;
        $days = intval($timediff/86400);

        $sql = "SELECT from_unixtime(`create_time`,'%H') time,count(*) num FROM `sm_order_bak` where partner_id=$pid and create_time>=$start and create_time<$end GROUP BY from_unixtime(`create_time`,'%H') ;";
        $list = M()->query($sql);

        for($i=1;$i<24;$i++){
            if($i<10){
                $index = '0'.$i;
            }else{
                $index = ''.$i;
            }
            if(empty($list[$index])){
                $data[] = 0;
            }else{
                $data[] = $list[$index];
            }
        }
        return $data;
    }

    //流失用户召回
    public function getUserBackList($p,$partner_id,$shop_id){
        $where = " u.partner_id=".$partner_id;
        if($shop_id != 0){
            $where .= ' and u.shop_id='.$shop_id;
        }

        $where .= " and u.leave_time!='' ";

        $where .= " and ui.buy_times!=0 ";

        $sql = "SELECT u.id,u.nickname,u.create_time,u.leave_time,u.status,ui.buy_times,ui.visit_index_num,ui.buy_moneys,ua.address FROM sm_wechat_user u LEFT join sm_user_address ua ON u.id=ua.uid LEFT JOIN sm_user_info ui ON u.id=ui.uid WHERE ".$where." ORDER BY u.leave_time DESC";
        return $this->getPage($sql,$p);
    }

}